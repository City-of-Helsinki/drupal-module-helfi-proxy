<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds an X-Robots-Tag to response headers.
 */
final readonly class RobotsResponseSubscriber implements EventSubscriberInterface {

  public function __construct(
    #[AutowireServiceClosure(ConfigFactoryInterface::class)] private \Closure $configFactoryClosure,
    private CurrentPathStack $pathStack,
    private AliasManagerInterface $aliasManager,
    private PathMatcherInterface $pathMatcher,
  ) {
  }

  /**
   * Adds an X-Robots-Tag response header.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to respond to.
   */
  public function onResponse(ResponseEvent $event) : void {
    $response = $event->getResponse();
    $config = ($this->configFactoryClosure)()->get('helfi_proxy.settings');

    if (!$paths = implode("\n", $config->get(ProxyManagerInterface::ROBOTS_PATHS) ?? [])) {
      return;
    }
    $alias = $this->aliasManager
      ->getAliasByPath($this->pathStack->getPath($event->getRequest()));

    if ($this->pathMatcher->matchPath($alias, $paths)) {
      $response->headers->add(['X-Robots-Tag' => 'noindex, nofollow']);

      if ($response instanceof CacheableResponseInterface) {
        $response->addCacheableDependency($config);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events[KernelEvents::RESPONSE][] = ['onResponse', -100];

    return $events;
  }

}
