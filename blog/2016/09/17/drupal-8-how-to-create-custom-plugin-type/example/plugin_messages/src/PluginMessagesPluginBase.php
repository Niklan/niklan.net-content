<?php

namespace Drupal\plugin_messages;

use Drupal\Component\Plugin\PluginBase;

abstract class PluginMessagesPluginBase extends PluginBase implements PluginMessagesPluginInterface {

  /**
   * Конструктор плагина - он в 99% случаев будет именно такой. По-умолчанию он
   * записывает все данные переменные в одноименные локальные свойства плагина.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    # Возвращаем значение аннотации $id.
    return $this->pluginDefinition['id'];
  }

  /**
   * По-умолчанию все наши сообщения будут иметь тип status, если плагин не
   * укажет своего.
   */
  public function getMessageType() {
    return 'status';
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPages() {
    return [];
  }

}