<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Plugin\DebugDataItem;

use Drupal\helfi_api_base\DebugDataItemPluginBase;

/**
 * Plugin implementation of the debug_data_item.
 *
 * @DebugDataItem(
 *   id = "robots",
 *   label = @Translation("Robots"),
 *   description = @Translation("Robots data")
 * )
 */
class Robots extends DebugDataItemPluginBase {

  /**
   * {@inheritdoc}
   */
  public function collect(): array {
    $data['DRUPAL_X_ROBOTS_TAG_HEADER'] = (bool)getenv('DRUPAL_X_ROBOTS_TAG_HEADER');
    return $data;
  }

}
