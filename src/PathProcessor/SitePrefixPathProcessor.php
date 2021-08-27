<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * A path processor.
 *
 * Adds the 'site name' prefix to all in/outcoming URLs.
 */
final class SitePrefixPathProcessor implements OutboundPathProcessorInterface, InboundPathProcessorInterface {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $config;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('helfi_proxy.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $parts = explode('/', trim($path, '/'));
    $prefix = array_shift($parts);

    if (in_array($prefix, $this->config->get('prefixes') ?? [])) {
      $path = '/' . implode('/', $parts);
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound(
    $path,
    &$options = [],
    Request $request = NULL,
    BubbleableMetadata $bubbleable_metadata = NULL
  ) : string {

    if (!isset($options['language'])) {
      return $path;
    }

    $language = $options['language'];

    if ($options['language'] instanceof LanguageInterface) {
      $language = $options['language']->getId();
    }
    $prefix = $this->config->get('prefixes')[$language] ?? NULL;

    if ($bubbleable_metadata) {
      $bubbleable_metadata->addCacheContexts(['site_prefix:' . $prefix]);
    }
    if ($prefix) {
      $options['prefix'] .= $prefix . '/';
    }

    return $path;
  }

}
