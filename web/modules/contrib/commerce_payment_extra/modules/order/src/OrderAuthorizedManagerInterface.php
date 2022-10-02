<?php

namespace Drupal\commerce_payment_extra_order;

/**
 * Manages orders that are authorized in full.
 */
interface OrderAuthorizedManagerInterface {

  /**
   * Process all orders that were authorized in full but never placed. This can
   * happened for multiple reasons - one of them being that user paid with an
   * app and never returned back to the website. Commerce has a code to
   * automatically place orders that were paid in full (captured) but not for
   * the authorized ones which is where this method becomes helpful.
   */
  public function processOrders();

}
