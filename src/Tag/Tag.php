<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Tag;

/**
 * A value object to store HTML attribute data.
 */
final class Tag {

  /**
   * Constructs a new instance.
   *
   * @param null|string $tagSelector
   *   The tag.
   * @param null|string $attribute
   *   The atribute.
   * @param bool $alwaysAbsolute
   *   Whether the given value needs to be converted to relative url.
   * @param bool $sitePrefix
   *   Whether the url should have a site prefix (like /fi/site-prefix/link).
   * @param bool $multipleValues
   *   Whether the item may contain more than one value.
   * @param string $multivalueSeparator
   *   The separator used for multi-value fields.
   */
  public function __construct(
    public ?string $tagSelector,
    public ?string $attribute,
    public bool $alwaysAbsolute = FALSE,
    public bool $sitePrefix = FALSE,
    public bool $multipleValues = FALSE,
    public string $multivalueSeparator = ','
  ) {
  }

}
