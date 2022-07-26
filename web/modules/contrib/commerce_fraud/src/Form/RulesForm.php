<?php

namespace Drupal\commerce_fraud\Form;

use Drupal\commerce_fraud\CommerceFraudManager;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\Form\EntityDuplicateFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds Form for Rules Entity.
 */
class RulesForm extends EntityForm {

  use EntityDuplicateFormTrait;

  /**
   * The rule plugin manager.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayManager
   */
  protected $pluginManager;

  /**
   * Constructs a new RulesForm object.
   *
   * @param \Drupal\commerce_fraud\CommerceFraudManager $plugin_manager
   *   The rule plugin manager.
   */
  public function __construct(CommerceFraudManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.commerce_fraud_rule')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($this->pluginManager->getDefinitions())) {
      $form['warning'] = [
        '#markup' => $this->t('No rule plugins found.'),
      ];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_fraud\Entity\RulesInterface $gateway */
    $gateway = $this->entity;

    // Get the plugin labels.
    $plugins = array_column($this->pluginManager->getDefinitions(), 'label', 'id');
    asort($plugins);

    // Use the first available plugin as the default value.
    if (!$gateway->getPluginId()) {
      $plugin_ids = array_keys($plugins);
      $plugin = reset($plugin_ids);
      $gateway->setPluginId($plugin);
    }

    // The form state will have a plugin value if #ajax was used.
    $plugin = $form_state->getValue('plugin', $gateway->getPluginId());

    // Pass the plugin configuration only if the plugin hasn't been changed via
    // #ajax.
    $plugin_configuration = $gateway->getPluginId() == $plugin ? $gateway->getPluginConfiguration() : [];

    $wrapper_id = Html::getUniqueId('rules-form');
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';
    $form['#tree'] = TRUE;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $gateway->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $gateway->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_fraud\Entity\Rules::load',
      ],
      '#disabled' => !$gateway->isNew(),
    ];

    // Select for the plugin.
    $form['plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $plugin,
      '#required' => TRUE,
      '#disabled' => !$gateway->isNew(),
      '#ajax' => [
        'callback' => '::ajaxRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];

    // Configuration from commerce fraud rule plugins.
    $form['configuration'] = [
      '#type' => 'commerce_plugin_configuration',
      '#plugin_type' => 'commerce_fraud_rule',
      '#plugin_id' => $plugin,
      '#default_value' => $plugin_configuration,
    ];

    // Amount added to fraud score.
    $form['score'] = [
      '#type' => 'number',
      '#title' => $this->t('Score'),
      '#default_value' => 5,
      '#required' => TRUE,
    ];
    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Status'),
      '#options' => [
        0 => $this->t('Disabled'),
        1  => $this->t('Enabled'),
      ],
      '#default_value' => (int) $gateway->status(),
    ];

    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
    $gateway = $this->entity;
    $gateway->setPluginConfiguration($form_state->getValue(['configuration']));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->postSave($this->entity, $this->operation);
    $this->messenger()->addMessage($this->t('Saved the %label rule.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.rules.collection');
  }

}
