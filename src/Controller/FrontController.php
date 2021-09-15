<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Empty front page controller.
 */
final class FrontController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function index() :array {
    $build['content'] = [
      '#type' => 'markup',
      '#markup' => '',
    ];
    return $build;
  }

}
