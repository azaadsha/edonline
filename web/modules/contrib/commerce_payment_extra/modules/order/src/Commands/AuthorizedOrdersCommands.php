<?php

namespace Drupal\commerce_payment_extra_order\Commands;

use Drupal\commerce_payment_extra_order\OrderAuthorizedManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Class AuthorizedOrdersCommands.
 *
 * @package Drupal\commerce_payment_extra_order
 */
class AuthorizedOrdersCommands extends DrushCommands {

  /**
   * The manager.
   *
   * @var \Drupal\commerce_payment_extra_order\OrderAuthorizedManagerInterface
   */
  protected $manager;

  /**
   * AuthorizedOrdersCommands constructor.
   *
   * @param \Drupal\commerce_payment_extra_order\OrderAuthorizedManagerInterface $manager
   *   The manager.
   */
  public function __construct(OrderAuthorizedManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * Place all orders that have been authorized in full but never placed. This
   * can happen if user never returned to the website after completing the
   * payment.
   *
   * @command commerce-payment-extra-order:place-authorized-orders
   * @aliases cpeo:pao
   */
  public function processOrders() {
    $this->manager->processOrders();
  }

}
