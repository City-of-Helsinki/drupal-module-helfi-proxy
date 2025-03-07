<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\EventSubscriber;

use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for the purge queue commit event.
 */
final class PurgeQueueCommitSubscriber implements EventSubscriberInterface {

  public const PURGE_QUEUE_COMMIT_EVENT_NAME = 'helfi_proxy.purge_queue_commit';

  /**
   * Constructs a PurgeQueueCommitSubscriber object.
   */
  public function __construct(
    private readonly QueueServiceInterface $purgeQueue,
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
      self::PURGE_QUEUE_COMMIT_EVENT_NAME => ['onPurgeQueueCommit'],
    ];
  }

}
