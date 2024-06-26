<?php

namespace Drupal\plugin_messages;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface PluginMessagesPluginInterface extends PluginInspectionInterface {

  /**
   * Метод, через который мы будем получать ID плагина.
   */
  public function getId();

  /**
   * Метод, через который будем получать тип сиситемного сообщения.
   */
  public function getMessageType();

  /**
   * Метод, который будет возвращать непосредственно само сообщение.
   */
  public function getMessage();

  /**
   * Данный метод будет возвращать список страниц на которых необходимо выводить
   * данное сообщение.
   */
  public function getPages();

}