<?php

namespace Drupal\commerce_fraud\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for Commerce Fraud Help page.
 */
class ContentController extends ControllerBase {

  /**
   * Displays the commerce fraud home page.
   *
   * @return array
   *   A render array.
   */
  public function content() {

    // @Todo To make this content more descriptive.
    return [
      '#markup' => $this->t('Detects potentially fraudulent orders'),
    ];

  }

}
