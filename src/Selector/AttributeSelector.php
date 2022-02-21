<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Selector;

/**
 * Defines a DOM selector.
 */
class AttributeSelector extends Selector {

  /**
   * Constructs a new instance.
   *
   * @param string $xpath
   *   The tag.
   * @param string $attribute
   *   The attribute.
   */
  public function __construct(
    string $xpath,
    public string $attribute,
  ) {
    parent::__construct($xpath);
  }

}
