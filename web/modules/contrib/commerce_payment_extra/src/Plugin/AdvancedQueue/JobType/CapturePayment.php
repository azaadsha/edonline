<?php

namespace Drupal\commerce_payment_extra\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsVoidsInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Void payment job.
 *
 * @AdvancedQueueJobType(
 *   id = "commerce_payment_extra_capture",
 *   label = @Translation("Capture payment"),
 *   max_retries = 10,
 *   retry_delay = 3600,
 * )
 */
class CapturePayment extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * CapturePayment constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.channel.commerce_payment_extra')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function process(Job $job) {
    $payload = $job->getPayload();
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entityTypeManager->getStorage('commerce_payment')->load($payload['payment_id']);
    $plugin = $payment->getPaymentGateway()->getPlugin();
    if (!$plugin instanceof SupportsAuthorizationsInterface) {
      return JobResult::failure($this->t('Payment gateway does not implement %s', ['%s' => SupportsVoidsInterface::class]), 0);
    }
    // If price is missing assume payment must be captured on full amount.
    if (is_array($payload['amount'])) {
      $payload['amount'] = Price::fromArray($payload['amount']);
    }
    elseif (!is_object($payload['price']) || !$payload['amount'] instanceof Price) {
      $payload['amount'] = $payment->getAmount();
    }

    try {
      $plugin->capturePayment($payment, $payload['amount']);
      return JobResult::success($this->t('Payment has been captured.'));
    }
    catch (HardDeclineException $e) {
      return JobResult::failure($this->t('Payment capture failed (hard decline): @message', ['@message' => $e->getMessage()]), 0);
    }
    catch (PaymentGatewayException $e) {
      return JobResult::failure($this->t('Payment capture failed: @message', ['@message' => $e->getMessage()]), 0);
    }
    catch (\Exception $e) {
      return JobResult::failure($this->t('Unknown error: @message', ['@message' => $e->getMessage()]));
    }
  }
}
