<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\helfi_api_base\EventSubscriber\DeployHookEventSubscriberBase;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Purge assets from varnish cache.
 */
final class DeploySubscriber extends DeployHookEventSubscriberBase {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\helfi_proxy\ProxyManagerInterface $proxyManager
   *   The proxy manager service.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $invalidationFactory
   *   The invalidation service.
   * @param \Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface $queuers
   *   The queuer service.
   * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $queue
   *   The purge queue service.
   */
  public function __construct(
    private readonly ProxyManagerInterface $proxyManager,
    private readonly InvalidationsServiceInterface $invalidationFactory,
    private readonly QueuersServiceInterface $queuers,
    private readonly QueueServiceInterface $queue,
  ) {
  }

  /**
   * Responds to 'helfi_api_base.post_deploy' event.
   *
   * @param \Symfony\Contracts\EventDispatcher\Event $event
   *   The event.
   */
  public function onPostDeploy(Event $event) : void {
    if (!$this->proxyManager->isConfigured(ProxyManagerInterface::ASSET_PATH)) {
      return;
    }
    $assetPath = $this->proxyManager->getConfig(ProxyManagerInterface::ASSET_PATH);

    $queuer = $this->queuers->get('helfi_proxy_queue_everything');
    // Purge all assets from Varnish cache. For example: /liikenne-assets/*
    // on Liikenne project.
    $this->queue->add($queuer, [
      $this->invalidationFactory->get('regex', ltrim($assetPath, '/')),
    ]);
  }

}
