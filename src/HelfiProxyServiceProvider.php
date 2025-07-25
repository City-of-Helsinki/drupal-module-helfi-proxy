<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\helfi_proxy\EventSubscriber\TunnistamoRedirectUrlSubscriber;
use Symfony\Component\DependencyInjection\Reference;

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

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) : void {
    // We cannot use the module handler as the container is not yet compiled.
    // @see \Drupal\Core\DrupalKernel::compileContainer()
    $modules = $container->getParameter('container.modules');

    // @todo This can be removed once all projects have been changed to use
    // non-linguistic return url.
    if (isset($modules['helfi_tunnistamo'])) {
      $container->register('helfi_proxy.tunnistamo_redirect_subscriber', TunnistamoRedirectUrlSubscriber::class)
        ->addTag('event_subscriber')
        ->addArgument(new Reference('language_manager'))
        ->addArgument(new Reference('helfi_proxy.proxy_manager'))
        ->addArgument(new Reference('helfi_proxy.active_prefix'));
    }
  }

}
