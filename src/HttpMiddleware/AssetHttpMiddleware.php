<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\HttpMiddleware;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\helfi_proxy\ProxyManager;
use Drupal\helfi_proxy\Tag\Tags;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Wa72\HtmlPageDom\HtmlPage;
use Wa72\HtmlPageDom\HtmlPageCrawler;

/**
 * A middleware to alter asset urls.
 *
 * @todo This is terrible and we need to achieve the same result some other way.
 */
final class AssetHttpMiddleware implements HttpKernelInterface {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private LoggerChannelInterface $logger;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  private ?Request $request;

  /**
   * Constructs a new instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The http kernel.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannel
   *   The logger.
   * @param \Drupal\helfi_proxy\ProxyManager $proxyManager
   *   The proxy manager.
   */
  public function __construct(
    private HttpKernelInterface $httpKernel,
    LoggerChannelFactoryInterface $loggerChannel,
    private ProxyManager $proxyManager
  ) {
    $this->logger = $loggerChannel->get('helfi_proxy');
  }

  /**
   * Converts attributes to have different hostname.
   *
   * @param \Wa72\HtmlPageDom\HtmlPage|\Wa72\HtmlPageDom\HtmlPageCrawler $dom
   *   The dom to manipulate.
   *
   * @return $this
   *   The self.
   */
  private function convertAttributes(HtmlPage|HtmlPageCrawler $dom) : self {
    foreach (Tags::all() as $map) {
      foreach ($dom->filter(sprintf('%s[%s]', $map->tagSelector, $map->attribute)) as $row) {
        $originalValue = $row->getAttribute($map->attribute);

        if (!$value = $this->proxyManager->getAttributeValue($this->request, $map, $originalValue)) {
          continue;
        }
        $row->setAttribute($map->attribute, $value);
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

      $dom = new HtmlPageCrawler($value['data']);
      $this->convertSvg($dom)
        ->convertAttributes($dom);
      $content[$key]['data'] = $dom->saveHTML();
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
   * @param \Wa72\HtmlPageDom\HtmlPage|\Wa72\HtmlPageDom\HtmlPageCrawler $dom
   *   The dom to manipulate.
   *
   * @return $this
   *   The self.
   *
   * @see https://css-tricks.com/svg-sprites-use-better-icon-fonts/
   */
  private function convertSvg(HtmlPage|HtmlPageCrawler $dom) : self {
    $cache = [];

    // Only match SVGs under theme folders.
    $themePaths = ['/core/themes', '/themes', '/core/misc'];

    foreach ($dom->filter('use') as $row) {
      foreach (['href', 'xlink:href'] as $attribute) {
        $value = NULL;

        // Skip non-theme SVGs.
        foreach ($themePaths as $path) {
          $attributeValue = $row->getAttribute($attribute);

          if (str_starts_with($attributeValue, $path)) {
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

          // Append SVGs before closing body tag, but don't show them since
          // it might have some negative effects.
          $dom->filter('body')->append('<span style="display: none;">' . $content . '</span>');
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
    $type = self::MASTER_REQUEST,
    $catch = TRUE
  ) : Response {
    $this->request = $request;
    $response = $this->httpKernel->handle($request, $type, $catch);

    if ($response instanceof JsonResponse) {
      if ($json = $this->processJson($response)) {
        return $response->setContent($json);
      }
      return $response;
    }
    $html = $response->getContent();

    if (!is_string($html)) {
      return $response;
    }
    $dom = new HtmlPage($html);

    $this->convertAttributes($dom)
      ->convertSvg($dom);
    $html = $dom->save();

    return $response->setContent($html);
  }

}
