<?php

namespace Drupal\plugin_messages;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Менеджер нашего плагина PluginMessages
 */
class PluginMessagesManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/PluginMessages',
      $namespaces,
      $module_handler,
      'Drupal\plugin_messages\PluginMessagesPluginInterface',
      'Drupal\plugin_messages\Annotation\PluginMessages'
    );
    # Регистрируем hook_plugin_messages_info_alter();
    # Это позволит править информацию уже существующих плагинов. Не обязательно,
    # в нашей ситуации даже бесползено, но для примера пусть будет.
    $this->alterInfo('plugin_messages_info');
    # Задаем ключ для кэша плагинов.
    $this->setCacheBackend($cache_backend, 'plugin_messages');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
