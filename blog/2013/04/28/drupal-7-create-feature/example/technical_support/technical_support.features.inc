<?php
/**
 * @file
 * technical_support.features.inc
 */

/**
 * Implements hook_views_api().
 */
function technical_support_views_api() {
  return array("api" => "3.0");
}

/**
 * Implements hook_node_info().
 */
function technical_support_node_info() {
  $items = array(
    'ticket' => array(
      'name' => t('Тикет'),
      'base' => 'node_content',
      'description' => '',
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
  );
  return $items;
}
