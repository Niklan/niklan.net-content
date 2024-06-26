<?php
/**
 * @file
 * technical_support.views_default.inc
 */

/**
 * Implements hook_views_default_views().
 */
function technical_support_views_default_views() {
  $export = array();

  $view = new view();
  $view->name = 'technical_support';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'node';
  $view->human_name = 'Техническая поддержка';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Техническая поддержка';
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'title' => 'title',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'title' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
  );
  /* Header: Global: Text area */
  $handler->display->display_options['header']['area']['id'] = 'area';
  $handler->display->display_options['header']['area']['table'] = 'views';
  $handler->display->display_options['header']['area']['field'] = 'area';
  $handler->display->display_options['header']['area']['content'] = '<a href="/node/add/ticket">Создать новый Тикет</a>

<h2>Ваши обращения:</h2>';
  $handler->display->display_options['header']['area']['format'] = 'filtered_html';
  /* No results behavior: Global: Text area */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
  $handler->display->display_options['empty']['area']['empty'] = TRUE;
  $handler->display->display_options['empty']['area']['content'] = '<h2>Вы не создавали ни одного обращения в службу технической поддержки. Вы можете создать новый тикет нажав на кнопку ниже.</h2>

<a href="/node/add/ticket">Создать новый Тикет</a>';
  $handler->display->display_options['empty']['area']['format'] = 'filtered_html';
  /* Field: Content: Title */
  $handler->display->display_options['fields']['title']['id'] = 'title';
  $handler->display->display_options['fields']['title']['table'] = 'node';
  $handler->display->display_options['fields']['title']['field'] = 'title';
  $handler->display->display_options['fields']['title']['label'] = 'ID';
  $handler->display->display_options['fields']['title']['alter']['word_boundary'] = FALSE;
  $handler->display->display_options['fields']['title']['alter']['ellipsis'] = FALSE;
  /* Field: Field: Категория */
  $handler->display->display_options['fields']['field_ticket_category']['id'] = 'field_ticket_category';
  $handler->display->display_options['fields']['field_ticket_category']['table'] = 'field_data_field_ticket_category';
  $handler->display->display_options['fields']['field_ticket_category']['field'] = 'field_ticket_category';
  $handler->display->display_options['fields']['field_ticket_category']['type'] = 'taxonomy_term_reference_plain';
  /* Field: Field: Приоритет */
  $handler->display->display_options['fields']['field_ticket_priority']['id'] = 'field_ticket_priority';
  $handler->display->display_options['fields']['field_ticket_priority']['table'] = 'field_data_field_ticket_priority';
  $handler->display->display_options['fields']['field_ticket_priority']['field'] = 'field_ticket_priority';
  $handler->display->display_options['fields']['field_ticket_priority']['type'] = 'taxonomy_term_reference_plain';
  /* Field: Field: Статус */
  $handler->display->display_options['fields']['field_ticket_status']['id'] = 'field_ticket_status';
  $handler->display->display_options['fields']['field_ticket_status']['table'] = 'field_data_field_ticket_status';
  $handler->display->display_options['fields']['field_ticket_status']['field'] = 'field_ticket_status';
  $handler->display->display_options['fields']['field_ticket_status']['type'] = 'taxonomy_term_reference_plain';
  /* Field: Content: New comments */
  $handler->display->display_options['fields']['new_comments']['id'] = 'new_comments';
  $handler->display->display_options['fields']['new_comments']['table'] = 'node';
  $handler->display->display_options['fields']['new_comments']['field'] = 'new_comments';
  $handler->display->display_options['fields']['new_comments']['exclude'] = TRUE;
  $handler->display->display_options['fields']['new_comments']['prefix'] = '(новых ';
  $handler->display->display_options['fields']['new_comments']['suffix'] = ')';
  /* Field: Content: Comment count */
  $handler->display->display_options['fields']['comment_count']['id'] = 'comment_count';
  $handler->display->display_options['fields']['comment_count']['table'] = 'node_comment_statistics';
  $handler->display->display_options['fields']['comment_count']['field'] = 'comment_count';
  $handler->display->display_options['fields']['comment_count']['label'] = 'Ответов';
  $handler->display->display_options['fields']['comment_count']['alter']['alter_text'] = TRUE;
  $handler->display->display_options['fields']['comment_count']['alter']['text'] = '[comment_count] [new_comments]';
  /* Sort criterion: Content: Post date */
  $handler->display->display_options['sorts']['created']['id'] = 'created';
  $handler->display->display_options['sorts']['created']['table'] = 'node';
  $handler->display->display_options['sorts']['created']['field'] = 'created';
  $handler->display->display_options['sorts']['created']['order'] = 'DESC';
  /* Contextual filter: Content: Author uid */
  $handler->display->display_options['arguments']['uid']['id'] = 'uid';
  $handler->display->display_options['arguments']['uid']['table'] = 'node';
  $handler->display->display_options['arguments']['uid']['field'] = 'uid';
  $handler->display->display_options['arguments']['uid']['default_action'] = 'default';
  $handler->display->display_options['arguments']['uid']['default_argument_type'] = 'current_user';
  $handler->display->display_options['arguments']['uid']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['uid']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['uid']['summary_options']['items_per_page'] = '25';
  /* Filter criterion: Content: Published */
  $handler->display->display_options['filters']['status']['id'] = 'status';
  $handler->display->display_options['filters']['status']['table'] = 'node';
  $handler->display->display_options['filters']['status']['field'] = 'status';
  $handler->display->display_options['filters']['status']['value'] = 1;
  $handler->display->display_options['filters']['status']['group'] = 1;
  $handler->display->display_options['filters']['status']['expose']['operator'] = FALSE;
  /* Filter criterion: Content: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'node';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['value'] = array(
    'ticket' => 'ticket',
  );

  /* Display: Техническая поддержка */
  $handler = $view->new_display('page', 'Техническая поддержка', 'page_1');
  $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
  $handler->display->display_options['path'] = 'support';
  $handler->display->display_options['menu']['type'] = 'normal';
  $handler->display->display_options['menu']['title'] = 'Техническая поддержка';
  $handler->display->display_options['menu']['weight'] = '0';
  $handler->display->display_options['menu']['context'] = 0;

  /* Display: Техническая подддержка (Администрирование) */
  $handler = $view->new_display('page', 'Техническая подддержка (Администрирование)', 'page_2');
  $handler->display->display_options['defaults']['title'] = FALSE;
  $handler->display->display_options['title'] = 'Техническая подддержка (Администрирование)';
  $handler->display->display_options['defaults']['hide_admin_links'] = FALSE;
  $handler->display->display_options['defaults']['access'] = FALSE;
  $handler->display->display_options['access']['type'] = 'role';
  $handler->display->display_options['access']['role'] = array(
    3 => '3',
  );
  $handler->display->display_options['defaults']['arguments'] = FALSE;
  $handler->display->display_options['defaults']['filter_groups'] = FALSE;
  $handler->display->display_options['defaults']['filters'] = FALSE;
  /* Filter criterion: Content: Published */
  $handler->display->display_options['filters']['status']['id'] = 'status';
  $handler->display->display_options['filters']['status']['table'] = 'node';
  $handler->display->display_options['filters']['status']['field'] = 'status';
  $handler->display->display_options['filters']['status']['value'] = 1;
  $handler->display->display_options['filters']['status']['group'] = 1;
  $handler->display->display_options['filters']['status']['expose']['operator'] = FALSE;
  /* Filter criterion: Content: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'node';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['value'] = array(
    'ticket' => 'ticket',
  );
  /* Filter criterion: Field: Категория (field_ticket_category) */
  $handler->display->display_options['filters']['field_ticket_category_tid']['id'] = 'field_ticket_category_tid';
  $handler->display->display_options['filters']['field_ticket_category_tid']['table'] = 'field_data_field_ticket_category';
  $handler->display->display_options['filters']['field_ticket_category_tid']['field'] = 'field_ticket_category_tid';
  $handler->display->display_options['filters']['field_ticket_category_tid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['field_ticket_category_tid']['expose']['operator_id'] = 'field_ticket_category_tid_op';
  $handler->display->display_options['filters']['field_ticket_category_tid']['expose']['label'] = 'Категория';
  $handler->display->display_options['filters']['field_ticket_category_tid']['expose']['operator'] = 'field_ticket_category_tid_op';
  $handler->display->display_options['filters']['field_ticket_category_tid']['expose']['identifier'] = 'field_ticket_category_tid';
  $handler->display->display_options['filters']['field_ticket_category_tid']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
    3 => 0,
  );
  $handler->display->display_options['filters']['field_ticket_category_tid']['type'] = 'select';
  $handler->display->display_options['filters']['field_ticket_category_tid']['vocabulary'] = 'ticket_category';
  /* Filter criterion: Field: Приоритет (field_ticket_priority) */
  $handler->display->display_options['filters']['field_ticket_priority_tid']['id'] = 'field_ticket_priority_tid';
  $handler->display->display_options['filters']['field_ticket_priority_tid']['table'] = 'field_data_field_ticket_priority';
  $handler->display->display_options['filters']['field_ticket_priority_tid']['field'] = 'field_ticket_priority_tid';
  $handler->display->display_options['filters']['field_ticket_priority_tid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['field_ticket_priority_tid']['expose']['operator_id'] = 'field_ticket_priority_tid_op';
  $handler->display->display_options['filters']['field_ticket_priority_tid']['expose']['label'] = 'Приоритет';
  $handler->display->display_options['filters']['field_ticket_priority_tid']['expose']['operator'] = 'field_ticket_priority_tid_op';
  $handler->display->display_options['filters']['field_ticket_priority_tid']['expose']['identifier'] = 'field_ticket_priority_tid';
  $handler->display->display_options['filters']['field_ticket_priority_tid']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
    3 => 0,
  );
  $handler->display->display_options['filters']['field_ticket_priority_tid']['type'] = 'select';
  $handler->display->display_options['filters']['field_ticket_priority_tid']['vocabulary'] = 'ticket_priority';
  /* Filter criterion: Field: Статус (field_ticket_status) */
  $handler->display->display_options['filters']['field_ticket_status_tid']['id'] = 'field_ticket_status_tid';
  $handler->display->display_options['filters']['field_ticket_status_tid']['table'] = 'field_data_field_ticket_status';
  $handler->display->display_options['filters']['field_ticket_status_tid']['field'] = 'field_ticket_status_tid';
  $handler->display->display_options['filters']['field_ticket_status_tid']['exposed'] = TRUE;
  $handler->display->display_options['filters']['field_ticket_status_tid']['expose']['operator_id'] = 'field_ticket_status_tid_op';
  $handler->display->display_options['filters']['field_ticket_status_tid']['expose']['label'] = 'Статус';
  $handler->display->display_options['filters']['field_ticket_status_tid']['expose']['operator'] = 'field_ticket_status_tid_op';
  $handler->display->display_options['filters']['field_ticket_status_tid']['expose']['identifier'] = 'field_ticket_status_tid';
  $handler->display->display_options['filters']['field_ticket_status_tid']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
    3 => 0,
  );
  $handler->display->display_options['filters']['field_ticket_status_tid']['type'] = 'select';
  $handler->display->display_options['filters']['field_ticket_status_tid']['vocabulary'] = 'ticket_status';
  $handler->display->display_options['path'] = 'support/admin';
  $handler->display->display_options['menu']['type'] = 'normal';
  $handler->display->display_options['menu']['title'] = 'ТП (Админ)';
  $handler->display->display_options['menu']['weight'] = '0';
  $handler->display->display_options['menu']['context'] = 0;
  $export['technical_support'] = $view;

  return $export;
}
