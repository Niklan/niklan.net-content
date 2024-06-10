---
id: d8-d9-placeholder-strategy
language: ru
title: 'Drupal 8, 9: Placeholder Strategy'
created: '2020-05-15T10:51:12'
updated: '2024-05-25T00:00:00'
description: >-
  Drupal 8, 9: Placeholder Strategy — узнайте, как оптимизировать рендер страниц
  с помощью плейсхолдеров и асинхронной обработки данных.
promo: 'image/kaleidico-26MJGnCM0Wc-unsplash.jpg'
tags:
  - Drupal
  - 'Drupal 8'
  - 'Drupal 9'
---

**Placeholder Strategy** — сервисы, отвечающие за стратегию
[рендера][d8-render-arrays] плейсхолдеров.

**Плейсхолдер** (в контексте рендера Drupal) — специальная строка, которая
вставляется на место реального результата. Она используется для того, чтобы
временно отложить обработку оригинальной логики, по тем или иным причинам, тем
самым, не блокируя основной поток обработки запроса.

PHP (в чистом виде) не асинхронный, он выполняет код последовательно. Если в
процессе обработки запроса появится «сон» на 3 секунды, то пока он не пройдёт,
всё что следует за ним — не будет выполняться. Таким образом, медленный код
тормозит весь процесс ровно на столько, насколько он медленный.

Но не всё так плохо. У нас есть множество инструментов и подходов к тому, как
решать данные проблемы. Один из данных инструментов
это — [ленивые билдеры][d8-lazy-builder].

**Ленивый билдер** (`#lazy_builder`) — позволяет производить специальную
разметку в [рендер массивах][d8-render-arrays] для тех частей сайта, где заранее
известно, что она может замедлить работу системы. Ленивые билдеры заменяются на
плейсхолдеры (если принудительно задано, или подпадают под условия), а за то,
что делать с ними и как обрабатывать, отвечает placeholder strategy!

При помощи стратегий рендера плейсхолдеров можно менять поведение ленивых
билдеров.

## Стандартные стратегии

Drupal ядро предоставляет две стратегии по умолчанию:

- `placeholder_strategy.single_flush`: Стратегия по умолчанию, работающая на
  всех сайтах и всегда. Эта стратегия передаёт всю обработку рендеру и ничего не
  меняет. Она имеет приоритет -1000, следовательно, будет выполняться самой
  последней. Она разрулит все ситуации, которые не решены другими стратегиями
  или при их отсутствии. В данной стратегии, рендер ленивых билдеров
  откладывается на конец рендера всех остальных элементов. Фактически, это
  ничего не даёт, всё остаётся на своих местах, только тяжелые операции будут
  выполнены в конце рендера.
- `placeholder_strategy.big_pipe`: Стратегия, предоставляемая одноимённым
  модулем Big Pipe. Данная стратегия не изменяет итоговую скорость загрузки
  страницы, но сильно влияет на TTFB. Все ленивые билдеры обрабатываются только
  после ответа сервера, где, вместо содержимого всё ещё плейсхолдеры. Но ответ
  (соединение) не закрывается и в него «пушатся» специальные конструкции, по
  мере готовности рендера ленивых билдеров, которые заменяют плейсхолдеры на
  значения. Это позволяет добиться максимальной скорости отдачи статического
  содержимого, без ущерба для юзера, а пока он читает статику, доходят и более
  тяжелые элементы. Данный способ работает только для пользователей с сессией и
  требует поддержки такой возможности сервером (вроде возможность HTTP\2).

:: youtube {vid=JwzX0Qv6u3A}

Как вы уже могли понять. Из коробки, если не включён Big Pipe, данная
возможность фактически не используется. А с включённым Big Pipe есть условности,
которые могут не подойти для ваших задач. Тут-то вы и можете сделать так, как
нужно только вам и вашему проекту! Зная потребности, проблемы, уязвимые места и
т.д., вы сможете создать более точные, подходящие и уместные решения конкретно
под проект.

## Создание стратегии

Итак, вы захотели создать свою стратегию. И это очень просто! Всё что вам нужно,
это создать [сервис][d8-services] [с меткой][d8-tagged-services]
`placeholder_strategy`, который реализует
`\Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface`.

Пример объекта:

```php
<?php

namespace Drupal\example;

use Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface;

/**
 * Provides placeholder strategy.
 */
final class ExamplePlaceholderStrategy implements PlaceholderStrategyInterface {

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders): array {
    return $placeholders;
  }

}
```

Сервис:

```yaml
services:
  placeholder_strategy.example:
    class: Drupal\example\ExamplePlaceholderStrategy
    tags:
      - { name: placeholder_strategy }
```

Интерфейс `PlaceholderStrategyInterface` требует реализации одного единственного
метода — `::processPlaceholders()`. В данный метод, в качестве аргумента,
приходит массив `$placeholders`, который содержит в себе информацию о всех
плейсхолдерах что создал Drupal в процессе рендера. В качестве ключа выступает
строка с плейсхолдером, который сгенерировал друпал, а в качестве значения —
рендер массив, на что заменить данный плейсхолдер в следующем цикле рендера. По
умолчанию там будет рендер массив с ленвым билдером, но стратегии могут менять
его.

Пример такого массива:

```php
$placeholders = [
  '<drupal-render-placeholder callback="example.slow_block_lazy_builder:build" arguments="" token="rN95G46IxS9VbeKtuyvSgOW60BLl5dHSdhNfTxBjBCI"></drupal-render-placeholder>' => [
    '#lazy_builder' => [
      'example.slow_block_lazy_builder:build', [],
    ],
  ],
  '<drupal-render-placeholder callback="Drupal\Core\Render\Element\StatusMessages::renderMessages" arguments="0" token="_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></drupal-render-placeholder>' => [
    '#lazy_builder' => [
      'Drupal\Core\Render\Element\StatusMessages::renderMessages', [NULL],
    ],
  ],
  'form_action_p_pvdeGsVG5zNF_XLGPTvYSKCf43t8qZYSwcfZl2uzM' => [
    '#lazy_builder' => [
      'form_builder:renderPlaceholderFormAction', [],
    ],
  ],
];
```

