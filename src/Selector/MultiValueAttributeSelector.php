<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Selector;

/**
 * Defines a multivalue selector.
 */
class MultiValueAttributeSelector extends AttributeSelector {

  /**
   * Constructs a new instance.
   *
   * @param string $xpath
   *   The xpath.
   * @param string $attribute
   *   The attribute.
   * @param string $multivalueSeparator
   *   The multivalue separator.
   */
  public function __construct(
    string $xpath,
    string $attribute,
    public string $multivalueSeparator = ','
  ) {
    parent::__construct($xpath, $attribute);
  }

}
