<?php

/**
 * @file
 * Provides views data for the calendar module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data_alter().
 */
function calendar_views_data_alter(array &$data) {
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $property_name => $properties) {
      if (isset($properties['argument']) && $properties['argument']['id'] === 'date') {
        $data[$table_name] += _calendar_create_field_arguments($property_name);
      }
    }
  }
  $data['views']['calendar_header'] = [
    'title' => t('Calendar Header'),
    'help' => t('Calendar heading or pager.'),
    'area' => [
      'id' => 'calendar_header',
    ],
  ];

}

/**
 * Create all the granularity arguments for a a field.
 *
 * @param $field_id
 * @param string $group
 *
 * @return mixed
 */
function _calendar_create_field_arguments($field_id, $group = '') {
  $granularity_args = [];
  // All other granularity types are added by core.
  $granularity_types = [
    'year_week' => t('Date in the form of YYYYW'),
  ];
  foreach ($granularity_types as $granularity_type => $granularity_label) {

    $granularity_arg = [
      'title' => t('Calendar @name @granularity', ['@name' => $field_id, '@granularity' => $granularity_type]),
      'help' => t('A calendar contextual filter that handles @label.', ['@label' => $granularity_label]),
      'argument' => [
        'field' => $field_id,
        'id' => 'date_' . $granularity_type,
      ],
    ];
    if ($group) {
      $granularity_arg['group'] = $group;
    }
    $granularity_args[$field_id . '_' . $granularity_type] = $granularity_arg;
  }
  return $granularity_args;
}

/**
 * Implements hook_field_views_data_alter().
 */
function calendar_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  foreach ($data as $table_name => $table_data) {
    // Set the 'datetime' filter type.
    if (isset($data[$table_name][$field_storage->getName() . '_value'])
      && $data[$table_name][$field_storage->getName() . '_value']['filter']['id'] == 'datetime') {
      // Create year, month, and day arguments.
      $group = $data[$table_name][$field_storage->getName() . '_value']['group'];
      $arguments = [
        // Argument type => help text.
        'year_week' => t('Calendar Date in the form of CCYYW.'),
      ];
      foreach ($arguments as $argument_type => $help_text) {
        $data[$table_name][$field_storage->getName() . '_value_' . $argument_type] = [
          'title' => 'Calendar ' . $field_storage->getLabel() . ' (' . $argument_type . ')',
          'help' => $help_text,
          'argument' => [
            'field' => $field_storage->getName() . '_value',
            'id' => 'datetime_' . $argument_type,
            'entity_type' => $field_storage->getTargetEntityTypeId(),
            'field_name' => $field_storage->getName(),
          ],
          'group' => $group,
        ];
      }
    }
  }
}