Ваша задача — заменить значения для интересующих вас плейсхолдеров на основе их
содержимого и вернуть новый массив. Затем, данные значения будут повторно
переданы на рендер, а плейсхолдеры будут заменены на получившиеся результаты.

А что вы уже будете делать и как — это решать вам. Тут вы свободны реализовывать
что угодно. Вы можете влиять только на определённые виды плейсхолдеров! Так, вы
можете менять поведение точечно, а не сразу на всём проекте и для всех.

Также, обратите внимание на примеры возможных плейсхолдеров выше. Важный момент
там в том, что некоторые выглядят как HTML элементы, а некоторые просто строки.
Плейсхолдеры можно задавать руками (см. статью про ленивые билдеры), но по
умолчанию, в ядре придерживаются следующему правилу:

- Плейсхолдер в качестве HTML элемента используется для каких-то общих
  результатов. Им может быть что угодно. Хоть строка, хоть большая разметка. Они
  выглядят таким образом, чтобы их было проще вставлять в HTML и хранить в кэше!
  Это поведение по умолчанию, если вы не указываете плейсхолдер руками.
- Плейсхолдер в качестве строки, как правило, используется для значений из
  аттрибутов HTML элементов. Например, плейсхолдер для форм `form_action_*`
  подставляется в `action=""` аттрибут элемента формы, следовательно, туда уже
  никакую HTML разметку в качестве результата не доставить.

Придерживайтесь этим простым правилам при обработке плейсхолдеров, и
обрабатывайте точечные значения, в поведении которых вы уверены, что оно отлично
от того что описано выше.

Это всё что нужно знать о стратегиях рендера плейсхолдеров. Поэтому, время
примера!

## Пример

В качестве примера мы сделаем ~~композитный сайт~~ свою собственную стратегию
рендера плейсхолдеров. Она будет заменять оригинальные HTML плейсхолдеры
(которые для контента) на наши собственные, а также подключать либу на страницу.
Затем, при помощи JS мы будем запрашивать результат для нашего плейсхолдера при
помощи AJAX запроса, и заменять плейсхолдер на результат.

Мы также сделаем так, что при отсутствии JavaScript у клиента, мы отдадим весь
контент без AJAX (как если бы у нас не было нашей стратегии), чтобы, например,
его смогли увидеть не только пользовали с отключенным JS, но и поисковые роботы.

Также, мы будем грузить только те данные, которые попадают в область видимости
окна браузера пользователя. Чтобы не грузились данные которые далеко в футере и
пользователь их никак не может сейчас увидеть. Или наоборот, сайт открылся
ниже (якоря или старая позиция), мы не загрузим данные из шапки. Так мы
сбалансируем нагрузку и ещё больше увеличим отзывчивость сайта, освободив main
thread JS движка браузера.

Для AJAX запросов мы будем использовать AJAX механизм Drupal с командами. Так,
наши результаты смогут подключать свои Drupal Settings, библиотеки (CSS и JS) и
при желании, добавлять свои команды в ответ.

### Стратегия плейсхолдера

Первым делом создаём нашу стратегию:

```php {"header":"src/Render/Placeholder/AjaxPlaceholderStrategy.php"}
<?php

namespace Drupal\example\Render\Placeholder;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides AJAX placeholder strategy.
 *
 * The placeholders on the page will be replaced with AJAX calls.
 */
final class AjaxPlaceholderStrategy implements PlaceholderStrategyInterface {

  /**
   * The module cookie name for no-JS mark.
   */
  public const NOJS_COOKIE = 'example_ajax_strategy_nojs';

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new AjaxPlaceholderStrategy object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders): array {
    // If client doesn't have JavaScript enabled, fallback to default response
    // with blocking rendering, but client will receive all content. F.e. search
    // engines crawlers without JS still be possible to parse content.
    if ($this->requestStack->getCurrentRequest()->cookies->has(static::NOJS_COOKIE)) {
      return $placeholders;
    }

    foreach ($placeholders as $placeholder => $placeholder_render_array) {
      // Skip processing attribute placeholders.
      // @see \Drupal\Core\Access\RouteProcessorCsrf::renderPlaceholderCsrfToken()
      // @see \Drupal\Core\Form\FormBuilder::renderFormTokenPlaceholder()
      // @see \Drupal\Core\Form\FormBuilder::renderPlaceholderFormAction()
      if (!$this->placeholderIsAttributeSafe($placeholder)) {
        $placeholders[$placeholder] = $this->createAjaxPlaceholder($placeholder_render_array);
      }
    }
    return $placeholders;
  }

  /**
   * Determines whether the given placeholder is attribute-safe or not.
   *
   * @param string $placeholder
   *   A placeholder.
   *
   * @return bool
   *   Whether the placeholder is safe for use in a HTML attribute (in case it's
   *   a placeholder for a HTML attribute value or a subset of it).
   */
  private function placeholderIsAttributeSafe($placeholder): bool {
    return $placeholder[0] !== '<' || $placeholder !== Html::normalize($placeholder);
  }

  /**
   * Creates an AJAX placeholder.
   *
   * @param array $placeholder_render_array
   *   The placeholder render array.
   *
   * @return array
   *   The renderable array with custom placeholder markup.
   */
  private function createAjaxPlaceholder(array $placeholder_render_array): array {
    $callback = $placeholder_render_array['#lazy_builder'][0];
    $args = $placeholder_render_array['#lazy_builder'][1];

    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#cache' => [
        'max-age' => 0,
      ],
      '#attributes' => [
        'data-ajax-placeholder' => Json::encode([
          'callback' => $placeholder_render_array['#lazy_builder'][0],
          'args' => $placeholder_render_array['#lazy_builder'][1],
          'token' => self::generateToken($callback, $args),
        ]),
      ],
      '#attached' => [
        'library' => ['example/ajax-placeholder'],
      ],
    ];
  }

  /**
   * Generates token for protection from random code executions.
   *
   * @param string $callback
   *   The callback function.
   * @param array $args
   *   The callback arguments.
   *
   * @return string
   *   The token that sustain across requests.
   */
  public static function generateToken(string $callback, array $args): string {
    // Use hash salt to protect token against attacks.
    $token_parts = [$callback, $args, Settings::get('hash_salt')];
    return Crypt::hashBase64(serialize($token_parts));
  }

}
```

