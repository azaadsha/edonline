<?php

namespace Drupal\phone_international\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;

/**
 * Plugin implementation of the 'phone_international_widget' widget.
 *
 * @FieldWidget(
 *   id = "phone_international_widget",
 *   module = "phone_international",
 *   label = @Translation("Text field"),
 *   field_types = {
 *     "phone_international"
 *   }
 * )
 */
class PhoneInternationalDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'initial_country' => 'PT',
      'geolocation' => FALSE,
      'exclude_countries' => [],
      'preferred_countries' => ['PT'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'phone_international',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#country' => $this->getSetting('initial_country'),
      '#geolocation' => $this->getSetting('geolocation') ? 1 : 0,
      '#exclude_countries' => $this->getSetting('exclude_countries'),
      '#preferred_countries' => $this->getSetting('preferred_countries'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['geolocation'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Geolocation'),
      '#default_value' => $this->getSetting('geolocation'),
    ];

    $countries = CountryManager::getStandardList();
    $elements['initial_country'] = [
      '#type' => 'select',
      '#title' => t('Initial Country'),
      '#options' => $countries,
      '#default_value' => $this->getSetting('initial_country'),
      '#description' => t('Set default selected country to use in phone field.'),
    ];

    $elements['exclude_countries'] = [
      '#type' => 'select',
      '#title' => t('Exclude Countries'),
      '#options' => $countries,
      '#multiple' => TRUE,
      '#default_value' => $this->getSetting('exclude_countries'),
      '#description' => t('In the dropdown, display all countries except the ones you specify here.'),
    ];

    $elements['preferred_countries'] = [
      '#type' => 'select',
      '#title' => t('Preferred Countries'),
      '#multiple' => TRUE,
      '#options' => $countries,
      '#default_value' => $this->getSetting('preferred_countries'),
      '#description' => t('Set the initial country selection by specifying its country code. If you leave Preferred Countries blank, it will default to the first country in the list.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $geolocation = $this->getSetting('geolocation');
    $summary[] = t('Use Geolocation: @display_label', ['@display_label' => ($geolocation ? t('Yes') : 'No')]);
    if (!$geolocation) {
      $summary[] = t('Default selected country: @value', ['@value' => $this->getSetting('initial_country')]);
    }
    $summary[] = t('Exclude Countries: @value', ['@value' => implode(",", $this->getSetting('exclude_countries'))]);
    $summary[] = t('Preferred Countries: @value', ['@value' => implode(",", $this->getSetting('preferred_countries'))]);
    return $summary;
  }

}
