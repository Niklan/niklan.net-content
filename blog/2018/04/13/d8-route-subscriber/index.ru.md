---
id: d8-route-subscriber
language: ru
title: 'Drupal 8: Route Subscriber — альтерим роуты'
created: '2018-04-13T14:27:29'
updated: '2023-10-16T18:21:20'
description: 'Как править роутеры программно используя Route Subscriber.'
attachments:
  - name: 'Готовый модуль с примерами'
    path: 'attachment/dummy.tar.gz'
promo: 'image/camera-destination-finger-7979.jpg'
tags:
  - Drupal
  - 'Drupal 8'
  - 'Route API'
---

Все адреса на сайте, так или иначе имеют собственный роут, который отвечает за
его обработку, подготовку и вывод. Иногда, может потребоваться, что какой-то
роут необходимо скорректировать под свои нужды. Одно дело если роут объявлен
вами в вашем же модуле, совсем другое, когда он объвлен сторонним модулем.
Хакать чужой код, не наш путь, мы будем делать правильно.

**Route Subscriber** — это всего лишь абстрактный класс-надстройка, который
является [Event Subscriber][d8-events]. Если подсмотреть в его код, становится
всё предельно ясно. Он подписывается на событие `RoutingEvents::ALTER`, вызывает
свой метод `onAlterRoutes()`, где он получает коллекцию роутов, и затем передает
её ещё одному методу `alterRoutes()`. Метод `alterRoutes()` является абстрактным
и объявляется в объекте-наследнике со всей логикой. Выходит, что **Route
Subscriber** это лишь базовый объект для ваших подписчиков на события альтера
роутов. Т.е. вы можете обойтись без него, но это унифицировано, и значит
пользоваться будем тем, что предоставляет ядро.

**Объявляется Route Subscriber** абсолютно идентично Event Subscriber, так как
им и является. Только, в отличии от Event Subscriber, мы не будем
реализовывать `EventSubscriberInterface`, а будем
расширять `RouteSubscriberBase`, который уже и реализует данный интерфейс.

Тут всё очень просто, основная часть
разжевана [в статье про события][d8-events], поэтому пойдем быстрым темпом и уже
перейдем к примеру. _Модуль в примере имеет название dummy._

**В качестве примера** мы напишем свой Route Subscriber, который будет заменять
стандартный путь `/user/login` на `/auth`, а также отключать доступ к
странице `/user/logout` — т.е. авторизованные пользователи не смогут выйти
штатным средством :)

Я ещё раз напоминаю, Route Subscriber = Event Subscriber, и объявляются они в
одном и том же месте: `src/EventSubscriber`.

```php {"header":"src/EventSubscriber/RouteSubscriber.php"}
<?php

namespace Drupal\dummy\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dummy route subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    /** @var \Symfony\Component\Routing\Route $route */
    if ($route = $collection->get('user.login')) {
      $route->setPath('/auth');
    }

    if ($route = $collection->get('user.logout')) {
      $route->setRequirement('_access', 'FALSE');
    }
  }

}
```

_Прошу заметить, что в данном примере, условия могут быть избыточными, так как
модуль user будет включен при любом раскладе. А для безопасности, что роут точно
есть, можно просто своему модулю указать зависимость от того, что объявляет
нужный вам роут. Для остальных, для подстраховки условие я бы все же писал._

А теперь пройдемся по порядку и разберем что к чему:

1. Первое на что обратите внимание, это то, о чём я писал чуть выше. Мы
   расширяем базовый подписчик `extends RouteSubscriberBase`, а не реализуем
   интерфейс подписчика.
2. Мы подписываемся на событие. Данный метод можно опустить, в таком случае всё
   будет работать, но вес у вызова будет равен 0. Мы же переопределяем его на
   -300, так как порядок выполнения от большего к меньшему. Чем меньше - тем
   позже вызовется. Например, все Route Subscriber из ядра имеют значения от 0
   до -210 (самый высокий, -210, у `ContentTranslationRouteSubscriber`). Это
   значит что -300 гарантированно, как минимум, переопределит все возможные
   изменения из ядра и, возможно, сторонних модулей.
