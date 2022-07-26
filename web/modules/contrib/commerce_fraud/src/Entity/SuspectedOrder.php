<?php

namespace Drupal\commerce_fraud\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\commerce\Entity\CommerceContentEntityBase;

/**
 * Defines the Suspected order entity.
 *
 * @ingroup commerce_fraud
 *
 * @ContentEntityType(
 *   id = "suspected_order",
 *   label = @Translation("Suspected order"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_fraud\SuspectedOrderListBuilder",
 *     "views_data" = "Drupal\commerce\CommerceEntityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\commerce_fraud\Form\SuspectedOrderForm",
 *       "add" = "Drupal\commerce_fraud\Form\SuspectedOrderForm",
 *       "edit" = "Drupal\commerce_fraud\Form\SuspectedOrderForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\commerce_fraud\SuspectedOrderAccessControlHandler",
 *   },
 *   base_table = "suspected_order",
 *   data_table = "suspected_order_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer suspected order entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "order_id",
 *     "langcode" = "langcode",
 *     "rules" = "rules",
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/config/suspected_order/{suspected_order}",
 *     "add-form" = "/admin/commerce/config/suspected_order/add",
 *     "edit-form" = "/admin/commerce/config/suspected_order/{suspected_order}/edit",
 *     "delete-form" = "/admin/commerce/config/suspected_order/{suspected_order}/delete",
 *     "delete-multiple-form" = "/admin/commerce/config/suspected_order/delete",
 *     "collection" = "/admin/commerce/config/suspected_order",
 *   },
 * )
 */
class SuspectedOrder extends CommerceContentEntityBase implements SuspectedOrderInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->get('order_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderId($orderId) {
    $this->set('order_id', $orderId);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRules() {
    return $this->get('rules')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setRules($rule) {
    $this->set('rules', $rule);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addRule(RulesInterface $rule) {
    if (!$this->hasRule($rule)) {
      $this->get('rules')->appendItem($rule);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRule(RulesInterface $rule) {

    // Getting rules id.
    $values = $this->get('rules')->getValue();
    $rule_ids = array_map(function ($value) {
      return $value['target_id'];
    }, $values);

    // Check if rule exsists.
    return array_search($rule->id(), $rule_ids) !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getScore() {
    $score = 0;
    foreach ($this->getRules() as $rule) {
      $score += $rule->getScore();
    }

    return $score;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order ID'))
      ->setDescription(t('The Order ID for the Suspected order entity.'))
      ->addConstraint('SuspectedOrderID')
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_order')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rules'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Fraud Rules'))
      ->setDescription(t('List of rules applicable to order.'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'rules')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
