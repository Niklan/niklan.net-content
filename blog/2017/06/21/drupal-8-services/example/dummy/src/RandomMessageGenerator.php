<?php

namespace Drupal\dummy;

/**
 * Class RandomMessageGenerator
 *
 * @package Drupal\dummy
 */
class RandomMessageGenerator {

  // Массив с сообщениями.
  private $messages;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    // Записываем сообщения в свойство.
    $this->setMessages();
  }

  /**
   * Здесь мы просто задаем все возможные варианты сообщений.
   */
  private function setMessages() {
    $this->messages = [
      'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
      'Phasellus maximus tincidunt dolor et ultrices.',
      'Maecenas vitae nulla sed felis faucibus ultricies. Suspendisse potenti.',
      'In nec orci vitae neque rhoncus rhoncus eu vel erat.',
      'Donec suscipit consequat ex, at ultricies mi venenatis ut.',
      'Fusce nibh erat, aliquam non metus quis, mattis elementum nibh. Nullam volutpat ante non tortor laoreet blandit.',
      'Suspendisse et nunc id ligula interdum malesuada.',
    ];
  }

  /**
   * Метод, который возвра
   */
  public function getRandomMessage() {
    $random = rand(0, count($this->messages));
    return $this->messages[$random];
  }

}