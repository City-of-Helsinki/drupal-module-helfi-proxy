<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy;

/**
 * A value object to store HTML attribute data.
 */
final class AttributeMap {

  /**
   * Constructs a new instance.
   *
   * @param string $tagSelector
   *   The tag.
   * @param string $attribute
   *   The atribute.
   * @param bool $forceRelative
   *   Whether the given value needs to be converted to relative url.
   * @param bool $toAssetPath
   *   Whether the URL should have asset path (like /assets/to/image.svg).
   * @param bool $toSitePrefixed
   *   Whether the url should have a site prefix (like /fi/site-prefix/link).
   */
  public function __construct(
    public string $tagSelector,
    public string $attribute,
    public bool $forceRelative = FALSE,
    public bool $toAssetPath = FALSE,
    public bool $toSitePrefixed = FALSE
  ) {
  }

}
