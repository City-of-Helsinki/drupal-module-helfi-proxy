<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\PathProcessor;

use Drupal\Core\Language\LanguageInterface;
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

    if (in_array($prefix, $this->sitePrefix->getPrefixes())) {
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
    BubbleableMetadata $bubbleable_metadata = NULL,
  ) : string {

    if (!isset($options['language'])) {
      return $path;
    }
    $language = $options['language'];

    if ($options['language'] instanceof LanguageInterface) {
      $language = $options['language']->getId();
    }
    // Use an already resolved language to figure out active prefix
    // since it might be different from content language.
    $prefix = $this->sitePrefix->getPrefix($language);

    $bubbleable_metadata?->addCacheContexts(['site_prefix:' . $prefix])
      ->addCacheableDependency($this->sitePrefix);

    if ($prefix) {
      $options['prefix'] .= $prefix . '/';
    }

    return $path;
  }

}
