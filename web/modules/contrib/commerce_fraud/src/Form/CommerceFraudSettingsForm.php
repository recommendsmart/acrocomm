<?php

namespace Drupal\commerce_fraud\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure commerce fraud settings for this site.
 */
class CommerceFraudSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_fraud.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_fraud_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('commerce_fraud.settings');

    $form['commerce_fraud_caps'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#title' => $this->t('Commerce Fraud Caps Settings'),
    ];

    // Checklist Cap - default: 10.
    $form['commerce_fraud_caps']['checklist_cap'] = [
      '#title' => $this->t('Checklist cap'),
      '#description' => $this->t('If an order has a fraud score greater than the number specified, it will be considered checklisted.'),
      '#default_value' => $config->get('checklist_cap'),
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
    ];

    // Blocklist Cap - default: 20.
    $form['commerce_fraud_caps']['blocklist_cap'] = [
      '#title' => $this->t('Blocklist cap'),
      '#description' => $this->t('If an order has a fraud score greater than the number specified, it will be considered blocklisted.'),
      '#default_value' => $config->get('blocklist_cap'),
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
    ];

    // Used to stop fraudulent order that have a score more than blocklist cap.
    $form['stop_order'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Stop fraudulent orders'),
      '#description' => $this->t('Activate this to stop blocklisted orders from
        being completed. Warning, this may cause lost orders if enabled.'),
      '#default_value' => $config->get('stop_order'),
    ];

    // Email where details of fraudulent orders are sent.
    $form['send_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $config->get('send_email'),
      '#required' => TRUE,
      '#description' => $this->t('If an order is blocklisted its details will be sent to this email'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $checkListValue = $form_state->getValue('checklist_cap');
    $blockListValue = $form_state->getValue('blocklist_cap');

    // Checklist value should be less than the BlockList value.
    if ($checkListValue >= $blockListValue) {
      $form_state->setErrorByName('commerce_fraud_caps][checklist_cap', $this->t('Check List value cannot be equal to or more than Block List value'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Set the submitted configuration setting.
    $this->config('commerce_fraud.settings')
      ->set('blocklist_cap', $form_state->getValue('blocklist_cap'))
      ->set('checklist_cap', $form_state->getValue('checklist_cap'))
      ->set('stop_order', $form_state->getValue('stop_order'))
      ->set('send_email', $form_state->getValue('send_email'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
