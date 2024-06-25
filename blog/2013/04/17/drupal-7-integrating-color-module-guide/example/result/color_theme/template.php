<?php
/**
 * Использум template_preprocess_html().
 */
function color_theme_process_html(&$variables) {
 if (module_exists('color')) {
 _color_html_alter($variables);
 }
}

/**
 * Используем template_process_page().
 */
function color_theme_process_page(&$variables, $hook) {
 if (module_exists('color')) {
 _color_page_alter($variables);
 }
}
