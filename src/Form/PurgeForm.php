<?php

declare(strict_types=1);

namespace Drupal\helfi_proxy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Purge everything form.
 */
final class PurgeForm extends FormBase {

  /**
   * The queuer.
   *
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface
   */
  private QueuerInterface $queuer;

  /**
   * The queue service.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  private QueueServiceInterface $queueService;

  /**
   * The purge invalidation service.
   *
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  private InvalidationsServiceInterface $invalidationsService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    $instance = parent::create($container);
    $instance->queuer = $container
      ->get('purge.queuers')
      ->get('helfi_proxy_queue_everything');
    $instance->queueService = $container->get('purge.queue');
    $instance->invalidationsService = $container->get('purge.invalidation.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'helfi_proxy_purge';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {
    $form['purge'] = [
      '#type' => 'submit',
      '#value' => $this->t('Purge'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {
    // Queue everything.
    $invalidations = [$this->invalidationsService->get('everything')];
    $this->queueService->add($this->queuer, $invalidations);
    $this->messenger()->addStatus($this->t('Added everything to queue!'));
  }

}
