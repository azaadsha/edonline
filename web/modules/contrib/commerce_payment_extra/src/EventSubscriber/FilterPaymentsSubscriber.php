<?php

namespace Drupal\commerce_payment_extra\EventSubscriber;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsVoidsInterface;
use Drupal\commerce_payment_extra\Event\FilterCapturablePaymentsEvent;
use Drupal\commerce_payment_extra\Event\FilterVoidablePaymentsEvent;
use Drupal\commerce_payment_extra\Event\PaymentExtraEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FilterPaymentsSubscriber implements EventSubscriberInterface {

  /**
   * @param \Drupal\commerce_payment_extra\Event\FilterCapturablePaymentsEvent $event
   */
  public function filterCapturable(FilterCapturablePaymentsEvent $event) {
    $payments = [];
    foreach ($event->getPayments() as $payment) {
      $plugin = $payment->getPaymentGateway()->getPlugin();
      if (!$plugin instanceof SupportsAuthorizationsInterface) {
        continue;
      }
      if (!$plugin->canCapturePayment($payment)) {
        continue;
      }
      $payments[] = $payment;
    }
    $event->setPayments($payments);
  }

  /**
   * @param \Drupal\commerce_payment_extra\Event\FilterVoidablePaymentsEvent $event
   */
  public function filterVoidable(FilterVoidablePaymentsEvent $event) {
    $payments = [];
    foreach ($event->getPayments() as $payment) {
      $plugin = $payment->getPaymentGateway()->getPlugin();
      if (!$plugin instanceof SupportsVoidsInterface) {
        continue;
      }
      if (!$plugin->canVoidPayment($payment)) {
        continue;
      }
      $payments[] = $payment;
    }
    $event->setPayments($payments);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      PaymentExtraEvents::FILTER_CAPTURABLE_PAYMENTS => ['filterCapturable'],
      PaymentExtraEvents::FILTER_VOIDABLE_PAYMENTS => ['filterVoidable'],
    ];
    return $events;
  }
}
