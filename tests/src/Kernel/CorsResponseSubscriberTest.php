<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests CORS response subscriber.
 */
#[RunTestsInSeparateProcesses]
#[Group('helfi_proxy')]
class CorsResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path_alias',
    'helfi_proxy',
    'purge',
    'purge_tokens',
    'purge_queuer_coretags',
    'purge_drush',
  ];

  /**
   * Make sure cors headers are set properly.
   */
  #[DataProvider(methodName: 'corsTestData')]
  public function testCors(?string $origin, bool $expected) : void {
    $request = Request::create('/', server: [
      'HTTP_HOST' => 'localhost:8888',
    ]);
    $request->headers->set('Origin', $origin);
    $http_kernel = $this->container->get('http_kernel');
    /** @var \Symfony\Component\HttpFoundation\Response $response */
    $response = $http_kernel->handle($request);
    $this->assertEquals($expected, $response->headers->has('Access-Control-Allow-Origin'));

    if ($expected) {
      $this->assertEquals($origin, $response->headers->get('Access-Control-Allow-Origin'));
    }
    // Responses must vary by origin, even when the origin is not allowed.
    $this->assertContains('Origin', $response->getVary());
  }

  /**
   * Data provider for testCors().
   *
   * @return array<mixed>
   *   The data.
   */
  public static function corsTestData() : array {
    return [
      ['https://www.hel.fi', TRUE],
      ['https://hel.fi', TRUE],
      ['https://WWW.HEL.FI', TRUE],
      ['https://www.hel.fi:443', TRUE],
      ['http://www.hel.fi', TRUE],
      ['https://www.test.hel.ninja', TRUE],
      ['https://docker.so', TRUE],
      ['https://helfi-kymp.docker.so', TRUE],
      ['https://testdocker.so', FALSE],
      ['https://evilhel.fi', FALSE],
      ['https://www.hel.fi.example.com', FALSE],
      ['www.hel.fi', FALSE],
      ['null', FALSE],
      [NULL, FALSE],
    ];
  }

}
