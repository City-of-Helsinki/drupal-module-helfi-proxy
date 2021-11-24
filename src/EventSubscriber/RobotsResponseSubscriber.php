<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds a X-Robots-Tag to response headers.
 */
final class RobotsResponseSubscriber implements EventSubscriberInterface {

  public const X_ROBOTS_TAG_HEADER_NAME = 'DRUPAL_X_ROBOTS_TAG_HEADER';

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
   * @param \Drupal\Core\Path\CurrentPathStack $pathStack
   *   The path stack.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   The path matcher.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    private CurrentPathStack $pathStack,
    private AliasManagerInterface $aliasManager,
    private PathMatcherInterface $pathMatcher
  ) {
    $this->config = $configFactory->get('helfi_proxy.settings');
  }

  /**
   * Adds the robots response header.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   */
  private function addRobotHeader(Response $response) : void {
    $response->headers->add(['X-Robots-Tag' => 'noindex, nofollow']);
  }

  /**
   * Adds a X-Robots-Tag response header.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to respond to.
   */
  public function onResponse(ResponseEvent $event) : void {
    $response = $event->getResponse();

    if ($response instanceof CacheableResponseInterface) {
      $response->addCacheableDependency($this->config);
    }

    if (getenv(self::X_ROBOTS_TAG_HEADER_NAME)) {
      $this->addRobotHeader($response);
      // No need to check individual paths if robots header should be
      // added for every page.
      return;
    }

    if (!$paths = implode("\n", $this->config->get('robots_paths') ?? [])) {
      return;
    }
    $alias = $this->aliasManager
      ->getAliasByPath($this->pathStack->getPath($event->getRequest()));

    if ($this->pathMatcher->matchPath($alias, $paths)) {
      $this->addRobotHeader($response);
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
