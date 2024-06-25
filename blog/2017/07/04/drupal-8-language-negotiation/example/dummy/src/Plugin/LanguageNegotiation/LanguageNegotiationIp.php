<?php

namespace Drupal\dummy\Plugin\LanguageNegotiation;

use Drupal\language\LanguageNegotiationMethodBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @LanguageNegotiation(
 *   id = \Drupal\dummy\Plugin\LanguageNegotiation\LanguageNegotiationIp::METHOD_ID,
 *   name = @Translation("IP"),
 *   description = @Translation("Language by IP pool."),
 *   config_route_name = "dummy.language_negotiation_ip_settings"
 * )
 */
class LanguageNegotiationIp extends LanguageNegotiationMethodBase {

  /**
   * ID нашего плагина.
   */
  const METHOD_ID = 'ip';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    if ($this->languageManager && $request) {
      // Получаем настройку.
      $ips = $this->config->get('dummy.language_negotiation_ip_settings')->get('ips');
      // Создаем массив.
      $ips_array = explode(PHP_EOL, $ips);
      $user_ip = \Drupal::request()->getClientIp();

      if (in_array($user_ip, $ips_array)) {
        // Устанавливаем русский язык если IP юзера находится в списке
        // указанных.
        $langcode = 'ru';
      }
    }

    return $langcode;
  }

}

