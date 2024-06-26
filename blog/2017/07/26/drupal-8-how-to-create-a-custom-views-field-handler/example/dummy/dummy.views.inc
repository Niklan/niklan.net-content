<?php

/**
 * @file
 * Views hooks.
 */

/**
 * Implements hook_views_data_alter().
 *
 * Alter DB tables defined via hook_views_data().
 */
function dummy_views_data_alter(array &$data) {
  $data['node']['dummy_created_relative'] = [
    'title' => t('Relative creation date'),
    'field' => [
      'title' => t('Relative creation date'),
      'help' => t('The creation date will be printed as relative date.'),
      'id' => 'dummy_created_relative',
    ],
  ];

  $data['node']['dummy_two_fields'] = [
    'title' => t('Two fields math operation'),
    'field' => [
      'title' => t('Two fields math operation'),
      'help' => t('Simple math operation between two selected fields.'),
      'id' => 'dummy_two_field_math',
      'click sortable' => FALSE,
    ],
  ];
}
