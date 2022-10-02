<?php

namespace Drupal\password_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'Password Field' formatter.
 *
 * @FieldFormatter(
 *   id = "PasswordFieldFormatter",
 *   label = @Translation("Password text"),
 *   field_types = {
 *     "Password"
 *   }
 * )
 */
class PasswordFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = ['#markup' => $item->value];
    }
    if ($element[0]['#markup'] != NULL) {
      $element[0]['#markup'] = '<span>*******</span>';
    }
    return $element;
  }

}