#### Константа для куки

```php
  /**
   * The module cookie name for no-JS mark.
   */
  public const NOJS_COOKIE = 'example_ajax_strategy_nojs';
```

В данной константе мы храним название куки, которая будет добавляться
пользователям если у них отключен или недоступен JavaScript.

#### Конструктор

```php
  /**
   * Constructs a new AjaxPlaceholderStrategy object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }
```

В конструкторе мы принимаем зависимости нашего сервиса. Нам потребуется
только `request_stack` — им можно получать куки.

#### Генерация токена

```php
  /**
   * Generates token for protection from random code executions.
   *
   * @param string $callback
   *   The callback function.
   * @param array $args
   *   The callback arguments.
   *
   * @return string
   *   The token that sustain across requests.
   */
  public static function generateToken(string $callback, array $args): string {
    // Use hash salt to protect token against attacks.
    $token_parts = [$callback, $args, Settings::get('hash_salt')];
    return Crypt::hashBase64(serialize($token_parts));
  }
```

Drupal предоставляет свой токен для плейсхолдеров в виде HTML элементов. Это
base64 сериализованных данных функции обратного вызова и аргументов. Фактически
один в один как у нас. Но зачем нам свой?

Дело в том, что стандартный используется для каких-то примитивных вещей. В нашем
случае, колбек и аргументы для него будут передаваться при помощи AJAX, они
будут вызываться и отдавать результат. Это очевидная дыра в безопасности. Никак
не защитив такое поведение, можно будет вызывать `eval()` с нужным аргументами
и — «Здравствуйте, нас взломали».

Для более усиленной защиты, помимо функции обратного вызова и аргументов, мы
также добавляем соль (`hash_salt`), которая у каждой инсталяции друпала своя, и
её можно менять, и уже затем сериализуем и превращаем в base64. Так, не зная
соли, злоумышленник не сможет создать валидный для нас токен в соответствии с
переданными значениями для колбека и аргументов, а значит, мы такой вызов не
обработаем.

Метод мы делаем статическим, так как он не требует никаких зависимостей, и
пригодится в нескольких местах нашего кода. Тащить стратегию целиком нет
никакого смысла.

#### Генерация собственного плейсхолдера для результата

```php
  /**
   * Creates an AJAX placeholder.
   *
   * @param array $placeholder_render_array
   *   The placeholder render array.
   *
   * @return array
   *   The renderable array with custom placeholder markup.
   */
  private function createAjaxPlaceholder(array $placeholder_render_array): array {
    $callback = $placeholder_render_array['#lazy_builder'][0];
    $args = $placeholder_render_array['#lazy_builder'][1];

    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#cache' => [
        'max-age' => 0,
      ],
      '#attributes' => [
        'data-ajax-placeholder' => Json::encode([
          'callback' => $placeholder_render_array['#lazy_builder'][0],
          'args' => $placeholder_render_array['#lazy_builder'][1],
          'token' => self::generateToken($callback, $args),
        ]),
      ],
      '#attached' => [
        'library' => ['example/ajax-placeholder'],
      ],
    ];
  }
```

Данный метод принимает в качестве аргумента массив-значение плейсхолдера,
который передаёт нам друпал для обработки. Из него мы получаем функцию обратного
вызова и аргументы для неё.

Затем мы генерируем рендер массив со `<span>` элементом, который будет на
странице вместо плейсхолдера. А также подключаем библиотеку с нашим JS, который
мы напишем чуть позже.

Примерный результат, который можно будет наблюдать на странице с такой
стратегией:

```html
<span data-ajax-placeholder="{&quot;callback&quot;:&quot;example.slow_block_lazy_builder:build&quot;,&quot;args&quot;:[],&quot;token&quot;:&quot;dXani8FiRrPyb_5IdLcaYrd_1X7ngGmb1FT_n9b19lQ&quot;}"></span>
```

Данные элементы будут искаться нашим JS, и данные о функции обратного вызова,
аргументах и токене будут передаваться в AJAX для получения результата.

#### Проверка на аттрибут

```php
  /**
   * Determines whether the given placeholder is attribute-safe or not.
   *
   * @param string $placeholder
   *   A placeholder.
   *
   * @return bool
   *   Whether the placeholder is safe for use in an HTML attribute (in case it's
   *   a placeholder for an HTML attribute value or a subset of it).
   */
  private function placeholderIsAttributeSafe($placeholder): bool {
    return $placeholder[0] !== '<' || $placeholder !== Html::normalize($placeholder);
  }
```

Данный метод в качестве аргумента принимает оригинальный плейсхолдер друпала
(ключ в массиве). Мы проверяем, не является ли строка значением для аттрибута.

Это необходимо для того, чтобы мы не подгружали AJAX значения для плейсхолдеров
которые находятся внутри аттрибутов. Например, экшен для формы.

#### Обработка плейсхолдеров

