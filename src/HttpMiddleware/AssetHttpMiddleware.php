<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\HttpMiddleware;

use Drupal\helfi_proxy\ProxyManager;
use Drupal\helfi_proxy\Tag\Tags;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * A middleware to alter asset urls.
 *
 * @todo This is terrible and we need to achieve the same result some other way.
 */
final class AssetHttpMiddleware implements HttpKernelInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The http kernel.
   * @param \Drupal\helfi_proxy\ProxyManager $proxyManager
   *   The proxy manager.
   */
  public function __construct(
    private HttpKernelInterface $httpKernel,
    private ProxyManager $proxyManager
  ) {
  }

  /**
   * Manipulates the given attributes to have correct values.
   *
   * @param string $html
   *   The html to manipulate.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The manipulated response.
   */
  private function processHtml(string $html, Request $request) : string {
    return $this->manipulate($html, function (\DOMDocument $dom) use ($request) {
      $xpath = new \DOMXPath($dom);
      foreach (Tags::all() as $map) {
        foreach ($xpath->query($map->tagSelector) as $row) {
          $originalValue = $row->getAttribute($map->attribute);

          if (!$value = $this->proxyManager->getAttributeValue($request, $map, $originalValue)) {
            continue;
          }
          $row->setAttribute($map->attribute, $value);
        }
      }
    });
  }

  /**
   * Manipulates given html.
   *
   * @param string $html
   *   The html to manipulate.
   * @param callable $callback
   *   The callback to manipulate dom.
   *
   * @return string
   *   The manipulated dom.
   */
  private function manipulate(string $html, callable $callback) : string {
    $dom = new \DOMDocument();
    $previousXmlErrorBehavior = libxml_use_internal_errors(TRUE);
    $encoding = '<?xml encoding="utf-8" ?>';

    @$dom->loadHTML(
      $encoding . $html,
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    $dom->encoding = 'UTF-8';

    $callback($dom);

    $result = trim($dom->saveHTML());
    libxml_use_internal_errors($previousXmlErrorBehavior);

    return str_replace($encoding, '', $result);
  }

  /**
   * Handles ajax responses.
   *
   * @param string $content
   *   The original content to manipulate.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return string|null
   *   The response.
   */
  private function processJson(string $content, Request $request) : ? string {
    $content = json_decode($content, TRUE);

    $hasChanges = FALSE;

    if (!$content) {
      return NULL;
    }

    foreach ($content as $key => $value) {
      if (!isset($value['data'])) {
        continue;
      }
      $hasChanges = TRUE;

      $content[$key]['data'] = $this->processHtml($value['data'], $request);
    }
    return $hasChanges ? json_encode($content) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(
    Request $request,
    $type = self::MASTER_REQUEST,
    $catch = TRUE
  ) : Response {
    $response = $this->httpKernel->handle($request, $type, $catch);

    // Nothing to do if asset path is not configured.
    if (!$this->proxyManager->getAssetPath()) {
      return $response;
    }
    $content = $response->getContent();

    if (!is_string($content)) {
      return $response;
    }

    if ($response instanceof JsonResponse) {
      if ($json = $this->processJson($content, $request)) {
        return $response->setContent($json);
      }
      return $response;
    }
    $content = $this->processHtml($content, $request);

    return $response->setContent($content);
  }

}
