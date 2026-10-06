<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\KernelTests\KernelTestBase;
use Drupal\helfi_proxy\EventSubscriber\AssetRedirectResponseSubscriber;
use Drupal\helfi_proxy\ProxyManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the asset redirect response subscriber.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_proxy')]
class AssetRedirectResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'helfi_proxy',
    'purge',
    'purge_tokens',
    'purge_processor_cron',
    'purge_queuer_coretags',
    'purge_drush',
  ];

  /**
   * Dispatches the response event.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  private function dispatch(Response $response) : Response {
    $event = new ResponseEvent(
      $this->container->get('http_kernel'),
      Request::create('https://www.hel.fi/sites/default/files/file.jpg'),
      HttpKernelInterface::MAIN_REQUEST,
      $response,
    );
    $this->container->get(AssetRedirectResponseSubscriber::class)->onResponse($event);

    return $event->getResponse();
  }

  /**
   * Tests the redirects.
   */
  #[DataProvider('redirectData')]
  public function testRedirect(string $url, string $expected) : void {
    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::ASSET_PATH, 'test-assets')
      ->save();

    $response = $this->dispatch(new RedirectResponse($url));
    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertSame($expected, $response->getTargetUrl());
  }

  /**
   * Data provider for testRedirect().
   *
   * @return array<string, array{string, string}>
   *   The data.
   */
  public static function redirectData() : array {
    $file = '/sites/default/files/styles/thumbnail/public/file.jpg.webp?itok=abc';

    return [
      'absolute' => ["https://www.hel.fi$file", "https://www.hel.fi/test-assets$file"],
      'relative' => [$file, "/test-assets$file"],
      'port and fragment' => ["http://www.hel.fi:8080$file#a", "http://www.hel.fi:8080/test-assets$file#a"],
      'already prefixed' => ["https://www.hel.fi/test-assets$file", "https://www.hel.fi/test-assets$file"],
      'other host' => ["https://example.com$file", "https://example.com$file"],
      'not an asset' => ['https://www.hel.fi/fi/uutiset', 'https://www.hel.fi/fi/uutiset'],
    ];
  }

  /**
   * Tests that the redirects aren't changed without the asset path.
   */
  public function testNoAssetPath() : void {
    $url = 'https://www.hel.fi/sites/default/files/file.jpg';
    $response = $this->dispatch(new TrustedRedirectResponse($url));
    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertSame($url, $response->getTargetUrl());

    $response = $this->dispatch(new Response('content'));
    $this->assertSame('content', $response->getContent());
  }

}
