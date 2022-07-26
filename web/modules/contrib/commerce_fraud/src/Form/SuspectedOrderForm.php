<?php

namespace Drupal\commerce_fraud\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Suspected order edit forms.
 *
 * @ingroup commerce_fraud
 */
class SuspectedOrderForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Suspected order.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Suspected order.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.suspected_order.canonical', ['suspected_order' => $entity->id()]);
  }

}
