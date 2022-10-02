<?php

namespace Drupal\appointment_time_slots\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Time Slot Field Type.
 *
 * @FieldType(
 *   id = "time_slot_field",
 *   label = @Translation("Time Slot"),
 *   default_widget = "time_slot_widget",
 *   default_formatter = "time_slot_formatter"
 * )
 */
class TimeSlotField extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = [
      'slot_time' => 30,

    ] + parent::defaultFieldSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [];
    $columns['time_slot_date'] = [
      'type' => 'text',
      'size' => 'tiny',
      'not null' => FALSE,
    ];
    $columns['time_slot_time'] = [
      'type' => 'text',
      'size' => 'tiny',
      'not null' => FALSE,
    ];

    return [
      'columns' => $columns,
      'indexes' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {

    $isEmpty = empty($this->get('time_slot_date')->getValue()) && empty($this->get('time_slot_time')->getValue());

    return $isEmpty;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::fieldSettingsForm($form, $form_state);

    $settings = $this->getSettings();
    $element['slot_time'] = [
      '#type' => 'select',
      '#title' => $this->t('Slot Time Difference'),
      '#options' => [
        15 => $this->t('15 minutes'),
        30 => $this->t('30 minutes'),
        45 => $this->t('45 minutes'),
        60 => $this->t('60 minutes'),
      ],
      '#default_value' => $settings['slot_time'],
      '#description' => $this->t('Select Slot Time'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties = [];

    $properties['time_slot_date'] = DataDefinition::create('string')
      ->setLabel(t('Time Slot date'));

    $properties['time_slot_time'] = DataDefinition::create('string')
      ->setLabel(t('Time Slot Time'));

    return $properties;
  }

}
