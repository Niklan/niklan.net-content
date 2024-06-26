<?php
/**
 * Используем hook_form_FORM_ID_alter().
 *
 * Альтерим форму настройки сайта. Это та, где вводится E-Mail сайта, его
 * название, страна, часовой пояс и регистрация юзера #1.
 */
function system_form_install_configure_form_alter(&$form, $form_state) {
  // Мы заполняет поле названия сайта по умолчанию названием нашего дистра.
  $form['site_information']['site_name']['#default_value'] = 'My first distro site';
}

/**
 * Используем hook_form_alter().
 *
 * Альтерим форму выбора дистрибутива. Так как мы не указали что он
 * эксклюзивен, то у пользователя будет выбор, а мы лишь сделаем чтобы наш
 * дистрибутив был выбран по-умолчанию (чтобы стояла галочка).
 */
function system_form_install_select_profile_form_alter(&$form, $form_state) {
  foreach ($form['profile'] as $key => $element) {
    // Указывается машинное имя сборки.
    $form['profile'][$key]['#value'] = 'my_first_distro';
  }
}


