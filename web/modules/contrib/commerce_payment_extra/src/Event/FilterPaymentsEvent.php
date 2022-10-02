<?php

namespace Drupal\commerce_payment_extra\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for filtering payments.
 *
 * @see \Drupal\commerce_payment_extra\Event\PaymentExtraEvents
 */
abstract class FilterPaymentsEvent extends Event {

  /**
   * The payments.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface[]
   */
  protected $payments;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new FilterCapturablePaymentsEvent object.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface[] $payments
   *   The payments.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function __construct(array $payments, OrderInterface $order) {
    $this->payments = $payments;
    $this->order = $order;
  }

  /**
   * Gets the payments.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface[]
   *   The payments.
   */
  public function getPayments() {
    return $this->payments;
  }

  /**
   * Sets the payments.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface[] $payments
   *   The payments.
   *
   * @return $this
   */
  public function setPayments(array $payments) {
    $this->payments = $payments;
    return $this;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

}
