<?php

declare(strict_types = 1);

namespace Drupal\helfi_proxy\HttpMiddleware;

use Drupal\helfi_proxy\HostnameTrait;
use Symfony\Component\HttpFoundation\Request;
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
   * Constructs a new instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The http kernel.
   */
  public function __construct(HttpKernelInterface $httpKernel) {
    $this->httpKernel = $httpKernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(
    Request $request,
    $tag = self::MASTER_REQUEST,
    $catch = TRUE
  ) {
    $response = $this->httpKernel->handle($request, $tag, $catch);

    $dom = new \DOMDocument();
    $dom->loadHTML($response->getContent());

    foreach (
      [
        'img' => 'src',
        'link' => 'href',
        'script' => 'src',
      ] as $tag => $attribute) {
      foreach ($dom->getElementsByTagName($tag) as $row) {
        $value = $row->getAttribute($attribute);

        if (substr($value, 0, 4) === 'http') {
          continue;
        }
        $value = sprintf('%s%s', $this->getHostname(), $value);
        $row->setAttribute($attribute, $value);
      }

    }
    $response->setContent($dom->saveHTML());
    return $response;
  }

}