3. Затем описываем метод `alterRoutes()`, который обязательно придется описать,
   так как он является абстрактным в базавом классе. В нём мы всё и меняем.

Давайте посмотрим на оригинальный роут `user.login`:

```yaml {"header":"core/modules/user/user.routing.yml"}
user.login:
  path: '/user/login'
  defaults:
    _form: '\Drupal\user\Form\UserLoginForm'
    _title: 'Log in'
  requirements:
    _user_is_logged_in: 'FALSE'
  options:
    _maintenance_access: TRUE
```

Мы видем что значение `path` установлено в `/user/login`. Мы его в своем методе,
при помощи `setPath()` метода, меняем на `/auth`.

Аналогично и с `user.logout`:

```yaml {"header":"core/modules/user/user.routing.yml"}
user.logout:
  path: '/user/logout'
  defaults:
    _controller: '\Drupal\user\Controller\UserController::logout'
  requirements:
    _user_is_logged_in: 'TRUE'
```

Только у него изначально не устаноавлено требование `_access` (на самом оно там
будет по умолчанию 'TRUE'), мы его устанавливаем на `'FALSE'`. <mark>Обратите
внимание, что булевое значение передается как строка, так как это YAML.</mark>

Всё что нам осталось, это объявить наш Route Subscriber в качестве сервиса:

```yaml {"header":"dummy.services.yml"}
services:
  dummy.route_subscriber:
    class: Drupal\dummy\EventSubscriber\RouteSubscriber
    tags:
      - { name: event_subscriber }
```

Сбрасываем кэш, и вуаля! Авторизация будет открываться по `/auth`,
а `/user/logout` будет всем отдавать 403 ошибку.

Как вы уже могли догадаться, при помощи Route Subscriber можно создавать
совершенно новые роуты — но на орге это делать не рекомендуется, для этого есть
другие инструменты, поэтому рассматривать мы такое не будем. Может как-то
отдельно, если я пойму в чем преимущество Route Subscriber
над `route_callbacks`. _Есть конечно, догадка, что это нужно, если требуется
сделать динамически роуты на основе чужих данных._

Вернемся немного назад и вставлю пару слов про объект `Route`. Как вы можете
заметить, мы ищем роут в `RouteCollection`, если он его находит, он
возвращает `Route` объект, при помощи которого мы и меняем значения, или
устанавливаем новые. Так что пробежимся быстро по его методам для этих задач:

- `setPath()`: Устанавливает путь или его шаблон для роута.
- `setHost()`*: Устанавливает хост или его шаблон для роута.
- `setSchemes()`*: Принимает строку или массив из строк, с протоколами, для
  которых данный роут доступен.
- `setMethoods()`: Принимает строку или массив из строк, с методами, которые
  доступны для данного роута. Например `['GET', 'POST']`.
- `setOptions()`: Массив с дополнительными опциями роута.
- `addOptions()`: Массив с опциями, которые нужно добавить к текущим. Если
  значения уже есть, они перезатрутся новыми.
- `setOption($name, $value)`: Устанавливает значение конкретной опции.
- `setDefaults()`: Массив со значениями по умолчанию.
- `addDefaults()`: Массив со значениями по умолчанию, которые нужно добавить к
  текущим.
- `setDefault($name, $default)`: Устанавливает значение конретного значения по
  умолчанию.
- `setRequirements()`: Массив с условиями для роута, которые должны быть
  выполнены.
- `addRequirements()`: Массив с условиями для роута, которые нужно добавить к
  текущим.
- `setRequirement($key, $regex)`: Устанавливает значение для конкретного
  требования.
- `setCondition()`*: Устанавливает условие выполнения
  роута. [Подробнее](https://symfony.com/doc/current/routing/conditions.html).

_* Методы помеченные звездочками существуют, и, скорее всего, работают. Они из
Symfony, и их использование в ядре я не нашел._

Также все эти методы имеют геттеры, чтобы вы могли получить текущие значения.

Более детальные описания для значений можно найти
на [drupal.org](https://www.drupal.org/docs/8/api/routing-system/structure-of-routes).

[d8-events]: ../../../../2018/04/10/d8-events/index.ru.md
