<?php

/**
 * @file
 * Пример плагина.
 */
namespace Drupal\plugin_messages\Plugin\PluginMessages;

use Drupal\plugin_messages\Annotation\PluginMessages;
use Drupal\plugin_messages\PluginMessagesPluginBase;

/**
 * @PluginMessages(
 *   id="default_plugin_example_1",
 * )
 */
class DefaultPluginExample1 extends PluginMessagesPluginBase {

  /**
   * Возвращаем сообщение данного плагина.
   */
  public function getMessage() {
    return 'This is message from Example #1';
  }

}