<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\Selector;

/**
 * A trait to provide default selectors.
 */
trait SelectorRepositoryTrait {

  /**
   * Gets the default selectors.
   *
   * @return array
   *   The default selectors.
   */
  protected function getDefaultSelectors() : array {
    // @todo Refactor these to PHP attributes.
    return [
      new AttributeSelector('//input[@type="image"]', 'src'),
      new MultiValueAttributeSelector('//source', 'srcset', ', '),
      new AttributeSelector('//img', 'src'),
      new AttributeSelector('//link', 'href'),
      new AbsoluteUriAttributeSelector('//meta[@property="og:image"]', 'content'),
      new AbsoluteUriAttributeSelector('//meta[@property="og:image:url"]', 'content'),
      new AbsoluteUriAttributeSelector('//meta[@name="twitter:image"]', 'content'),
      new AttributeSelector('//script', 'src'),
      new AttributeSelector('//use', 'href'),
      new AttributeSelector('//use', 'xlink:href'),
    ];
  }

}
