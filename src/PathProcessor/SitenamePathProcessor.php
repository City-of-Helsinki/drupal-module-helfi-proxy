<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * A path processor.
 *
 * Adds the 'site name' prefix to all in/outcoming URLs.
 */
final class SitenamePathProcessor implements OutboundPathProcessorInterface, InboundPathProcessorInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $config;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(LanguageManagerInterface $languageManager, ConfigFactoryInterface $configFactory) {
    $this->languageManager = $languageManager;
    $this->config = $configFactory->get('helfi_proxy.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $parts = explode('/', trim($path, '/'));
    $prefix = array_shift($parts);

    if (in_array($prefix, $this->config->get('prefixes') ?? [])) {
      $path = '/' . implode('/', $parts);
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound(
    $path,
    &$options = [],
    Request $request = NULL,
    BubbleableMetadata $bubbleable_metadata = NULL
  ) : string {
    $language = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL);

    $prefix = $this->config->get('prefixes')[$language->getId()] ?? NULL;

    if ($prefix) {
      $options['prefix'] .= $prefix . '/';
    }

    return $path;
  }

}
