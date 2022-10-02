<?php

namespace Drupal\commerce_payment_extra\Event;

final class PaymentExtraEvents {

  /**
   * Filters capturable payments.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment_extra\Event\FilterCapturablePaymentsEvent
   */
  const FILTER_CAPTURABLE_PAYMENTS = 'commerce_payment_extra.filter_capturable_payments';

  /**
   * Filters voidable payments.
   *
   * @Event
   *
   * @see \Drupal\commerce_payment_extra\Event\FilterVoidablePaymentsEvent
   */
  const FILTER_VOIDABLE_PAYMENTS = 'commerce_payment_extra.filter_voidable_payments';

}
