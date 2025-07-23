<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_proxy\Kernel;

use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\helfi_proxy\ProxyManagerInterface;
use Drupal\helfi_proxy\ProxyTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests session configuration.
 *
 * @group helfi_proxy
 */
class SessionConfigurationTest extends KernelTestBase {

  use ProxyTrait;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|null
   */
  protected ?RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_proxy',
    'path_alias',
    'purge',
    'purge_tokens',
    'purge_processor_cron',
    'purge_queuer_coretags',
    'purge_drush',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->requestStack = $this->container->get('request_stack');
  }

  /**
   * Gets the SUT.
   *
   * @return \Drupal\Core\Session\SessionConfigurationInterface
   *   The SUT.
   */
  private function getSut() : SessionConfigurationInterface {
    return $this->container->get('session_configuration');
  }

  /**
   * Tests session suffix from configuration.
   */
  public function testSessionNameConfig() : void {
    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::SESSION_SUFFIX, 'testdev')
      ->save();
    $options = $this->getSut()->getOptions($this->requestStack->getCurrentRequest());

    $this->assertEquals('testdev', $options['name_suffix']);
  }

  /**
   * Tests session suffix from DRUPAL_SESSION_SUFFIX env variable.
   */
  public function testSessionNameEnvVariable() : void {
    putenv('DRUPAL_SESSION_SUFFIX=testlocal');
    $options = $this->getSut()->getOptions($this->requestStack->getCurrentRequest());

    $this->assertEquals('testlocal', $options['name_suffix']);
  }

  /**
   * Tests session suffix fallback.
   */
  public function testSessionNameFallback() : void {
    $options = $this->getSut()->getOptions($this->requestStack->getCurrentRequest());

    $this->assertNotEmpty($this->getCleanHostname());
    $this->assertEquals($this->getCleanHostname(), $options['name_suffix']);
  }

}
