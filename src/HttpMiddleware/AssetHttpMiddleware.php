<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\HttpMiddleware;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\helfi_proxy\HostnameTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * A middleware to alter asset urls.
 */
final class AssetHttpMiddleware implements HttpKernelInterface {

  use HostnameTrait;

  /**
   * The http kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  private HttpKernelInterface $httpKernel;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private LoggerChannelInterface $logger;

  /**
   * Constructs a new instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The http kernel.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannel
   *   The logger.
   */
  public function __construct(HttpKernelInterface $httpKernel, LoggerChannelFactoryInterface $loggerChannel) {
    $this->httpKernel = $httpKernel;
    $this->logger = $loggerChannel->get('helfi_proxy');
  }

  /**
   * Gets a dom document object for given html.
   *
   * @param string $html
   *   The html to load.
   *
   * @return \DOMDocument
   *   The dom document.
   */
  private function getDocument(string $html) : \DOMDocument {
    libxml_use_internal_errors(TRUE);
    $dom = new \DOMDocument();

    if (!$dom->loadHTML($html)) {
      foreach (libxml_get_errors() as $error) {
        $this->logger->debug($error->message);
      }

      libxml_clear_errors();
    }

    return $dom;
  }

  /**
   * Converts attributes to have different hostname.
   *
   * @param \DOMDocument $dom
   *   The dom to manipulate.
   *
   * @return $this
   *   The self.
   */
  private function convertAttributes(\DOMDocument $dom) : self {
    foreach (
      [
        'source' => 'srcset',
        'img' => 'src',
        'link' => 'href',
        'script' => 'src',
      ] as $tag => $attribute) {
      foreach ($dom->getElementsByTagName($tag) as $row) {
        $value = $row->getAttribute($attribute);

        if (!$value || substr($value, 0, 4) === 'http' || substr($value, 0, 2) === '//') {
          continue;
        }
        $value = sprintf('//%s%s', $this->getHostname(), $value);
        $row->setAttribute($attribute, $value);
      }
    }
    return $this;
  }

  /**
   * Inlines all SVG definitions.
   *
   * SVG sprites cannot be sourced from different domain, so instead we
   * parse all SVGs and insert them directly into dom and convert attributes
   * to only include fragments, like /theme/sprite.svg#logo -> #logo.
   *
   * @param \DOMDocument $dom
   *   The dom to manipulate.
   *
   * @see https://css-tricks.com/svg-sprites-use-better-icon-fonts/
   *
   * @return $this
   *   The self.
   */
  private function convertSvg(\DOMDocument $dom) : self {
    $cache = [];

    // Only match SVGs under theme folders.
    $themePaths = ['/core/themes' => 12, '/themes' => 7];

    foreach ($dom->getElementsByTagName('use') as $row) {
      foreach (['href', 'xlink:href'] as $attribute) {
        $value = NULL;

        // Skip non-theme SVGs.
        foreach ($themePaths as $path => $length) {
          $attributeValue = $row->getAttribute($attribute);

          if (substr($attributeValue, 0, $length) === $path) {
            $value = $attributeValue;
            break;
          }
        }

        $uri = parse_url(DRUPAL_ROOT . $value);

        if (!$value || !isset($uri['path'], $uri['fragment'])) {
          $this->logger
            ->critical(
              sprintf('Found a SVG that cannot be inlined. Please fix it manually: %s', $value)
            );
          continue;
        }
        $path = $uri['path'];

        if (!isset($cache[$path])) {
          $cache[$path] = TRUE;

          if (!$content = file_get_contents($path)) {
            $this->logger
              ->critical(
                sprintf('Found a SVG that cannot be inlined. Please fix it manually: %s', $value)
              );
            continue;
          }

          $fragment = $dom->createDocumentFragment();
          // Don't show SVG in dom since it might have some negative effects.
          $fragment->appendXML('<span style="display: none;">' . $content . '</span>');
          $dom->documentElement->appendChild($fragment);
        }
        $row->setAttribute($attribute, '#' . $uri['fragment']);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(
    Request $request,
    $tag = self::MASTER_REQUEST,
    $catch = TRUE
  ) : Response {
    $response = $this->httpKernel->handle($request, $tag, $catch);

    $html = $response->getContent();

    if (!is_string($html)) {
      return $response;
    }
    $dom = $this->getDocument($html);

    $this->convertAttributes($dom)
      ->convertSvg($dom);

    $response->setContent($dom->saveHTML());
    return $response;
  }

}
