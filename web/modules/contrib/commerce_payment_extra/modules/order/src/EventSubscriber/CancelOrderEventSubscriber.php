<?php

namespace Drupal\commerce_payment_extra_order\EventSubscriber;

use Drupal\advancedqueue\Job;
use Drupal\commerce_payment_extra\PaymentManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CancelOrderEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The payment manager.
   *
   * @var \Drupal\commerce_payment_extra\PaymentManager
   */
  protected $paymentManager;

  /**
   * The module's config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * CancelOrderEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\commerce_payment_extra\PaymentManager $paymentManager
   *   The payment manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, PaymentManager $paymentManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->paymentManager = $paymentManager;
    $this->config = $configFactory->get('commerce_payment_extra_order.settings');
  }

  /**
   * Queue all voidable payments.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   */
  public function onCancel(WorkflowTransitionEvent $event) {
    if ($this->config->get('cancel_payments_on_order_transition') != TRUE) {
      return;
    }
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    /** @var \Drupal\advancedqueue\Entity\Queue $queue */
    $queue = $this->entityTypeManager->getStorage('advancedqueue_queue')->load('commerce_payment_extra_order');
    $payments = $this->paymentManager->loadVoidablePaymentsByOrder($order);
    foreach ($payments as $payment) {
      // Enqueue payment.
      $job = Job::create('commerce_payment_extra_void', ['payment_id' => $payment->id()]);
      $queue->enqueueJob($job);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.cancel.post_transition' => ['onCancel'],
    ];
    return $events;
  }

}
