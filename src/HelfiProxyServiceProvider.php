<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Registers services for non-required modules.
 */
class HelfiProxyServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) : void {
    // PathProcessorLanguage and SitemapPathProcessor have the same
    // priority. Alter SitemapPathProcessor's priority, so
    // SitePrefixPathProcessor can be run in between the two. Otherwise,
    // the routes provided by 'simple_sitemap' path won't be recognized
    // via the proxy path.
    if ($container->hasDefinition('simple_sitemap.path_processor')) {
      $definition = $container->getDefinition('simple_sitemap.path_processor');
      $tags = $definition->getTags();
      $tags['path_processor_inbound'][0]['priority'] = 200;
      $definition->setTags($tags);
    }
  }

}
