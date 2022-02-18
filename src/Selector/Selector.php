<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Selector;

/**
 * Defines the base selector.
 */
abstract class Selector implements SelectorInterface {

  /**
   * Constructs a new instance.
   *
   * @param string $xpath
   *   The xpath.
   */
  public function __construct(public string $xpath) {
  }

}
