<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\helfi_proxy\ActiveSitePrefix;
use Symfony\Component\HttpFoundation\Request;

/**
 * A path processor.
 *
 * Adds the 'site name' prefix to all in/outcoming URLs.
 */
final class SitePrefixPathProcessor implements OutboundPathProcessorInterface, InboundPathProcessorInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_proxy\ActiveSitePrefix $sitePrefix
   *   The site prefix service.
   */
  public function __construct(private ActiveSitePrefix $sitePrefix) {
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) : string {
    $parts = explode('/', trim($path, '/'));
    $prefix = array_shift($parts);

    if ($prefix === $this->sitePrefix->getPrefix()) {
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
    $prefix = $this->sitePrefix->getPrefix();

    $bubbleable_metadata?->addCacheContexts(['site_prefix:' . $prefix])
      ->addCacheableDependency($this->sitePrefix);

    if ($prefix) {
      $options['prefix'] .= $prefix . '/';
    }

    return $path;
  }

}