```php
  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders): array {
    // If client doesn't have JavaScript enabled, fallback to default response
    // with blocking rendering, but client will receive all content. F.e. search
    // engines crawlers without JS still be possible to parse content.
    if ($this->requestStack->getCurrentRequest()->cookies->has(static::NOJS_COOKIE)) {
      return $placeholders;
    }

    foreach ($placeholders as $placeholder => $placeholder_render_array) {
      // Skip processing attribute placeholders.
      // @see \Drupal\Core\Access\RouteProcessorCsrf::renderPlaceholderCsrfToken()
      // @see \Drupal\Core\Form\FormBuilder::renderFormTokenPlaceholder()
      // @see \Drupal\Core\Form\FormBuilder::renderPlaceholderFormAction()
      if (!$this->placeholderIsAttributeSafe($placeholder)) {
        $placeholders[$placeholder] = $this->createAjaxPlaceholder($placeholder_render_array);
      }
    }
    return $placeholders;
  }
```

Последний, и самый главный метод — обработка плейсхолдеров.

Первым делом мы проверяем, есть ли кука об отсутствии JS у пользователя, и если
она имеется, мы не трогаем оригинальные плейхсолдеры и сразу отдаём их как
результат. Это значит, что страница обработается в обход нашей логики и будет
возвращён результат от следующей активной стратегии. Если больше никаких
стратегий нет, значит контент отрендерится в основном потоке.

Если куки нет, мы проходимся по каждому плейсхолдеру, и если он является HTML
плейсхолдером, заменяем его на наш рендер массив, который превратится затем
в `<span>`. Изменённый массив возвращаем в качестве результата.

После чего регистрируем наш класс как сервис с меткой `placeholder_strategy`.

```yaml {"header":"example.services.yml"}
services:
  placeholder_strategy.ajax_example:
    class: Drupal\example\Render\Placeholder\AjaxPlaceholderStrategy
    arguments: ['@request_stack']
    tags:
      - { name: placeholder_strategy }
```

### Контроллер и маршруты

После того как мы написали свою стратегию, нам нужно подготовить контроллер и
маршруты.

Нам потребуется два маршрута:

- Маршрут для ответа на AJAX запросы, который будет генерировать ответ с данными
  для замены нашего плейсхолдера реальным результатом.
- Маршрут, который будет добавлять куку об отсутствии JS у пользователя. Мы не
  сможем добавить эту куку на фронте, так как JS будет отключен.

```php {"header":"src/Controller/AjaxPlaceholderController.php"}
<?php

namespace Drupal\example\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\example\Render\Placeholder\AjaxPlaceholderStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides controller implementations for Ajax Placeholder strategy.
 */
final class AjaxPlaceholderController implements ContainerInjectionInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer|object|null
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AjaxPlaceholderController {
    $instance = new static();
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Handles request with no JS enabled client.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function noJsCookie(Request $request): Response {
    if ($request->cookies->has(AjaxPlaceholderStrategy::NOJS_COOKIE)) {
      throw new AccessDeniedException();
    }

    if (!$request->query->has('destination')) {
      throw new HttpException(Response::HTTP_BAD_REQUEST, 'The original location is missing.');
    }

    $response = new LocalRedirectResponse($request->query->get('destination'));
    // Set cookie without httpOnly, so that JavaScript can delete it.
    $response->headers->setCookie(new Cookie(AjaxPlaceholderStrategy::NOJS_COOKIE, TRUE, 0, '/', NULL, FALSE, FALSE, FALSE, NULL));
    $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['cookies:' . AjaxPlaceholderStrategy::NOJS_COOKIE]));
    return $response;
  }

  /**
   * Handles request from AJAX and returns result.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The AJAX request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The AJAX response.
   */
  public function process(Request $request): Response {
    $json = $request->getContent();
    $info = Json::decode($json);
    $callback = $info['callback'];
    $args = $info['args'];
    $token = $info['token'];

    // @see \Drupal\Core\Ajax\AjaxResponseAttachmentsProcessor
    $response = new AjaxResponse();
    if ($this->validateToken($callback, $args, $token)) {
      // @see \Drupal\Core\Render\Renderer::doCallback
      $render_array = [
        '#lazy_builder' => [$callback, $args],
        '#create_placeholder' => FALSE,
      ];
      $html = $this->renderer->renderRoot($render_array);
      $response->setAttachments($render_array['#attached']);

      // The placeholder will be replaced only if there is a result. If result
      // is empty (callback returns nothing or rendering doesn't provide HTML)
      // then we remove placeholder from the page.
      if (!empty($html)) {
        $response->addCommand(new ReplaceCommand(NULL, $html));
      }
      else {
        $response->addCommand(new RemoveCommand(NULL));
      }
    }
    else {
      $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    return $response;
  }

  /**
   * Validates that provided token in payload is valid.
   *
   * Since this controller response for every POST request and execute code,
   * we must reduce possible thread income. The very first and simple solution
   * is to validate token from placeholder with what is actually expected.
   *
   * The token uses site 'salt' and can't be compromise if 'salt' is not leaked.
   * By this token we only allows callbacks that we expect. If callback or any
   * argument will be different from what we expect, the token will be
   * different.
   *
   * @param string $callback
   *   The callback function.
   * @param array $args
   *   The callback arguments.
   * @param string $provided_token
   *   The payload token.
   *
   * @return bool
   *   Whether token is valid and data is valid.
   */
  private function validateToken(string $callback, array $args, string $provided_token): bool {
    return AjaxPlaceholderStrategy::generateToken($callback, $args) == $provided_token;
  }

}
```

#### Dependency Injection

```php
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AjaxPlaceholderController {
    $instance = new static();
    $instance->renderer = $container->get('renderer')
    return $instance;
  }
```

Внедряем зависимость `renderer`. Так как лейзи билдеры отдают рендер массивы,
для получения HTML разметки нам нужно будет их отрендерить.

#### Добавление куки

