<?php

namespace Drupal\tagify;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Matcher class to get autocompletion results for entity reference.
 */
class TagifyEntityAutocompleteMatcher implements TagifyEntityAutocompleteMatcherInterface {

  /**
   * The entity reference selection handler plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a TagifyEntityAutocompleteMatcher object.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The entity reference selection handler plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(SelectionPluginManagerInterface $selection_manager, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->selectionManager = $selection_manager;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheridoc}
   */
  public function getMatches($target_type, $selection_handler, array $selection_settings, $string = '', array $selected = []) {
    $matches = [];

    $options = $selection_settings + [
        'target_type' => $target_type,
        'handler' => $selection_handler,
      ];
    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $match_limit = isset($selection_settings['match_limit']) ? (int) $selection_settings['match_limit'] : 10;
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $match_limit + count($selected));

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $bundle => $values) {

        foreach ($values as $entity_id => $label) {

          // Filter out already selected items.
          if (in_array($entity_id, $selected)) {
            continue;
          }

          // Allow whatever type of entity to use it.
          $entity_type_bundle = $this->entityTypeManager->getStorage($target_type)->getEntityType()->getKey('bundle');
          $entity_storage = $this->entityTypeManager->getStorage($target_type)->loadByProperties([$entity_type_bundle => $bundle]);
          foreach ($entity_storage as $entity) {
            $matches[$entity->id()] = $this->buildTagifyItem($entity);
          }
        }
      }
      $matches = array_slice($matches, 0, $match_limit, TRUE);

      $this->moduleHandler->alter('tagify_autocomplete_matches', $matches, $options);
    }

    return array_values($matches);
  }

  /**
   * Builds the array that represents the entity in the tagify autocomplete.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being added to the Tagify autocomplete.
   *
   * @return array
   *   The tagify item array. Associative array with the following keys:
   *   - 'entity_id':
   *     The referenced entity ID.
   *   - 'label':
   *     The text to be shown in hte autocomplete and tagify, IE: "My label"
   *   - 'type':
   *     The type of the entity being represented., IE: tags
   *   - 'attributes':
   *     A key-value array of extra properties sent directly to tagify, IE:
   *     ['--tag-bg' => '#FABADA']
   */
  protected function buildTagifyItem(EntityInterface $entity): array {
    return [
      'entity_id' => $entity->id(),
      'label' => Html::decodeEntities($entity->label()),
      'type' => $entity->bundle(),
    ];
  }

}
