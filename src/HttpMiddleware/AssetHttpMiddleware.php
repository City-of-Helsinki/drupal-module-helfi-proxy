<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\HttpMiddleware;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\helfi_proxy\HostnameTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Wa72\HtmlPageDom\HtmlPage;

/**
 * A middleware to alter asset urls.
 *
 * @todo This is terrible and we need to achieve the same result some other way.
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
   * Converts attributes to have different hostname.
   *
   * @param \Wa72\HtmlPageDom\HtmlPage $dom
   *   The dom to manipulate.
   *
   * @return $this
   *   The self.
   */
  private function convertAttributes(HtmlPage $dom) : self {
    foreach (
      [
        'source' => 'srcset',
        'img' => 'src',
        'link' => 'href',
        'script' => 'src',
      ] as $tag => $attribute) {
      foreach ($dom->filter(sprintf('%s[%s]', $tag, $attribute)) as $row) {
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
   * Handles ajax responses.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   *
   * @return string|null
   *   The response.
   */
  private function processJson(Response $response) : ? string {
    $content = json_decode($response->getContent(), TRUE);

    $hasChanges = FALSE;

    if (!$content) {
      return NULL;
    }

    foreach ($content as $key => $value) {
      if (!isset($value['data'])) {
        continue;
      }
      $hasChanges = TRUE;

      $dom = new HtmlPage($value['data']);
      $this->convertSvg($dom)
        ->convertAttributes($dom);

      $content[$key]['data'] = $this->format($dom);
    }
    return $hasChanges ? json_encode($content) : NULL;
  }

  /**
   * Inlines all SVG definitions.
   *
   * SVG sprites cannot be sourced from different domain, so instead we
   * parse all SVGs and insert them directly into dom and convert attributes
   * to only include fragments, like /theme/sprite.svg#logo -> #logo.
   *
   * @param \Wa72\HtmlPageDom\HtmlPage $dom
   *   The dom to manipulate.
   *
   * @return $this
   *   The self.
   *
   * @see https://css-tricks.com/svg-sprites-use-better-icon-fonts/
   */
  private function convertSvg(HtmlPage $dom) : self {
    $cache = [];

    // Only match SVGs under theme folders.
    $themePaths = ['/core/themes' => 12, '/themes' => 7];

    foreach ($dom->filter('use') as $row) {
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

        if (!$value) {
          continue;
        }

        $uri = parse_url(DRUPAL_ROOT . $value);

        if (!isset($uri['path'], $uri['fragment'])) {
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

          // Append SVGs before closing body tag, but don't show them since it might have
          // some negative effects.
          $dom->filter('body')->append('<span style="display: none;">' . $content . '</span>');
        }
        $row->setAttribute($attribute, '#' . $uri['fragment']);
      }
    }
    return $this;
  }

  /**
   * Formats the response.
   *
   * @param \Wa72\HtmlPageDom\HtmlPage $dom
   *   The dom.
   *
   * @return string
   *   The formatted response.
   */
  private function format(HtmlPage $dom) : string {
    return $dom->save();
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

    if ($response instanceof JsonResponse) {
      if ($json = $this->processJson($response)) {
        return $response->setContent($json);
      }
      return $response;
    }
    $html = $response->getContent();

    // Skip non-ajax post requests.
    if (!is_string($html) || $request->getMethod() !== 'GET') {
      return $response;
    }
    $dom = new HtmlPage($html);

    $html = $this->convertAttributes($dom)
      ->convertSvg($dom)
      ->format($dom);

    return $response->setContent($html);
  }

}
