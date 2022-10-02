<?php

namespace Drupal\phone_international\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form with setting for phone international.
 */
class PISettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phone_international_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'phone_international.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('phone_international.settings');
    $form['cdn'] = [
      '#type'          => 'checkbox',
      '#default_value' => $config->get('cdn'),
      '#title'         => $this->t('Enable CDN Libraries'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('phone_international.settings')
      ->set('cdn', $form_state->getValue('cdn'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
