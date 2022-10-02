<?php

namespace Drupal\commerce_payment_extra;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment_extra\Event\FilterCapturablePaymentsEvent;
use Drupal\commerce_payment_extra\Event\FilterVoidablePaymentsEvent;
use Drupal\commerce_payment_extra\Event\PaymentExtraEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * PaymentManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger, EventDispatcherInterface $eventDispatcher) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Loads all Payments assigned to Order that can be Captured.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface[]
   */
  public function loadCapturablePaymentsByOrder(OrderInterface $order) {
    $payments = $this->entityTypeManager->getStorage('commerce_payment')->loadMultipleByOrder($order);
    $event = new FilterCapturablePaymentsEvent($payments, $order);
    $this->eventDispatcher->dispatch(PaymentExtraEvents::FILTER_CAPTURABLE_PAYMENTS, $event);
    return $event->getPayments();
  }

  /**
   * Loads all Payments assigned to Order that can be Voided.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface[]
   */
  public function loadVoidablePaymentsByOrder(OrderInterface $order) {
    $payments = $this->entityTypeManager->getStorage('commerce_payment')->loadMultipleByOrder($order);
    $event = new FilterVoidablePaymentsEvent($payments, $order);
    $this->eventDispatcher->dispatch(PaymentExtraEvents::FILTER_VOIDABLE_PAYMENTS, $event);
    return $event->getPayments();
  }

}
