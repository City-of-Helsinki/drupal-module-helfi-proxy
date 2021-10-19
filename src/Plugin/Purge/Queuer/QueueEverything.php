<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Plugin\Purge\Queuer;

use Drupal\purge\Plugin\Purge\Queuer\QueuerBase;

/**
 * Queuer to queue everything.
 *
 * @PurgeQueuer(
 *   id = "helfi_proxy_queue_everything",
 *   label = @Translation("Queue everything"),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
final class QueueEverything extends QueuerBase {
}
