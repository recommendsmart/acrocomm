<?php

namespace Drupal\commerce_fraud\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a confirmation form for resetting orders.
 */
class OrderResetForm extends ConfirmFormBase {

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new OrderResetForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match) {
    $this->order = $route_match->getParameter('commerce_order');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_order_reset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to reset order fraud score for order %id?', ['%id' => $this->order->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->order->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("Reset this orders fraud score to 0");
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset Fraud Score');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $suspectedOrderStorage = $this->entityTypeManager->getStorage('suspected_order');
    $suspectedOrder = $suspectedOrderStorage->loadByProperties(['order_id' => $this->order->id()]);

    $suspectedOrder = reset($suspectedOrder);

    if (!empty($suspectedOrder)) {
      $suspectedOrder->delete();
    }

    $this->messenger()->addMessage($this->t('The orders score has been reset.'));

    // Redirect to order lists page.
    $form_state->setRedirectUrl($this->order->toUrl('collection'));

  }

}
