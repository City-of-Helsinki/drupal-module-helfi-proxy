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
   * @param bool $forceRelative
   *   Whether the given value needs to be converted to relative url.
   * @param bool $assetPath
   *   Whether the URL should have asset path (like /assets/to/image.svg).
   * @param bool $sitePrefix
   *   Whether the url should have a site prefix (like /fi/site-prefix/link).
   */
  public function __construct(
    public ?string $tagSelector,
    public ?string $attribute,
    public bool $forceRelative = FALSE,
    public bool $assetPath = FALSE,
    public bool $sitePrefix = FALSE
  ) {
    if ($this->assetPath && $this->sitePrefix) {
      throw new \InvalidArgumentException('Cannot set both asset path and prefix to true.');
    }
  }

}
