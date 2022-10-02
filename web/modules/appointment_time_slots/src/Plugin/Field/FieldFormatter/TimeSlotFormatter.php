<?php

namespace Drupal\appointment_time_slots\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Time Slot Formatter.
 *
 * @FieldFormatter(
 *   id = "time_slot_formatter",
 *   label = @Translation("Time Slot Formatter"),
 *   field_types = {
 *     "time_slot_field",
 *     "string"
 *   }
 * )
 */
class TimeSlotFormatter extends FormatterBase {

  /**
   * Defines how the time slot field is shown.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->time_slot_date . ', ' . $item->time_slot_time,
      ];
    }

    return $elements;
  }

}
