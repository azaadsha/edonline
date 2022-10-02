<?php

namespace Drupal\commerce_payment_extra_order\EventSubscriber;

use Drupal\advancedqueue\Job;
use Drupal\commerce_payment_extra\PaymentManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CompleteOrderEventSubscriber implements EventSubscriberInterface {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\commerce_payment_extra\PaymentManager $paymentManager
   *   The payment manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger, PaymentManager $paymentManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->paymentManager = $paymentManager;
    $this->config = $configFactory->get('commerce_payment_extra_order.settings');
  }

  /**
   * Queue all capturable payments.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   */
  public function onComplete(WorkflowTransitionEvent $event) {
    if ($this->config->get('capture_payments_on_order_transition') != TRUE) {
      return;
    }
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    /** @var \Drupal\advancedqueue\Entity\Queue $queue */
    $queue = $this->entityTypeManager->getStorage('advancedqueue_queue')->load('commerce_payment_extra_order');
    $payments = $this->paymentManager->loadCapturablePaymentsByOrder($order);
    $balance = $order->getBalance();
    foreach ($payments as $payment) {
      // Find the amount.
      $amount = $balance->greaterThan($payment->getAmount()) ? $payment->getAmount() : $balance;

      // Enqueue payment.
      $job = Job::create('commerce_payment_extra_capture', ['payment_id' => $payment->id(), 'amount' => $amount->toArray()]);
      $queue->enqueueJob($job);

      // Get updated balance.
      $balance = $balance->subtract($amount);

      if ($balance->isZero()) {
        return;
      }
    }
    if ($balance->isPositive()) {
      $this->logger->emergency('Insufficient funds for order @order_id. Uncovered balance @balance', [
        '@order_id' => $order->id(),
        '@balance' => $balance->getNumber(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.fulfill.post_transition' => ['onComplete'],
    ];
    return $events;
  }

}
