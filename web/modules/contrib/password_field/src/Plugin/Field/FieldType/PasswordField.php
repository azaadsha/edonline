<?php

namespace Drupal\password_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of Password.
 *
 * @FieldType(
 *   id = "Password",
 *   label = @Translation("Password"),
 *   default_widget ="PasswordWidget",
 *   default_formatter = "PasswordFieldFormatter"
 * )
 */
class PasswordField extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Columns contains the values that the field will store.
      'columns' => [
        // List the values that the field will save. This
        // field will only save a single value, 'value'.
        'value' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('string');
    return $properties;
  }

  /**
   * Unsets the variable.
   */
  public function __unset($value) {
    $str = $this->get('value')->getValue();
    $val = password_field_encrypt_decrypt('encrypt', $str);
    $this->set('value', $val);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
