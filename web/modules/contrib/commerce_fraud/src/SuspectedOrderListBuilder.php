<?php

namespace Drupal\commerce_fraud;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Suspected order entities.
 *
 * @ingroup commerce_fraud
 */
class SuspectedOrderListBuilder extends EntityListBuilder {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new SuspectedOrderListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Suspected order ID');
    $header['created'] = $this->t('Created');
    $header['order_id'] = $this->t('Order ID');
    $header['score'] = $this->t('Total Score');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\commerce_fraud\Entity\SuspectedOrder $entity */
    $row['id'] = $entity->id();
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'short');

    $row['order_id'] = Link::createFromRoute(
      $entity->getOrderId(),
      'entity.suspected_order.edit_form',
      ['suspected_order' => $entity->id()]
    );
    $row['score'] = $entity->getScore();
    return $row + parent::buildRow($entity);
  }

}
