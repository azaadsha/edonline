<?php

namespace Drupal\phone_international\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'phone_international_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "phone_international_formatter",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "phone_international"
 *   }
 * )
 */
class PhoneInternationalDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $phone_number = $this->viewValue($item);
      // Render each element as link.
      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $phone_number,
        // Prepend 'tel:' to the telephone number.
        '#url' => Url::fromUri('tel:' . $phone_number),
        '#options' => ['external' => TRUE],
      ];

      if (!empty($item->_attributes)) {
        $elements[$delta]['#options'] += ['attributes' => []];
        $elements[$delta]['#options']['attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
