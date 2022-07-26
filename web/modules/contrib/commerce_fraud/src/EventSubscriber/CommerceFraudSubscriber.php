<?php

namespace Drupal\commerce_fraud\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\commerce_fraud\Entity\SuspectedOrder;

/**
 * Event subscriber, that acts on the place transition of commerce order.
 *
 * Used to apply commerce fraud rules and set fraud score.
 */
class CommerceFraudSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * The suspected order storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $suspectedOrderStorage;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The current language code.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Whether this order is suspected to be fraudulent.
   *
   * @var bool
   */
  protected $orderSuspectedFraudulent;

  /**
   * The suspected order entity for this order.
   *
   * @var \Drupal\commerce_fraud\Entity\SuspectedOrderInterface
   */
  protected $suspectedOrder;

  /**
   * Constructs a new FraudSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to be used.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, MessengerInterface $messenger, Connection $connection, ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->connection = $connection;
    $this->mailManager = $mail_manager;
    $this->messenger = $messenger;
    $this->langcode = $language_manager->getCurrentLanguage()->getId();
    $this->logStorage = $this->entityTypeManager->getStorage('commerce_log');
    $this->suspectedOrderStorage = $this->entityTypeManager->getStorage('suspected_order');
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.pre_transition' => ['setFraudScore'],
    ];
    return $events;
  }

  /**
   * Sets the Fraud score on placing the order.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function setFraudScore(WorkflowTransitionEvent $event) {

    $config = $this->configFactory->get('commerce_fraud.settings');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    // Get Order.
    $order = $event->getEntity();

    $this->suspectedOrder = $this->suspectedOrderStorage->loadByProperties(['order_id' => $order->id()]);
    $this->suspectedOrder = reset($this->suspectedOrder);

    $this->orderSuspectedFraudulent = TRUE;

    if (empty($this->suspectedOrder)) {
      $this->suspectedOrder = SuspectedOrder::create([
        'order_id' => $order->id(),
        'rules' => [],
      ]);
      $this->orderSuspectedFraudulent = FALSE;
    }

    // Get Rules.
    $rules = $this->entityTypeManager->getStorage('rules')->loadMultiple();

    // Apply rules to order.
    foreach ($rules as $rule) {

      // Rule already applicable to order.
      if ($this->suspectedOrder->hasRule($rule)) {
        continue;
      }

      // Apply the rule.
      // File contating apply function is plugin-fraud rule.
      $action = $rule->getPlugin()->apply($order);

      // Check if the rule applied.
      if (!$action) {
        continue;
      }

      // Get the name set in the entity.
      $rule_name = $rule->getPlugin()->getLabel();

      // Add a log to order activity.
      $this->logStorage->generate($order, 'fraud_rule_name', ['rule_name' => $rule_name])->save();

      $this->suspectedOrder->addRule($rule);

      $this->orderSuspectedFraudulent = TRUE;
    }

    // No rule applicable to order.
    if (!$this->orderSuspectedFraudulent) {
      return;
    }

    // Order is suspected.
    $this->suspectedOrder->save();

    // Compare order fraud score with block list cap set in settings.
    if ($this->suspectedOrder->getScore() <= $config->get('blocklist_cap')) {
      return;
    }

    $orderStopped = FALSE;

    // Cancel order if set in settings.
    if ($config->get('stop_order')) {
      $this->cancelFraudulentOrder($order);
      $orderStopped = TRUE;
    }

    // Sending the details of the blocklisted order via mail.
    $this->sendBlockListedOrderMail($order, $orderStopped);

  }

  /**
   * Returns name of fraud rules that applied to a order.
   *
   * @return array
   *   The name of fraud rules that were applied on the order.
   */
  public function getFraudRulesNames() {

    $rule_names = [];
    $rules = $this->suspectedOrder->getRules();

    foreach ($rules as $rule) {

      $rule_names[] = $rule->getPlugin()->getLabel()->render() . ": " . $rule->getScore();
    }
    return $rule_names;

  }

  /**
   * Cancels the order and sets its status to fraudulent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order.
   */
  public function cancelFraudulentOrder(OrderInterface $order) {
    // Cancelling the order and setting the status to fraudulent.
    $order->getState()->applyTransitionById('cancel');
    $order->getState()->setValue(['value' => 'fraudulent']);

    // Creating of log for the order and refreshing it on load.
    $this->logStorage->generate($order, 'order_fraud')->save();
    $order->setRefreshState(OrderInterface::REFRESH_ON_LOAD);
    $this->messenger->addWarning($this->t('This order is suspected to be
      fraudulent and cannot be completed. Contact the administrators for more
      info and help.'));
  }

  /**
   * Sends email about blocklisted orders to the email chosen in settings.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order.
   * @param bool $orderStopped
   *   Whether order stopped from completing due to fraud score.
   */
  public function sendBlockListedOrderMail(OrderInterface $order, $orderStopped) {

    // Mail details.
    $module = 'commerce_fraud';
    $key = 'send_blocklist';
    $to = $this->configFactory->get('commerce_fraud.settings')->get('send_email');

    // Mail message.
    $params['message'] = $this->getMailParamsForBlockList($order, $orderStopped);
    $params['order_id'] = $order->id();
    $send = TRUE;

    $this->mailManager->mail($module, $key, $to, $this->langcode, $params, NULL, $send);

  }

  /**
   * Return message with details about order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order.
   * @param bool $orderStopped
   *   Whether order stopped from completing due to fraud score.
   *
   * @return array
   *   Array of parameters for use in block listed mail.
   *   Keyed as:
   *   - site_name: Name of site.
   *   - order_id: Order ID.
   *   - user_id: User id
   *   - user_name: User name.
   *   - status: Current Order status.
   *   - placed: When was the order placed in m/d/y format.
   *   - fraud_score: Fraud score of order.
   *   - stopped: Whether order stopped from completing due to fraud score
   *   - fraud_notes: List of name of fraud rules that applied to order.
   */
  public function getMailParamsForBlockList(OrderInterface $order, $orderStopped) {
    return [
      'site_name' => $this->configFactory->get('system.site')->get('name'),
      'order_id' => $order->id(),
      'user_id' => $order->getCustomerId(),
      'user_name' => $order->getCustomer()->getDisplayName(),
      'status' => $order->getState()->getId(),
      'placed' => date('m/d/Y H:i:s', $order->getPlacedTime()),
      'fraud_score' => $this->suspectedOrder->getScore(),
      'stopped' => $orderStopped,
      'fraud_notes' => $this->getFraudRulesNames(),
    ];
  }

}