```php
  /**
   * Handles request with no JS enabled client.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function noJsCookie(Request $request): Response {
    if ($request->cookies->has(AjaxPlaceholderStrategy::NOJS_COOKIE)) {
      throw new AccessDeniedException();
    }

    if (!$request->query->has('destination')) {
      throw new HttpException(Response::HTTP_BAD_REQUEST, 'The original location is missing.');
    }

    $response = new LocalRedirectResponse($request->query->get('destination'));
    // Set cookie without httpOnly, so that JavaScript can delete it.
    $response->headers->setCookie(new Cookie(AjaxPlaceholderStrategy::NOJS_COOKIE, TRUE, 0, '/', NULL, FALSE, FALSE, FALSE, NULL));
    $response->addCacheableDependency((new CacheableMetadata())->addCacheContexts(['cookies:' . AjaxPlaceholderStrategy::NOJS_COOKIE]));
    return $response;
  }
```

Метод `::noJsCookie()` будет добавлять куку в случае если обратятся по маршруту.

Мы будем выдавать ошибку доступа, если кука уже есть, чтобы лишний раз ничего не
делать. Если в query параметре не переден `destination`, мы будем вызывать
исключение.

В случае если проверки прошли, мы подготавливаем ответ редиректа
по `?destination`, добавляем куку, добавляем [кэш-контекст][d8-cache-metadata],
чтобы ответ имел разное поведение в зависимости от наличия куки и отвечаем.

И немного про `destination`. Логика у нас будет простая. Если куки нет и
отсутствует JS, мы будем перебрасывать пользователя на данный маршрут, передавая
откуда он был отправлен. Установив куку на стороне бэкенда, мы вернём его туда,
откуда он пришёл. Так, для пользователя это будет выглядеть буд-то страница
быстро обновилась и всё.

Также, регистрируем этот метод как маршрут:

```yaml {"header":"example.routing.yml"}
example.ajax_nojs:
  path: /ajax-placeholder-nojs
  defaults:
    _controller: \Drupal\example\Controller\AjaxPlaceholderController::noJsCookie
  options:
    no_cache: TRUE
  requirements:
    _access: 'TRUE'
```

#### Обработка AJAX запросов

Данный контроллер также отвечает за обработку поступивших с фронта AJAX запросов
и ответа с готовым результатом.

##### Валидация токена

```php
  /**
   * Validates that provided token in payload is valid.
   *
   * Since this controller response for every POST request and execute code,
   * we must reduce possible thread income. The very first and simple solution
   * is to validate token from placeholder with what is actually expected.
   *
   * The token uses site 'salt' and can't be compromise if 'salt' is not leaked.
   * By this token we only allows callbacks that we expect. If callback or any
   * argument will be different from what we expect, the token will be
   * different.
   *
   * @param string $callback
   *   The callback function.
   * @param array $args
   *   The callback arguments.
   * @param string $provided_token
   *   The payload token.
   *
   * @return bool
   *   Whether token is valid and data is valid.
   */
  private function validateToken(string $callback, array $args, string $provided_token): bool {
    return AjaxPlaceholderStrategy::generateToken($callback, $args) == $provided_token;
  }
```

В данном методе мы сравниваем токен который прислали нам в запросе, с тем, что
должен получиться на основе переданных аргументов и функции обратного вызова. Он
вернёт `TRUE`, если токен для переданных данных корректный, в противном
случае `FALSE`. Для генерации токена мы используем метод из нашей стратегии.

Так, мы убеждаемся что данные не подверглись изменению или не пытаются
произвести вызов того, чего мы не ожидаем.

##### Обработка запроса

```php
  /**
   * Handles request from AJAX and returns result.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The AJAX request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The AJAX response.
   */
  public function process(Request $request): Response {
    $json = $request->getContent();
    $info = Json::decode($json);
    $callback = $info['callback'];
    $args = $info['args'];
    $token = $info['token'];

    // @see \Drupal\Core\Ajax\AjaxResponseAttachmentsProcessor
    $response = new AjaxResponse();
    if ($this->validateToken($callback, $args, $token)) {
      // @see \Drupal\Core\Render\Renderer::doCallback
      $render_array = [
        '#lazy_builder' => [$callback, $args],
        '#create_placeholder' => FALSE,
      ];
      $html = $this->renderer->renderRoot($render_array);
      $response->setAttachments($render_array['#attached']);

      // The placeholder will be replaced only if there is a result. If result
      // is empty (callback returns nothing or rendering doesn't provide HTML)
      // then we remove placeholder from the page.
      if (!empty($html)) {
        $response->addCommand(new ReplaceCommand(NULL, $html));
      }
      else {
        $response->addCommand(new RemoveCommand(NULL));
      }
    }
    else {
      $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    return $response;
  }
```

Обработка запроса простая — мы получаем данные, проверяем токен на валидность и
если токен валидный, вызываем функцию обратного вызова с аргументами, а её
результат (HTML), мы отдаём как команду для AJAX.

Один из моментов на что тут стоит обратить внимание — это то, как производится
вызов функции обратного вызова через рендер. Мы формируем новый ленивый билдер
на основе полученных данных, но принудительно отключаем создание плейсхолдера,
таким образом, данный лейзи билдер сразу уйдёт на обработку и пропустит все
placeholder strategy.

Более того, функция обратного вызова может иметь вид `[service]:[method]` (и мы
это сделаем далее по примеру) и простым `call_user_func()` её не обработать.
Поэтому мы готовим ожидаемый рендер массив для друпала и сразу отдаём на рендер,
а в результат получаем готовую HTML разметку результата.

Далее, добавляем из модифицированного `$render_array` все аттачменты
(библиотеки, drupal settings) в ответ. Данное значение всегда будет после
рендера, либо пустое, либо со значениями, поэтому мы смело сразу к нему
обращаемся.

После чего мы проводим проверку на результат HTML. Если он пустой, мы добавляем
AJAX команду удаления элемента (плейсхолдера), а если имеется, его замены. В
качестве `selector` мы передаём `NULL`, ибо мы в своём JS подставим конкретный
плейсхолдер элемент, чтобы его не пришлось повторно искать в DOM, усложнять
логику, увеличивать нагрузку на JS движок.

