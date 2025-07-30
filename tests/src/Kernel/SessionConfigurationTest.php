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
   * The session configuration service.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface|null
   */
  protected ?SessionConfigurationInterface $configuration;

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
  public function setUp(): void {
    parent::setUp();

    $this->configuration = $this->container->get('session_configuration');
    $this->requestStack = $this->container->get('request_stack');
  }

  /**
   * Gets the SUT.
   *
   * @return \Drupal\Core\Session\SessionConfigurationInterface
   *   The SUT.
   */
  private function getSut(): SessionConfigurationInterface {
    return $this->container->get('session_configuration');
  }

  /**
   * Tests session suffix from configuration.
   */
  public function testSessionNameConfig(): void {
    $this->config('helfi_proxy.settings')
      ->set(ProxyManagerInterface::SESSION_SUFFIX, 'testdev')
      ->save();

    $options = $this->configuration->getOptions($this->requestStack->getCurrentRequest());
    $this->assertTrue(str_ends_with($options['name'], 'testdev'));
  }

  /**
   * Tests session suffix from DRUPAL_SESSION_SUFFIX env variable.
   */
  public function testSessionNameEnvVariable(): void {
    putenv('DRUPAL_SESSION_SUFFIX=testlocal');

    $options = $this->configuration->getOptions($this->requestStack->getCurrentRequest());
    $this->assertTrue(str_ends_with($options['name'], 'testlocal'));
  }

  /**
   * Tests session suffix fallback.
   */
  public function testSessionNameFallback(): void {
    $options = $this->configuration->getOptions($this->requestStack->getCurrentRequest());
    $this->assertNotEmpty($this->getCleanHostname());
    $this->assertTrue(str_ends_with($options['name'], $this->getCleanHostname()));
  }

}
