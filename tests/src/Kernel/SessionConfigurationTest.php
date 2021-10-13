<?php

declare(strict_types = 1);

namespace Drupal\Tests\helfi_proxy;

use Drupal\helfi_proxy\ProxyTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests session configuration.
 *
 * @group helfi_proxy
 */
class SessionConfigurationTest extends KernelTestBase {

  use ProxyTrait;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['helfi_proxy'];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->configuration = $this->container->get('session_configuration');
  }

  /**
   * Tests that session gets prefixed with hostname.
   */
  public function testSessionName() : void {
    /** @var \Symfony\Component\HttpFoundation\RequestStack $requestStack */
    $requestStack = $this->container->get('request_stack');
    $options = $this->configuration->getOptions($requestStack->getCurrentRequest());

    $this->assertNotEmpty($this->getCleanHostname());
    $this->assertStringContainsString($this->getCleanHostname(), $options['name']);
  }

}