У вас, возможно, возникнет вопрос, почему мы добавляем аттачменты независимо от
результата? Результат хоть может быть и пустой, но функция обратного вызова всё
же может запросить библиотеки или передать своё состояние
через `drupalSettings`, чтобы оповестить остальных на странице, что результат
был пустой. Иными словами, пустой HTML результат для нас не равен пустому
ответу. Он может быть и не HTML, например, лейзи билдер подтянет только JS, без
разметки.

Получившийся ответ мы отсылаем клиенту, а его уже перехватит JS.

Нам лишь остаётся объявить маршрут для этой логики:

```yaml {"header":"example.routing.yml"}
example.ajax_processor:
  path: /ajax-placeholder-processor
  defaults:
    _controller: \Drupal\example\Controller\AjaxPlaceholderController::process
  options:
    no_cache: TRUE
  requirements:
    _access: 'TRUE'
```

### Определяем отключенный JavaScript

Мы уже имеем логику, которая проверяет наличие или отсутствие куки у
пользователя. Если у пользователя есть кука — значит отключен JavaScript, если
нет — включён. Зная это мы меняем поведение стратегии.

По умолчанию данной куки ни у кого не будет. Следовательно, нам нужно установить
куку пользователю, если его JS отключен. Маршрут для добавления у нас есть, а
значит, если у пользователя нет JS, нам нужно туда направить пользователя, и ему
будет установлена кука. Но мы не можем со 100% уверенностью сказать есть ли у
пользователя JS или нет, это знает только браузер. На помощь нам придёт метатег.

