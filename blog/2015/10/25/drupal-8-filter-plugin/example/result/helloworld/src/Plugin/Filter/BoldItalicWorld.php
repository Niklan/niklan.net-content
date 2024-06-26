<?php
/**
 * @file
 * Contains Drupal\helloworld\Plugin\Filter\BoldItalicWorld.
 */

// Напоминаю что helloworld - название модуля.
namespace Drupal\helloworld\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * Ниже аннотация. В ней обязательно надо указать id, title, type:
 * id - уникальный идентификатор фильтра (машинное имя);
 * title - лейбел (название) для нашего фильтра в административном интерфейсе;
 * type - тип фильтра. Типов всего 4:
 *  - TYPE_HTML_RESTRICTOR - используется для фильтрации html. Для того чтобы
 *    убрать неподобающие теги, атрибуты и т.д. В частности для защиты от XSS.
 *  - TYPE_MARKUP_LANGUAGE - для фильтров, которые работают не с HTML, а на
 *    выходе уже дают HTML. Какраз то что нам надо. Мы работаем с текстом, но
 *    затем мы обарачиваем его в <strong><i></i></strong>.
 *  - TYPE_TRANSFORM_IRREVERSIBLE;
 *  - TYPE_TRANSFORM_REVERSIBLE.
 *
 * По поводу последних двух я пока не понял для чего они. Примеров и упоминаний
 * о них в ядре не нашел, описания совершенно ни о чем не говорят. Как узнаю
 * дополню информацию.
 *
 * @Filter(
 *   id = "bold_italic_world",
 *   title = @Translation("Hello World is strong."),
 *   description = @Translation("Makes 'Hello World' bold and italic."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class BoldItalicWorld extends FilterBase {

  /**
   * В данном методе мы уже выполняем саму логику фильтра.
   *
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Этот класс требуется для удобства работы.
    $result = new FilterProcessResult($text);

    // Для достижения нужного результата нам будет достаточно воспользоваться
    // стандартной php функцией для замены строки.
    $text = str_replace('Hello World', '<strong><i>Hello World</i></strong>', $text);

    // Сохраняем результат и возвращаем.
    $result->setProcessedText($text);
    return $result;
  }

  /**
   * В данном методе (он не обязателен) мы возвращаем подсказку для форматов
   * ввода.
   *
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Every "Hello World" text will be strong and italic.');
  }

}
