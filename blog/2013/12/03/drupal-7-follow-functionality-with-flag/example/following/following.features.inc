<?php
/**
 * @file
 * following.features.inc
 */

/**
 * Implements hook_views_api().
 */
function following_views_api($module = NULL, $api = NULL) {
  return array("api" => "3.0");
}

/**
 * Implements hook_flag_default_flags().
 */
function following_flag_default_flags() {
  $flags = array();
  // Exported flag: "Подписка на пользователей".
  $flags['user_subscribe'] = array(
    'entity_type' => 'user',
    'title' => 'Подписка на пользователей',
    'global' => 0,
    'types' => array(),
    'flag_short' => 'Подписаться',
    'flag_long' => '',
    'flag_message' => '',
    'unflag_short' => 'Отписаться',
    'unflag_long' => '',
    'unflag_message' => '',
    'unflag_denied_text' => '',
    'link_type' => 'toggle',
    'weight' => 0,
    'show_in_links' => array(
      'full' => 0,
      'token' => 0,
    ),
    'show_as_field' => 0,
    'show_on_form' => 0,
    'access_author' => '',
    'show_contextual_link' => 0,
    'show_on_profile' => 1,
    'access_uid' => 'others',
    'api_version' => 3,
    'module' => 'following',
    'locked' => array(
      0 => 'name',
    ),
  );
  return $flags;

}

/**
 * Implements hook_node_info().
 */
function following_node_info() {
  $items = array(
    'article' => array(
      'name' => t('Article'),
      'base' => 'node_content',
      'description' => t('Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.'),
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
  );
  return $items;
}