В спецификации HTML есть возможность
задать [метатег](https://developer.mozilla.org/ru/docs/Web/HTML/Element/meta) `http-equiv`,
при помощи которого можно произвести обновление страницы или произвести редирект
на уровне браузера. Также мы можем воспользоваться `<noscript>` обёрткой,
значение внутри которой будет выполнено только при отсутствии JS.

Сложив 2+2 мы можем провернуть финт, что данный метатег сработает только тогда,
когда у пользователя отключен JS, а когда сработает метатег, он произведёт
соответствующий редирект на наш маршрут, а маршрут вернёт пользователя обратно,
но уже с кукой! Для пользователя это будет практически бесшовно и быстро, а для
нас 100% уверенность в отсутствии JS.

В HTML это выглядит так:

```html

<noscript>
    <meta http-equiv="Refresh"
          content="0; URL=/ajax-placeholder-nojs?destination=/node"/>
</noscript>
```

Но если мы добавим куку, что нет JS, но JS появится в последующих заходах — то
наша стратегия по прежнему будет отключена. Следовательно, нам нужен механизм,
который удалит куку если JS появится.

Мы приходим к тому, что информацию о JS, опять, знает только браузер. Поэтому мы
сделаем наоборот, если есть кука об отсутствии JS, мы будем
добавлять `<script>`, который будет удалять куку. И как только JS появится, кука
будет удалена и заработает наша стратегия.

Так мы сделаем переходы между разными состояниями и ситуациями максимально
простыми и бесшовными.

Для этого нам потребуется реализовать эту логику, и в этом деле нам
поможет `hook_page_attachments()`.

```php {"header":"example.module"}
/**
 * Implements hook_page_attachments().
 */
function example_page_attachments(array &$attachments) {
  $attachments['#cache']['contexts'][] = 'cookies:' . AjaxPlaceholderStrategy::NOJS_COOKIE;

  $request = Drupal::request();
  $has_nojs_cookie = $request->cookies->has(AjaxPlaceholderStrategy::NOJS_COOKIE);

  if (!$has_nojs_cookie) {
    // When user has nojs cookie, we add special metatag which will be executed
    // by browser if JavaScript support is disabled. This will redirect user to
    // the special page which will put this cookie. The cookie will mark for us
    // that JS is not enabled and prevent from infinity redirect loop here.
    // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/meta
    // @see \Drupal\example\Controller\AjaxPlaceholderController::noJsCookie
    $attachments['#attached']['html_head'][] = [
      [
        '#tag' => 'meta',
        '#noscript' => TRUE,
        '#attributes' => [
          'http-equiv' => 'Refresh',
          'content' => '0; URL=' . Url::fromRoute('example.ajax_nojs', [], ['query' => Drupal::service('redirect.destination')->getAsArray()])->toString(),
        ],
      ],
      'example_ajax_nojs',
    ];
  }
  else {
    // If JavaScript is enabled and cookie is set, force delete it.
    $attachments['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#value' => 'document.cookie = "' . AjaxPlaceholderStrategy::NOJS_COOKIE . '=1; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT"',
      ],
      'example_ajax_nojs',
    ];
  }
}
```

В реализации хука мы первым делом добавляем для всех таких страниц кэш-контекст
по нашей куке. Ведь хедеры будут разные при таком поведении. Заодно это решит
вопрос кэширования страниц для разных состояний стратегии.

После чего, мы проверяем наличие куки, и в зависимости от результата, добавляем
либо `<noscript>` + `<meta>` с редиректом на наш маршрут, либо `<script>` с
удалением куки.

### Пишем JavaScript для AJAX подгрузки

У нас уже имеется подключение библиотеки `example/ajax-placeholder` в стратегии,
но библиотеки пока нет. В этом разделе мы её и создадим.

Для этого нам потребуется JS, который будет ловить все наши плейсхолдеры и
делать AJAX запросы на созданный уже маршрут.

```javascript {"header":"js/ajax-placeholder.js"}
/**
 * @file
 * AJAX placeholder strategy behaviors.
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.exampleAjaxPlaceholderStrategy = {
    attach: function (context, settings) {
      const intersectionObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const placeholderElement = entry.target
            intersectionObserver.unobserve(placeholderElement);
            this.load(placeholderElement);
          }
        })
      })

      $('[data-ajax-placeholder]', context).once('ajax-placeholder').each(function (placeholderElement) {
        intersectionObserver.observe(this);
      })
    },

    load: function (placeholderElement) {
      const ajax = new Drupal.ajax({
        url: '/ajax-placeholder-processor',
        progress: false,
        submit: placeholderElement.dataset.ajaxPlaceholder,
      })

      ajax.success = function (response, status) {
        // Call all provided AJAX commands.
        Object.keys(response || {}).forEach(i => {
          if (response[i].command && this.commands[response[i].command]) {
            if (!response[i].selector) {
              // Set selector by our element.
              response[i].selector = placeholderElement;
            }
            this.commands[response[i].command](this, response[i], status);
          }
        });
      };

      ajax.execute();
    },

    htmlStringToElement: function (htmlString) {
      htmlString = htmlString.trim();
      const template = document.createElement('template');
      template.innerHTML = htmlString;
      return template.content.firstChild;
    },
  };

})(jQuery, Drupal);
```

#### Конвертация HTML строки в DOM

```javascript
htmlStringToElement: function (htmlString) {
  htmlString = htmlString.trim();
  const template = document.createElement('template');
  template.innerHTML = htmlString;
  return template.content.firstChild;
},
```

Наш маршрут будет отвечать HTML разметкой в виде строки, просто так мы её не
можем вставить на страницу. Поэтому добавляем небольшую функцию, которая будет
конвертировать строку в полноценный DOM элемент с дочерними элементами.

#### AJAX запрос

```javascript
load: function (placeholderElement) {
  const ajax = new Drupal.ajax({
    url: '/ajax-placeholder-processor',
    progress: false,
    submit: placeholderElement.dataset.ajaxPlaceholder,
  })

  ajax.success = function (response, status) {
    // Call all provided AJAX commands.
    Object.keys(response || {}).forEach(i => {
      if (response[i].command && this.commands[response[i].command]) {
        if (!response[i].selector) {
          // Set selector by our element.
          response[i].selector = placeholderElement;
        }
        this.commands[response[i].command](this, response[i], status);
      }
    });
  };

  ajax.execute();
},
```

Данная функция будет производить загрузку при помощи Drupal AJAX запросов.

В ней мы переопределяем функцию обратного вызова для `success` аякса. Мы
дорабатываем вызов AJAX так, что если `selector` у команды не задан (мы отвечаем
с `NULL` селекторами в контроллере), то туда сразу подставится элемент
плейсхолдера, чтобы не искать его по DOM.

После того как сформировали AJAX запрос, мы его вызываем, а дальше всё сделает
Drupal.

#### Наблюдаем и загружаем

```javascript
attach: function (context, settings) {
  const intersectionObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const placeholderElement = entry.target
        intersectionObserver.unobserve(placeholderElement);
        this.load(placeholderElement);
      }
    })
  })

  $('[data-ajax-placeholder]', context).once('ajax-placeholder').each(function (placeholderElement) {
    intersectionObserver.observe(this);
  })
},
```

`attach` — стандартная функция вызываемая для всех бихейворов. В ней мы находим
все наши плейсхолдеры по аттрибуту `data-ajax-placeholder` и передаем их
обзерверу.

Внутри обзервера мы снимаем наблюдение за элементом и передаем его на загрузку
при помощи AJAX.

Благодаря обзерверам вызовы будут происходить только тогда, когда элемент
попадёт в область видимости браузера.

#### Объявляем библиотеку

Мы уже знаем что библиотека будет иметь название `example/ajax-placeholder`,
поэтому просто регистрируем JS выше:

```yaml {"header":"example.libraries.yml"}
ajax-placeholder:
  version: VERSION
  js:
    js/ajax-placeholder.js: { }
  dependencies:
    - core/drupal
    - core/drupal.ajax
```

### Замедляем Drupal

Всё готово и в текущем состоянии будет работать, как и ожидается. Чтобы заметить
его работу — нужно замедлять Drupal.

Для тестов мы создадим блок, который будет слипать обработку запроса на 3
секунды, а также делать случайную выборку 20 материалов из 5000. Так мы увидим
как работает со стратегий и без, а также, что данные каждый раз новые, даже с
включенным кэшем (не забывайте что лейзи билдеры в таком случае сами отвечают за
свой кэш).

#### Сервис с медленной логикой

Так как мы заранее знаем что блок будет отдавать своё содержимое через ленивые
билдеры, мы выносим логику в отдельный сервис, для DI и удобства.

```php {"header":"src/SlowBlockLazyBuilder.php"}
<?php

namespace Drupal\example;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides lazy builder for slow block content.
 */
final class SlowBlockLazyBuilder {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new SlowBlockLazyBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * Build content with noticeable delay.
   *
   * @return array
   *   The renderable array result for lazy builder.
   */
  public function build(): array {
    sleep(3);

    $node_ids = $this->nodeStorage->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', 'article')
      ->range(0, 20)
      ->addTag('example_random')
      ->execute();
    $nodes = $this->nodeStorage->loadMultiple($node_ids);

    return array_map(function (NodeInterface $node) {
      return [
        '#type' => 'container',
        'link' => $node->toLink()->toRenderable(),
      ];
    }, $nodes);
  }

}
```

##### Конструктор

```php
  /**
   * Constructs a new SlowBlockLazyBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }
```

В качестве Dependency Injection у нас будет `entity_type.manager`. Из него мы
получаем хранилище `node`, который, затем будет использован для запросов.

##### Построение результата

```php
  /**
   * Build content with noticeable delay.
   *
   * @return array
   *   The renderable array result for lazy builder.
   */
  public function build(): array {
    sleep(3);

    $node_ids = $this->nodeStorage->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('type', 'article')
      ->range(0, 20)
      ->addTag('example_random')
      ->execute();
    $nodes = $this->nodeStorage->loadMultiple($node_ids);

    return array_map(function (NodeInterface $node) {
      return [
        '#type' => 'container',
        'link' => $node->toLink()->toRenderable(),
      ];
    }, $nodes);
  }
```

В данном методе мы сразу же останавливаем обработку на 3 секунды. Это
гарантированный тормоз обработки.

После чего получаем ID 20 материалов типа «статья» и загружаем их сущности. А
затем формируем рендер массив из контейнеров, внутри которых будут ссылки на эти
материалы. Контейнер нам нужен лишь для того чтобы каждая ссылка была с новой
строки.

Обратите внимание на `->addTag('example_random')`. Запросы сущностей из коробки
не поддерживают случайную выборку, поэтому мы добавляем тэг к запросу, и всем
запросам с таким тегом добавляем случайную выборку на уровне запроса:

```php {"header":"example.module"}
/**
 * Implements hook_query_TAG_alter() for 'example_random'.
 */
function example_query_example_random_alter(AlterableInterface $query) {
  $query->orderRandom();
}
```

##### Объявляем сервис

```yaml {"header":"example.services.yml"}
  example.slow_block_lazy_builder:
    class: Drupal\example\SlowBlockLazyBuilder
    arguments: ['@entity_type.manager']
```

Объявляем наш билдер как сервис с нужными зависимостями. Данный сервис будет
являться функцией обратного вызова в блоке.

#### Блок

```php {"header":"src/Plugin/Block/SlowBlock.php"}
<?php

namespace Drupal\example\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a slow block.
 *
 * @Block(
 *   id = "example_slow_block",
 *   admin_label = @Translation("Slow block"),
 *   category = @Translation("Custom")
 * )
 */
final class SlowBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // Rand argument to exclude from internal caching.
    $rand = rand(0, 1000);
    $build['content'] = [
      '#lazy_builder' => ['example.slow_block_lazy_builder:build', [$rand]],
      '#create_placeholder' => TRUE,
    ];
    return $build;
  }

}
```

С блоком всё просто. Мы указываем наш сервис как функцию обратного вызова, без
аргументов и принудительно создаём плейсхолдер. Чтобы, независимо от ситуации,
он всегда проходил через плейсхолдеры, а следовательно, через нашу стратегию.

Для того чтобы для блоков были созданы разные плейсхолдеры (токены) и каждый
блок тормозил индивидуально, мы просто докидываем случайное число в качестве
аргумента.

### Проверки

Для проверки, экземпляры данного блока были добавлены в левый сайдбар и под
основное содержимое (вне области видимости).

#### Без стратегии (стандартно)

:: video [Ajax Placeholder Strategy (disabled)] (video/without-placeholder-strategy.mp4)

Данный пример демонстрирует работу страницы без нашей стратегии. В таком случае
применяется стандартная, которая откладывает наши блоки на конец обработки
запроса. Очевидный результат: 2 блока, в каждом по 3 секунды слип + запросы +
основная обработка 6+ секунд ожидается как минимум.

Как видно из демонстрации. Итого, страница полностью была готова через 7 секунд
и 150 миллисекунд. Из которых друпал отвечал 6 секунд (2х3 сек сон) и 970
миллисекунд (на всё остальное).

Также себя будет вести тест если юзер будет без JS.

#### Со стратегией

:: video [Ajax Placeholder Strategy (enabled)] (video/with-placeholder-strategy.mp4)

Со стратегией ситуация резко меняется. Наши тяжелые блоки вместо контента отдают
только плейсхолдеры и JS для их обработки. После того как страница загружается,
и первый блок попадает в область видимости (сразу), начинается AJAX запрос его
данных, который длится 3 секунды (слип) и 40 миллисекунд (на запрос) и рендер.
Как только сервер отвечает, JS выполняет команды и мы видим результат.

Обратите внимание, когда начинается загрузка второго блока.

## Итог

В итоге мы имеем два разных результата, и они очень сильно разнятся (из-за
серьезного искусственного замедления). Ответ друпала сократился с 6.9 секунд до
10 миллисекунд. Пользователь уже видит сайт и может им пользоваться. А тяжелые
элементы подгружаются в фоне. Более того, часть из них не грузится вообще, пока
не попадёт в область браузера. Так, на страницах где может быть множество
тяжелых элементов которые сложно кэшировать (например просмотренные товары
пользователем), можно подгружать таким способом. А пока пользователь не
долистает до них, у него и вовсе ничего грузиться не будет, а серверу
полегчает — он не будет готовить содержимое для того, что не нужно юзеру.

**Обратите внимание** что это демонстрация работы стратегий и их
применения, <mark>это не готовый для использования модуль</mark>. Он не
тестировался на реальных проектах, его задача другая. Поэтому не тащите его на
прод. Если вдруг захотите взять за основу — тщательно проверьте что всё работает
как и должно.

## Ссылки

- [Исходный код примера](https://github.com/Niklan/niklan.net-examples/tree/master/blog/213)
- [Drupal 8: Рендер массивы и их рендеринг][d8-render-arrays]
- [Drupal 8: Сервисы с метками][d8-tagged-services]
- [Drupal 8: Сервисы][d8-services]
- [Drupal 8: #lazy_builder — ленивый билдер][d8-lazy-builder]
- [Drupal 8: #cache — cache tags, context и max-age][d8-cache-metadata]
- [Drupal 8: Libraries API (Добавление CSS/JS на страницы)][drupal-8-libraries-api]

[d8-services]: ../../../../2017/06/21/d8-services/index.ru.md
[d8-cache-metadata]: ../../../../2017/07/15/d8-cache-tags-context-max-age/index.ru.md
[d8-lazy-builder]: ../../../../2017/07/07/d8-lazy-builder/index.ru.md
[d8-tagged-services]: ../../../../2019/05/05/d8-tagged-services/index.ru.md
[d8-render-arrays]: ../../../../2020/02/05/d8-render-arrays/index.ru.md
[drupal-8-libraries-api]: ../../../../2015/10/15/drupal-8-libraries-api/index.ru.md
