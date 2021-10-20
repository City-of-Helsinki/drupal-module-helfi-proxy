<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Empty front page controller.
 */
final class FrontController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function index() : array {
    $metadata = new BubbleableMetadata();
    $metadata->addCacheableDependency($this->config('helfi_proxy.settings'));

    $build['content'] = [
      '#type' => 'markup',
      '#markup' => '',
      '#cache' => [
        'tags' => $metadata->getCacheTags(),
      ],
    ];
    return $build;
  }

  /**
   * Gets the title.
   *
   * @return string
   *   The title.
   */
  public function title() : string {
    if ($title = $this->config('helfi_proxy.settings')->get('front_page_title')) {
      return $title;
    }
    return (string) $this->t('Front');
  }

}
