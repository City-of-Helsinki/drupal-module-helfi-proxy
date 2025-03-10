<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\helfi_proxy\EventSubscriber\PurgeQueueCommitSubscriber;
use Drupal\KernelTests\KernelTestBase;
use Drupal\purge\Plugin\Purge\Queue\TxBuffer;
use Drupal\Tests\purge\Traits\TestTrait;

/**
 * Tests purge queue commit event subscriber..
 *
 * @group helfi_proxy
 */
class PurgeQueueCommitSubscriberTest extends KernelTestBase {
  use TestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'helfi_proxy',
    'path_alias',
    'purge',
    'purge_tokens',
    'purge_processor_cron',
    'purge_queuer_coretags',
    'purge_drush',
    'purge_purger_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->installConfig(['helfi_proxy', 'purge']);

    $this->initializePurgersService(['good']);
  }

  /**
   * Gets the purge queue commit subscriber service.
   *
   * @return \Drupal\helfi_proxy\EventSubscriber\PurgeQueueCommitSubscriber
   *   The purge queue commit subscriber.
   */
  private function getPurgeQueueCommitSubscriber() : PurgeQueueCommitSubscriber {
    return $this->container->get('helfi_proxy.purge_queue_commit_subscriber');
  }

  /**
   * Make sure the purge queue buffer is processed when the event is triggered.
   */
  public function testPurgeQueueCommit() : void {
    $queuer = $this->container->get('purge.queue');

    $reflection = new \ReflectionClass($queuer);
    $property = $reflection->getProperty('buffer');
    $property->setAccessible(TRUE);
    $buffer = $property->getValue($queuer);

    $sut = new PurgeQueueCommitSubscriber($queuer);

    $queuers = $this->container->get('purge.queuers');

    // Create the tag invalidation.
    $invalidator = $this->container->get('purge.invalidation.factory');
    $invalidations = $invalidator->get('tag', 'node:1');

    $this->assertEquals(0, $buffer->count(), 'The buffer should be empty before adding the invalidation.');

    // Add the tag invalidation to the queue.
    $queuer->add($queuers->get('coretags'), [$invalidations]);

    $this->assertEquals(1, $buffer->count(), 'The buffer should have one item after adding the invalidation.');

    // Get the current invalidation object from the buffer.
    $current = $buffer->current();

    $this->assertEquals(TxBuffer::ADDING, $buffer->getState($current), 'The item should be in the adding state.');

    // Commit the invalidation.
    $sut->onPurgeQueueCommit();

    $this->assertEquals(TxBuffer::RELEASED, $buffer->getState($current), 'The item should be in the released state.');
  }

}
