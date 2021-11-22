<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Tag;

/**
 * Repository to fetch Tags.
 */
final class Tags {

  /**
   * Gets all tags.
   *
   * @return \Drupal\helfi_proxy\Tag\Tag[]
   *   The tags.
   */
  public static function all() : array {
    return [
      'input' => new Tag('input[type="image"]', 'src'),
      'source' => new Tag('source', 'srcset', multipleValues: TRUE, multivalueSeparator: ', '),
      'img' => new Tag('img', 'src'),
      'link' => new Tag('link', 'href'),
      'og:image' => new Tag('meta[property="og:image"]', 'content', forceRelative: TRUE),
      'og:image:url' => new Tag('meta[property="og:image:url"]', 'content', forceRelative: TRUE),
      'twitter:image' => new Tag('meta[name="twitter:image"]', 'content', forceRelative: TRUE),
      'script' => new Tag('script', 'src', assetPath: TRUE),
      'a' => new Tag('a', 'href', sitePrefix: TRUE),
    ];
  }

  /**
   * Gets the tag.
   *
   * @param string $key
   *   The key.
   *
   * @return \Drupal\helfi_proxy\Tag\Tag|null
   *   The tag.
   */
  public static function tag(string $key) : ? Tag {
    $tags = self::all();
    return $tags[$key] ?? NULL;
  }

}
