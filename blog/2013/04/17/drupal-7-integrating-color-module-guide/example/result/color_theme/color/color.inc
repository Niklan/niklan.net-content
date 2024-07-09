<?php

// Массив со всеми настройками.
$info = array();

// Оъявляем все переменные-цвета.
// Каждая переменная - поле для выбора цвета.
// Имеют вид: 'ключ' => 'метка'.
// Можно использовать функцию t().
$info['fields'] = array(
	'bg' => 'Фон сайта',
	'page_bg' => 'Фон страницы',
	'link_color' => 'Цвет ссылок',
	'menu_bg_color' => 'Цвет фона пунктов меню',
	'menu_link_color' => 'Цвет ссылок пунктов-меню',
);

// Объявляем базовые цветовые схемы (предустановленные).
$info['schemes'] = array(
	// Объявляем обязательную схему, которая будет использовать по-умолчанию, и 
	// от которой будет отталкиваться модуль Color.
	'default' => array(
		// Название цветовой схемы. Будет отображаться в списке всех схем.
		'title' => 'Цветовая схема по-умолчанию',
		// Задаем цвета для каждого ключа ($info['fields']).
		// Все стандартные значения я беру из CSS.
		// Цвет должен быть в HEX формате, начинаться с решетки и быть в нижнем
		// регистре.
		'colors' => array(
			'bg' => '#f1f1f1',
			'page_bg' => '#ffffff',
			'link_color' => '#2158ce',
			'menu_bg_color' => '#ececec',
			'menu_link_color' => '#000000',
		),
	),
	// Добавляем еще одну цветовую схему, но уже с нужными нам цветами.
	'example' => array(
		'title' => 'Пример дополнительной схемы',
		// Делаем её полностью белой.
		'colors' => array(
			'bg' => '#ffffff',
			'page_bg' => '#ffffff',
			'link_color' => '#ffffff',
			'menu_bg_color' => '#ffffff',
			'menu_link_color' => '#ffffff',
		),
	),	
);

/**
 * Настройки ниже - пустышки. Они нужны модулю color, даже если мы их не
 * используем в своей теме.
 */

// Путь указывается относительно корня темы.
$info['css'] = array('color/color.css');

// Файлы для копирования. (color.css - копируется автоматически)
$info['copy'] = array();
 
// Файлы которые будут использоваться в превью.
$info['preview_css'] = 'color/preview.css';
$info['preview_js'] = 'color/preview.js';
$info['preview_html'] = 'color/preview.html';
 
// Объявление градиентов.
$info['gradients'] = array();
 
// Координаты для заливки.
$info['fill'] = array();
 
// Координаты для нарезки изображения.
$info['slices'] = array();
 
// Базовая картинка.
$info['base_image'] = 'color/base.png';