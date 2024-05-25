---
id: d8-middleware-api
language: ru
title: 'Drupal 8: Middleware API'
created: '2018-07-05T07:15:54'
updated: '2024-05-25T00:00:00'
description: >-
  В этом материале рассказываю и показываю что такое Middleware API и как он
  может вам помочь.
attachments:
  - name: 'Готовый модуль с примерами'
    path: 'attachment/dummy.tar.gz'
promo: 'image/pexels-photo-247768.jpeg'
tags:
  - Drupal
  - 'Drupal 8'
---

**Middleware API** (посредники) — это обработчики HTTP-запроса. Благодаря
посредникам, можно влиять на формирование запроса и ответа сайта на самом раннем
уровне. Скорее всего, раньше посредников ничего, к чему бы можно было
подключиться, и не вызывается.

Это достаточно специфичный API, и пользоваться им нужно крайне аккуратно. Это
аналог [hook_boot()](https://api.drupal.org/api/drupal/modules%21system%21system.api.php/function/hook_boot/7.x)
из Drupal 7.

Drupal 8 Middleware API совместим со [StackPHP](https://stackphp.com/), который,
в свою очередь, основывается на Symfony `HttpKernelInterface` и позволяет
реализовывать посредников. Это означает, что вы можете зайти,
например, [сюда](https://stackphp.com/middlewares/), и накатить любой Middleware
на свой сайт (нужно его объявить в качестве сервиса в Drupal). Главное не
забывать, что вы работаете с Drupal, и возможно какие-то особенные или
специфичные Middleware вам не подойдут, например, посредник написанный
исключительно под Laravel с учётом его спицифики в Drupal может не завестись.

**Для чего можно использовать** Middleware? Вариантов может быть очень много,
учитывая что вызывается он **на каждый запрос** к сайту, то он может решать
множество задач, например:

- Редиректить при определенных условиях.
- Лимитировать количество запросов с определенных
  IP ([Rate Limiter](https://www.drupal.org/project/rate_limiter)).
- Отвечать за кэширование (в ядре `http_middleware.page_cache`).
- Отключать загрузку сайта если IP в черном списке (в ядре `ban.middleware`).
- Управлять сессиями пользователей (в ядре `http_middleware.session`).
- Заниматься реверс-прокси (в ядре `http_middleware.reverse_proxy`).
- и прочим.

## Создание Middleware

Middleware является обычным [сервисом][d8-services] (читай объектом), который
реализует `HttpKernelInterface`.

```yaml
services:
  mymodule.first_middleware:
    class: Drupal\mymodule\FirstMiddleware
    tags:
      - { name: http_middleware, priority: 150 }
```

Всё стандартно для сервисов. Но в `tags` сервиса можно также задать `responder`.
Данное значение опциональное, и очень спецефичное. Если установить в `true`, то
это означает, что данный сервис может вернуть уже готовый ответ сервера (
Response).

Вернуть ответ может любой из Middleware, никто этого не запрещает. Но такой
параметр подразумевает что большинство запросов будет напрямую обработано
посредником. При этом, он не обязательно должен возвращать ответ, он также может
вызывать последующие Middleware сервисы. Так работает единственный в ядре
responder `http_middleware.page_cache`, который возвращает ответ из кэша, если
кэш включен и найден, иначе он передает обработку дальше всем следующим
Middleware.

Можно это понимать и следующим образом. Все посредники что идут после первого по
приоритету responder помечаются "ленивыми", и их объекты инициализируются только
если этот responder не вернет ответ. Своего рода оптимизация. Если опять
вернуться к кэш респондеру. То все что идет после него, запустится и подгрузится
только если он не смог вернуть ответ из кэша.

Если посредник с responder = true вернет ответ, то все посредники идущие после
него по приоритету, будут проигнорированы. Вероятнее всего, вам такое не
потребуется.

```php {"header":"Пример из Chi-teck/drupal-code-generator"}
<?php

namespace Drupal\mymodule;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * FirstMiddleware middleware.
 */
class FirstMiddleware implements HttpKernelInterface {

  use StringTranslationTrait;

  /**
   * The kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs the FirstMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($request->getClientIp() == '127.0.0.1') {
      return new Response($this->t('Bye!'), 403);
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

}
```

Опять же, ничего необыного. Обязательный метод `handle()` отвечает за всю
логику. Если ваш Middleware не responder, то вы должны всегда
возвращать `return $this->httpKernel->handle($request, $type, $catch);`, так
обработка запроса передается другим посредникам. Если, конечно, нет
необходимости сразу отдать ответ.

Например, как это делает модуль Ban. Он не занимается формированием ответа,
поэтому он не responder, но при этом, если в его логике проверка вернет, что IP
того, кто обращается к сайту, находится в черном списке. Он просто вернет ответ
с запретом и прекратит дальнешее выполнение обработки запроса, ядра и всех
прочих операций. Тем самым, пользователи с заблокированных IP не смогут увидеть
сайт и не будут создавать практически никакой нагрузки.

Вы можете заметить похожую конструкцию в **index.php
** — `$response = $kernel->handle($request);`. Отсюда начинается работа ядра, и
в этом вызове, почти сразу, начинается вызов
Middleware `$response = $this->getHttpKernel()->handle($request, $type, $catch);`,
которая делает первый вызов в цепочке, которую мы продолжаем в своём return
Middleware, либо сразу отдаем нужный ответ сервера.

**Очень важно** понимать что Middleware вызывается **абсолютно при каждом
обращении к сайту** (если до него ни один из посредников не вернет Response).
Ничего тяжелого там выполняться не должно. Если вы напишите в своём Middleware,
условный код `sleep(10)`, то абсолютно каждый запрос к сайту, даже с кэшем,
будет ожидать эти 10 секунд, а только затем выполняться остальная обработка.
Поэтому, ничего тяжелого тут быть просто не должно.

В связи с тем, что вызываются посредники на очень ранних этапах, то некоторые
возможности ядра могут быть просто недоступны — это нормально, и это нужно
учитывать. Если у вас что-то не получается, что-то ещё не прогруженно, возможно
стоит обратить внимание на `KernelEvents` и использовать [Events][d8-events]
вместо посредника.

_Далее в примерах, код пишется в модуле под названием dummy._

_Для быстрой генерации можно использовать Drush `drush generate middleware`._

## Пример №1 — чистка UTM-меток

В первом примере мы создадим простой Middleware. Мы будем проверять, есть ли в
текущем URL query параметры UTM-меток, и если есть, переадресовывать на эту же
страницу, но предварительно подчистив их.

Для этого, в src папке модуля создадим **UtmDummyMiddleware.php** и опишем всю
логику.

```php {"header":"src/UtmDummyMiddleware.php"}
<?php

namespace Drupal\dummy;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * UtmDummyMiddleware middleware.
 */
class UtmDummyMiddleware implements HttpKernelInterface {

  /**
   * The kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * An array with all available utm_* query params.
   */
  protected $utmQueryList;

  /**
   * Constructs the UtmDummyMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
    $this->utmQueryList = [
      'utm_source',
      'utm_medium',
      'utm_campaign',
      'utm_term',
      'utm_content',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($this->hasUtm($request)) {
      $this->cleanUtm($request);
      // Get uri without any query args.
      // We can't use here Url::createFromRequest(), because core boostrap at
      // very early stage and this will cause error.
      $uri_without_query = strtok($request->getUri(), '?');
      $altered_query_params = empty($request->query->all()) ? '' : '?' . http_build_query($request->query->all());
      return new RedirectResponse($uri_without_query . $altered_query_params);
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Checks for utm query parameters in current request.
   */
  public function hasUtm(Request $request) {
    foreach ($this->utmQueryList as $utm_query) {
      if ($request->query->has($utm_query)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Remove all UTM parameters from query.
   *
   * This method only removes UTM query args from parameter bag, not from URL.
   */
  public function cleanUtm(Request $request) {
    foreach ($this->utmQueryList as $utm_query) {
      $request->query->remove($utm_query);
    }
  }

}
```

- В `__construct` мы получаем обязательный сервис-аргумент, а также задаем
  массив из списка всех доступных UTM-меток что мы будем вычищать.
- Метод `hasUtm()` принимает объект запроса, проходится по массиву с UTM
  метками, и если нашел хотябы одну, сразу возвращает `TRUE`, если не найдет ни
  одной, вернет `FALSE`.
- Метод `cleanUtm()` принимает объект запроса, и проходится по массиву с UTM
  метками, и удаляет все соответствующие query параметры из Parameters Bag
  запроса.
- Ядро вызывает `handle()`. В нем мы описывем всю необходимую логику. Первым
  делом проверяем, есть ли в текущем запросе UTM-метки, при помощи кастомного
  метода `hasUtm()`.
  - Если нашлось, мы вызываем второй кастомный метод `cleanUtm()` и вычищаем эти
    метки. Затем формируем новый URL из адреса запроса и query параметров, но
    уже без UTM параметров. А затем переадресовываем пользователя на
    получившийся URL (по умолчаню 302).
  - Если не нашлось, мы передаем обработку запроса следующим посредникам.

Теперь нам нужно объявить данный объект как сервис Middleware.

```yaml {"header":"dummy.services.yml"}
services:
  dummy.middleware.utm:
    class: Drupal\dummy\UtmDummyMiddleware
    tags:
      - { name: http_middleware, priority: 450 }
```

Мы установили приоритет 450, так как самый высокий приоритет в ядре 400. Так,
наш посредник будет вызываться самым первым. Нет никакого смысла чтобы
вызывались какие-либо обработчики до нашего, так как в случае чего случится
редирект, и то что они выполняли, время и нагрузка — лишняя работа.

Теперь, если включить модуль, а если включен, сбросить кэш, то при заходе на
любую страницу сайта с UTM метками в запросе, будет произведен 302-ой редирект
на тот же самый URL без них. `example.com/node/1?utm_term=drupal&test=test`
переадресует на `example.com/node/1?test=test`.

**В чём плюс** такого редиректа? Данный редирект произойдет когда только
поступил запрос. Ничего не успеет даже загрузиться как посетитель будет
переадресован. И это хорошо скажется на скорости редиректа и нагрузки на сервер.
Если очень грубо, по скорости будет так, буд-то вы прописали редирект прямо в
index.php файле.

Также, например, если у вас есть страницы с cache contexts `url.query_args`,
которые будут иметь варианты на все возможные комбинации в query строке, то они
не будут иметь вариантов с UTM метками, так как наш посредник вычистит их и
переадресует пользователя задолго до срабатывания посредника с кэшированием.

## Пример №2 — определение страны и города

**Данный пример не предназначен для использования на рабочих сайтах, это пример,
никакой оптимизации в его логике для примера нет (надо кэшить результат). Да и
не факт, что передавать через header удачное решение.**

В этом примере мы воспользуемся
библиотекой [Geocoder](https://github.com/geocoder-php/Geocoder) и будем
определять страну с городом посетителя, а затем записывать их в header запроса
под ключами `X-Country` и `X-City`. Так, в дальнейшем процессе обработки запроса
ядром Drupal, из любого места будут доступны город и страна посетителя.

Первым делом нам потребуется подтянуть зависимости для такого модуля. Они
ставятся **исключительно** [композером][d8-composer], без него пример не
реализовать.

Для этого в папке модуля создаем `composer.json` файлик, и прописываем все
необходимые зависимости для Geocoder. Мы будем
использовать [GeoPlugin](https://github.com/geocoder-php/geo-plugin-provider)
провайдер, который определят страну и город (и много чего ещё) по IP, также нам
две дополнительные зависимости без который Geocoder не будет работать.

```json {"header":"composer.json модуля"}
{
  "name": "drupal/dummy",
  "type": "drupal-module",
  "description": "Middleware example",
  "keywords": [
    "Drupal"
  ],
  "require": {
    "geocoder-php/geo-plugin-provider": "^4.0",
    "php-http/guzzle6-adapter": "^1.1",
    "php-http/message": "^1.6"
  }
}
```

После этого, если в composer.json файле вашего проекта нет
зависимости `wikimedia/composer-merge-plugin`, то её необходимо поставить (в
стандартной поставке ядра он есть, в composer drupal project нет).

А затем прописать путь до вашего composer.json из модуля в extra ->
merge-plugin -> include.

```json {"header":"composer.json проекта"}
{
    "extra": {
        "merge-plugin": {
            "include": [
                "modules/custom/*/composer.json"
            ]
        }
    }
}
```

После чего вызвать `composer update`, чтобы он выкачал наши новые зависимости.

Всё, мы готовы писать Middleware!

Создаем файл **GeoIpDummyMiddleware.php**

```php {"header":"GeoIpDummyMiddleware.php"}
<?php

namespace Drupal\dummy;

use Geocoder\Provider\GeoPlugin\GeoPlugin;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;
use Http\Adapter\Guzzle6\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * GeoIpDummyMiddleware middleware.
 */
class GeoIpDummyMiddleware implements HttpKernelInterface {

  /**
   * The kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs the GeoIpDummyMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    // Trying to find actual country and city.
    $this->lookup($request);
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Trying to find country and city by using Geolocation.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function lookup(Request $request) {
    $httpClient = new Client();
    $provider = new GeoPlugin($httpClient);
    $geocoder = new StatefulGeocoder($provider, $request->getLocale());
    $geo_query = GeocodeQuery::create($request->getClientIp());
    $results = $geocoder->geocodeQuery($geo_query);
    if ($results->has(0)) {
      $first_locality = $results->get(0);
      $request->headers->set('X-Country', $first_locality->getCountry());
      $request->headers->set('X-City', $first_locality->getLocality());
    }
  }

}
```

Всё как и в предыдущем примере, только мы вынесли всю логику работы с Geocoder
библиотекой в отдельный метод `lookup()`.

Нам осталось объявить данный объект как Middleware сервис.

```yaml {"highlighted_lines":"7:10","header":"dummy.services.yml"}
services:
  dummy.middleware.utm:
    class: Drupal\dummy\UtmDummyMiddleware
    tags:
      - { name: http_middleware, priority: 450 }

  dummy.middleware.geoip:
    class: Drupal\dummy\GeoIpDummyMiddleware
    tags:
      - { name: http_middleware, priority: 400 }
```

Мы сделали приоритет ниже нашего редиректа, так как искать город до него, нет
никакого смысла, пустая трата ресурсов.

Ну и чтобы проверить что Middleware работает, напишем простенький
hook_preprocess_HOOK().

```php {"header":"dummy.module"}
<?php

/**
 * @file
 * Main file for custom hooks.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_preprocess_HOOK() for page.html.twig.
 */
function dummy_preprocess_page(&$variables) {
  $request = \Drupal::request();
  $country = $request->headers->get('X-Country', FALSE);
  $city = $request->headers->get('X-City', FALSE);
  if ($country && $city) {
    $message = new TranslatableMarkup('We know that you are from @city, @country! ;)', [
      '@city' => $city,
      '@country' => $country,
    ]);
    \Drupal::messenger()->addMessage($message);
  }
  else {
    \Drupal::messenger()->addMessage('You are invisible to us, or accessing from localhost :)');
  }
}
```

В препроцессе мы получаем объект текущего запроса. Из него мы пытаемся получить
заголовки X-Country и X-City, которые добавляет наш Middleware. На всякий
случай, указываем что значение по умолчанию будет `FALSE`, если что-то пошло не
так.

Если страна и город установлены (найдены), мы выводим откуда текущий
пользователь. Если по каким-то причинам информация недоступна, пишем что не
смогли определить.

![GeoPlugin пример](image/example-2.png)

[d8-services]: ../../../../2017/06/21/d8-services/index.ru.md
[d8-events]: ../../../../2018/04/10/d8-events/index.ru.md
[d8-composer]: ../../../../2016/09/03/d8-composer/index.ru.md
