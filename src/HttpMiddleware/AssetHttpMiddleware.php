<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\HttpMiddleware;

use Drupal\helfi_proxy\ProxyManager;
use Drupal\helfi_proxy\ProxyManagerInterface;
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

    if (!$content || !is_array($content)) {
      return NULL;
    }

    foreach ($content as $key => $value) {
      if (!isset($value['data']) || !is_string($value['data'])) {
        continue;
      }
      $hasChanges = TRUE;

      $content[$key]['data'] = $this->proxyManager
        ->processHtml($value['data'], $request);
    }
    return $hasChanges ? json_encode($content) : NULL;
  }

  /**
   * Checks if the given response is json.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   *
   * @return bool
   *   TRUE if response is JSON.
   */
  private function isJsonResponse(Response $response) : bool {
    if ($response instanceof JsonResponse) {
      return TRUE;
    }

    return $response->headers->get('content-type') === 'application/json';
  }

  /**
   * Checks for xml type mainly for sitemap.
   *
   * @param Response $response
   *   The response.
   *
   * @return bool
   *   TRUE if response is XML
   */
  private function isXmlResponse(Response $response){
    $contentTypes = [
      'application/xml',
      'application/xml; charset=utf-8'
    ];
    return in_array($response->headers->get('content-type'), $contentTypes);
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
    if (!$this->proxyManager->isConfigured(ProxyManagerInterface::ASSET_PATH)) {
      return $response;
    }
    $content = $response->getContent();

    if (!is_string($content)) {
      return $response;
    }

    if ($this->isJsonResponse($response)) {
      if ($json = $this->processJson($content, $request)) {
        return $response->setContent($json);
      }
      return $response;
    }

    if ($this->isXmlResponse($response)) {
      return $response;
    }

    $content = $this->proxyManager
      ->processHtml($content, $request);

    return $response->setContent($content);
  }

}
