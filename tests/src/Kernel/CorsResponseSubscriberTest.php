<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests CORS response subscriber.
 *
 * @group helfi_proxy
 */
class CorsResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'helfi_proxy',
  ];

  /**
   * Make sure cors headers are set properly.
   *
   * @dataProvider corsTestData
   */
  public function testCors(mixed $domain, bool $expected) : void {
    $request = Request::create('/', server: [
      'HTTP_HOST' => 'localhost:8888',
    ]);
    $request->headers->set('Origin', $domain);
    $http_kernel = $this->container->get('http_kernel');
    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $response = $http_kernel->handle($request);
    $this->assertEquals($expected, $response->headers->has('Access-Control-Allow-Origin'));
  }

  /**
   * Data provider for testCors().
   *
   * @return array[]
   *   The data.
   */
  public function corsTestData() : array {
    return [
      ['www.hel.fi', TRUE],
      ['hel.fi', TRUE],
      ['docker.so', TRUE],
      ['helfi-kymp.docker.so', TRUE],
      ['testdocker.so', FALSE],
      [NULL, FALSE],
    ];
  }

}
