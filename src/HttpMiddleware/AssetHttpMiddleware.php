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
    $response->setContent($dom->saveHTML());
    return $response;
  }

}
