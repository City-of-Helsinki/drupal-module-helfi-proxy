<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\helfi_api_base\Event\CacheTagInvalidateEvent;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
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
    #[Autowire('@purge.queue')] private readonly QueueServiceInterface $purgeQueue,
  ) {}

  /**
   * Handles the purge commit event.
   */
  public function onPurgeQueueCommit(): void {
    if (!method_exists($this->purgeQueue, 'commit')) {
      // The commit method is mostly used in tests. Check if the method exists
      // before invoking it.
      throw new \LogicException('QueueServiceInterface::commit() does not exist anymore.');
    }
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
