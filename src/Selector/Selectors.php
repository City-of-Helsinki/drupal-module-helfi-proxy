<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Selector;

/**
 * Repository to fetch Tags.
 */
final class Selectors {

  /**
   * Gets all tags.
   *
   * @return \Drupal\helfi_proxy\Selector\Selector[]
   *   The tags.
   */
  public static function all() : array {
    return [
      'input' => new Selector('//input[@type="image"]', 'src'),
      'source' => new Selector('//source', 'srcset', multipleValues: TRUE, multivalueSeparator: ', '),
      'img' => new Selector('//img', 'src'),
      'link' => new Selector('//link', 'href'),
      'og:image' => new Selector('//meta[@property="og:image"]', 'content', alwaysAbsolute: TRUE),
      'og:image:url' => new Selector('//meta[@property="og:image:url"]', 'content', alwaysAbsolute: TRUE),
      'twitter:image' => new Selector('//meta[@name="twitter:image"]', 'content', alwaysAbsolute: TRUE),
      'script' => new Selector('//script', 'src'),
      'a' => new Selector('//a', 'href', sitePrefix: TRUE),
      'use' => new Selector('//use', 'href'),
      'use-xhref' => new Selector('//use', 'xlink:href'),
    ];
  }

  /**
   * Gets the tag.
   *
   * @param string $key
   *   The key.
   *
   * @return \Drupal\helfi_proxy\Selector\Selector|null
   *   The tag.
   */
  public static function get(string $key) : ? Selector {
    $selectors = self::all();
    return $selectors[$key] ?? NULL;
  }

}
