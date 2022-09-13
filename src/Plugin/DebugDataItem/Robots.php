<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Plugin\DebugDataItem;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\helfi_api_base\DebugDataItemPluginBase;
use Psr\Container\ContainerInterface;

/**
 * Plugin implementation of the debug_data_item.
 *
 * @DebugDataItem(
 *   id = "robots",
 *   label = @Translation("Robots"),
 *   description = @Translation("Robots data")
 * )
 */
class Robots extends DebugDataItemPluginBase implements ContainerFactoryPluginInterface {

  protected bool $robots_header;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    try {
      $instance->robots_header = (bool) $container->get('config.factory')
        ->get('helfi_proxy.settings')
        ->get('robots_header_enabled');
    }
    catch (\Exception $e) {
      $instance->robots_header = FALSE;
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function collect(): array {
    $data['DRUPAL_X_ROBOTS_TAG_HEADER'] = $this->robots_header;
    return $data;
  }

}
