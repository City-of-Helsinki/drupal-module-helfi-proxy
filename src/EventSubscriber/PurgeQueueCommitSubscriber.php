<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\helfi_api_base\Event\CacheTagInvalidateEvent;
use Drupal\purge\Plugin\Purge\Queue\QueueService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for the purge queue commit event.
 */
final class PurgeQueueCommitSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a PurgeQueueCommitSubscriber object.
   */
  public function __construct(
    #[Autowire('@purge.queue')] private readonly QueueService $purgeQueue,
  ) {}

  /**
   * Handles the purge commit event.
   */
  public function onPurgeQueueCommit(): void {
    $this->purgeQueue->commit();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CacheTagInvalidateEvent::class => ['onPurgeQueueCommit'],
    ];
  }

}
