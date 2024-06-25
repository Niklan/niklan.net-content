<?php

namespace Drupal\dummy\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @LanguageNegotiation(
 *   id = \Drupal\dummy\Plugin\LanguageNegotiation\LanguageNegotiationParameter::METHOD_ID,
 *   name = @Translation("GET parameter"),
 *   description = @Translation("Language from GET parameter."),
 * )
 */
class LanguageNegotiationParameter extends LanguageNegotiationMethodBase {

  /**
   * ID нашего плагина.
   */
  const METHOD_ID = 'get-parameter';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    // Если вы хотите выполняет какое либо условие, обязательно проверяйте на
    // существование $this->languageManager && $request для избежания ошибок.
    // При помощи $request->query->get('set-lang') мы получаем $_GET['set-lang']
    // и узнаем передали его или нет.
    if ($this->languageManager && $request && $request->query->get('set-lang')) {
      // Получаем доступные на сайте языки. Они идут в виде массива:
      // langcode => Language Name. Поэтому мы сразу получаем только ключи, так
      // как определять будем по ним.
      $langcodes = array_keys($this->languageManager->getLanguages());
      $set_lang = $request->query->get('set-lang');
      // Если указанный пользователям язык присудствует на сайте, мы
      // устанавливаем как языка для всего сайта.
      if (in_array($set_lang, $langcodes)) {
        $langcode = $set_lang;
      }
    }

    return $langcode;
  }

}
