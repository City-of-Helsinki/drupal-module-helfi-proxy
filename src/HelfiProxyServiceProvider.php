<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\helfi_proxy\Config\SitemapPathOverride;
use Drupal\helfi_proxy\EventSubscriber\TunnistamoRedirectUrlSubscriber;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers services for non-required modules.
 */
class HelfiProxyServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // We cannot use the module handler as the container is not yet compiled.
    // @see \Drupal\Core\DrupalKernel::compileContainer()
    $modules = $container->getParameter('container.modules');

    if (isset($modules['helfi_tunnistamo'])) {
      $container->register('helfi_proxy.tunnistamo_redirect_subscriber', TunnistamoRedirectUrlSubscriber::class)
        ->addTag('event_subscriber')
        ->addArgument(new Reference('language_manager'))
        ->addArgument(new Reference('helfi_proxy.proxy_manager'))
        ->addArgument(new Reference('helfi_proxy.active_prefix'));
    }

    if (isset($modules['simple_sitemap'])) {
      $container->register('helfi_proxy.sitemap_path_override', SitemapPathOverride::class)
        ->addTag('config.factory.override')
        ->addArgument(new Reference('helfi_proxy.proxy_manager'))
        ->addArgument(new Reference('helfi_proxy.active_prefix'));
    }

  }

}
