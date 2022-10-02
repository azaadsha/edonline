<?php

namespace Drupal\commerce_payment_extra_order;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment_extra\PaymentManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Psr\Log\LoggerInterface;

class OrderAuthorizedManager implements OrderAuthorizedManagerInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * @var \Drupal\commerce_payment_extra\PaymentManager
   */
  protected $paymentManager;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * OrderAuthorizedManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Component\Datetime\TimeInterface $time
   * @param \Drupal\commerce_payment_extra\PaymentManager $paymentManager
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, TimeInterface $time, PaymentManager $paymentManager, LoggerInterface $logger, Connection $database, LockBackendInterface $lock) {
    $this->config = $configFactory->get('commerce_payment_extra_order.settings');
    $this->entityTypeManager = $entityTypeManager;
    $this->time = $time;
    $this->paymentManager = $paymentManager;
    $this->logger = $logger;
    $this->database = $database;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public function processOrders() {
    $lock = 'commerce_payment_extra_order_process_authorized_orders';
    // Try to acquire a lock.
    if (!$this->lock->acquire($lock, 900.0)) {
      // Process is still running.
      $this->logger->warning('Attempting to re-run @lock while it is already running.', [
        '@lock' => $lock,
      ]);
      return;
    }
    $storage = $this->entityTypeManager->getStorage('commerce_order');
    $order_ids = $this->getOrderIds();
    if (empty($order_ids)) {
      $this->logger->debug('No orders found matching fulfilment criteria.');
      return;
    }
    foreach ($order_ids as $order_id) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
      $order = $storage->loadUnchanged($order_id);
      try {
        $this->processOrder($order);
      }
      catch (\Throwable $e) {
        $this->logger->error('Caught exception trying to process order id @order_id: @msg. Stack trace: @trace', [
          '@msg' => $e->getMessage(),
          '@trace' => $e->getTraceAsString(),
          '@order_id' => $order_id,
        ]);
      }
    }
    $this->lock->release($lock);
  }

  /**
   * Return list of order ids that may match fulfilment criteria.
   *
   * The actual check will be done later; this just limits number of orders to
   * orders changed within timeframe, that are locked and draft, and have at
   * least one payment entity attached to it.
   *
   * @return int[]
   */
  protected function getOrderIds() {

    $query = $this->database->select('commerce_order', 'co');
    $query->innerJoin('commerce_payment', 'cp', 'co.order_id = cp.order_id');
    $query->condition('co.state', 'draft');
    $query->condition('co.locked', 1);
    $query->condition('co.changed', $this->time->getRequestTime() - ($this->config->get('authorized_auto_place_min_threshold') ?: 3600), '<');
    $query->condition('co.changed', $this->time->getRequestTime() - ($this->config->get('authorized_auto_place_max_threshold') ?: 2592000), '>');
    $query->groupBy('co.order_id');
    $query->addField('co', 'order_id');
    $ids = $query->execute()->fetchCol();
    return $ids;
  }

  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   */
  protected function processOrder(OrderInterface $order) {
    if ($order->getState()->getId() != 'draft') {
      $this->logger->warning('The order @order_id has already been placed', [
        '@order_id' => $order->id(),
      ]);
      return;
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $order->get('payment_gateway')->entity;
    if (!$payment_gateway) {
      $this->logger->emergency('The payment gateway is unknown for order @order_id', [
        '@order_id' => $order->id(),
      ]);
      return;
    }

    $balance = $order->getBalance();
    foreach ($payments = $this->paymentManager->loadCapturablePaymentsByOrder($order) as $payment) {
      if (!in_array($payment->getPaymentGatewayId(), $this->config->get('supported_payment_gateways') ?: [])) {
        continue;
      }
      $balance = $balance->subtract($payment->getAmount());
    }
    if (empty($payments)) {
      $this->logger->debug('Order @order_id is locked and in draft state but no payments were found to cover it.', [
        '@order_id' => $order->id(),
      ]);
      return;
    }
    elseif ($balance->isPositive()) {
      $this->logger->emergency('Insufficient funds for order @order_id. Uncovered balance @balance.', [
        '@order_id' => $order->id(),
        '@balance' => $balance->getNumber(),
      ]);
      return;
    }

    $order->getState()->applyTransitionById('place');
    // A placed order should never be locked.
    $order->unlock();
    $order->save();
    $this->logger->notice('The order @order_id has been placed automatically', [
      '@order_id' => $order->id(),
    ]);
  }
}
