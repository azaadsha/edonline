<?php

namespace Drupal\appointment_time_slots\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Time Slot Widget.
 *
 * @FieldWidget(
 *   id = "time_slot_widget",
 *   label = @Translation("Time Slot Widget"),
 *   field_types = {
 *     "time_slot_field",
 *     "string"
 *   }
 * )
 */
class TimeSlotWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings            = $this->getFieldSettings();
    $interval                  = $field_settings['slot_time'];
    $element['time_slot_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Time Slot Date'),
      '#default_value' => isset($items[$delta]->time_slot_date) ? $items[$delta]->time_slot_date : date('Y-m-d'),
      '#prefix' => '<span class= "check-booking"></span>',
      '#ajax' => [
        'callback' => 'Drupal\time_slot\Plugin\Field\FieldWidget\TimeSlotWidget::slotschecker',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Updating Time Slots',
        ],
      ],
    ];
    $selected_date             = $element['time_slot_date']['#default_value'];
    $start_time                = $selected_date . " 00:00";
    $end_time                  = $selected_date . " 23:59";
    $time_interval_array       = [];
    $start_time                = strtotime($start_time);
    $end_time                  = strtotime($end_time);
    $add_mins                  = $interval * 60;
    while ($start_time <= $end_time) {
      $start = date("G:i", $start_time);
      $end = $start_time + $add_mins;
      $end = date("G:i", $end);
      $time_interval_array[] = $start . ' - ' . $end;
      $start_time += $add_mins;
    }
    $data = $time_interval_array;
    $element['time_slot_time'] = [
      '#type' => 'select',
      '#title' => $this->t('Time Slot'),
      '#default_value' => isset($items[$delta]->time_slot_time) ? $items[$delta]->time_slot_time : NULL,
      '#options' => $data,
    ];
    return $element;
  }

  /**
   * AJAX callback method to check slots.
   */
  public function slotschecker(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $form_id = $form['#attributes']['class'][0];
    $val2 = substr($form_id, 5);
    $val3 = stripos($val2, "-");
    $content_type = substr($val2, 0, $val3);
    $fieldsArray = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('time_slot_field');
    $fields = $fieldsArray['node'];
    $field_name = array_keys($fields);
    foreach ($field_name as $f) {
      if ($content_type == key($fieldsArray['node'][$f]['bundles'])) {
        $field_name = $f;
      }
    }

    $values = $form_state->getValues();
    $ajaxdate = $values[$field_name][0]['time_slot_date'];
    $database_name = 'node_revision__' . $field_name;
    $condition_date = $field_name . '_time_slot_date';
    $condition_time = $field_name . '_time_slot_time';

    $db = \Drupal::database();
    $query = $db->select($database_name, 'n');
    $query->fields('n');
    $query->condition('bundle', $content_type, "=");
    $query->condition($condition_date, $ajaxdate, "=");
    $result = $query->execute()->fetchAll();

    $options = [];
    foreach ($result as $key => $value) {
      $options[$key] = $result[$key]->$condition_time;
    }

    $values = $form_state->getValues();
    $ajaxtime = $values[$field_name][0]['time_slot_time'];
    if (in_array($ajaxtime, $options)) {
      $text = 'Already Booked';
      $ajax_response->addCommand(new HtmlCommand('.check-booking', $text));
    }
    else {
      $text = 'Slot Available';
      $ajax_response->addCommand(new HtmlCommand('.check-booking', $text));
    }
    return $ajax_response;
  }

}
