<?php

namespace Drupal\commerce_payment_extra_order\Form;

use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Commerce Payment Extra settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager) {
    $this->setConfigFactory($config_factory);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_payment_extra_order_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_payment_extra_order.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['enable_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable cron'),
      '#description' => $this->t('Run order placing script in cron'),
      '#default_value' => $this->config('commerce_payment_extra_order.settings')->get('enable_cron'),
    ];

    $form['authorized_auto_place_min_threshold'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Min threshold'),
      '#description' => $this->t('Minimum time from when order was changed to be picked up by auto-placing cron. In seconds.'),
      '#default_value' => $this->config('commerce_payment_extra_order.settings')->get('authorized_auto_place_min_threshold') ?: 3600,
    ];
    $form['authorized_auto_place_max_threshold'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max threshold'),
      '#description' => $this->t('Maximum time from when order was changed to be picked up by auto-placing cron. In seconds.'),
      '#default_value' => $this->config('commerce_payment_extra_order.settings')->get('authorized_auto_place_max_threshold') ?: 2592000,
    ];
    $gateways = $this->entityTypeManager->getStorage('commerce_payment_gateway')->loadMultiple();
    $form['supported_payment_gateways'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Supported payment gateways'),
      '#description' => $this->t('Only payments using one of selected payment gateways will be considered as auto-placeable.'),
      '#default_value' => $this->config('commerce_payment_extra_order.settings')->get('supported_payment_gateways') ?: [],
      '#options' => array_map(function (PaymentGatewayInterface $value) {
        return $value->label();
      }, $gateways),
    ];

    $form['capture_payments_on_order_transition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capture payment(s) on order transition'),
      '#description' => $this->t('When this option is checked, system will perform payments capture on the order total on all applicable payments.'),
      '#default_value' => $this->config('commerce_payment_extra_order.settings')->get('capture_payments_on_order_transition') ?: FALSE,
    ];
    $form['cancel_payments_on_order_transition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cancel payment(s) on order transition'),
      '#description' => $this->t('When this option is checked, system will void all applicable payments.'),
      '#default_value' => $this->config('commerce_payment_extra_order.settings')->get('cancel_payments_on_order_transition') ?: FALSE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('commerce_payment_extra_order.settings')
      ->set('enable_cron', $form_state->getValue('enable_cron'))
      ->set('authorized_auto_place_min_threshold', $form_state->getValue('authorized_auto_place_min_threshold'))
      ->set('authorized_auto_place_max_threshold', $form_state->getValue('authorized_auto_place_max_threshold'))
      ->set('supported_payment_gateways', $form_state->getValue('supported_payment_gateways'))
      ->set('capture_payments_on_order_transition', $form_state->getValue('capture_payments_on_order_transition'))
      ->set('cancel_payments_on_order_transition', $form_state->getValue('cancel_payments_on_order_transition'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
