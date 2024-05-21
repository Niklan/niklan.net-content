---
id: d8-user-data-service
language: ru
title: 'Drupal 8: Сервис user.data — хранилище данных пользователей'
created: '2019-05-02T19:44:51'
updated: '2023-10-16T18:21:20'
description: '"Ключ-значение" хранилище для пользователей.'
attachments:
  - name: 'Модуль с примером user.data'
    path: 'attachment/dummy.tar__2.gz'
promo: 'image/factory-man-planks-1797415.jpg'
tags:
  - Drupal
  - 'Drupal 8'
  - Services
---

**user.data** — сервис, который добавляет хранилище где можно хранить
какие-угодно данные пользователей.

Данный сервис очень похож по своему принципу и работе на
[State API][d8-state-api], с тем отличием, что он больше заточен под хранение
данных для пользователя и тесно связан с ними.

Может возникнуть резонный вопрос, а зачем этот сервис, когда есть State API,
темболее, если их принцип работы практически не отличается:

1. Сервис user.data предназначен для хранения данных пользователей, тогда как
   State API является хранилищем общего назначения.
1. Если вы желаете сохранить какие-то данные привязанные к конкретному
   пользователю, значит вам, скорее всего, нужен user.data, нежели State API.
   Так вам не придется выдумывать ключи и заботиться об их удалении в
   дальнейшем.
1. user.data чистит хранилище пользователя, при его удалении, так у вас не
   останутся неиспользуемые данные в БД, и вам не нужно об этом даже думать.

Резюмируя: используйте user.data, если хотите хранить данные с привязкой к
пользователю по его UID, в иных случаях используйте State API.

Также стоит отметить то, что использование user.data в некоторых случаях не
только перекрывает необходимость использования полей, но даже имеет ряд
преимуществ над ними. А именно — <mark>user.data не модифицирует сущность
пользователя</mark>, тем самым, сохраняя данные в user.data, вы не запускаете
процесс сохранения сущности, который производит инвалидацию кэш-тегов, а они, в
свою очередь, инвалидируют все данные, которые так или иначе связаны с
пользователем.

Таким образом, user.data более благоприятный инструмент для производительности
сайта, так как не будет инвалидировать кэш. Вероятнее всего, данные что вы
будете в нем хранить и не должны его инвалидировать. Этот сервис идеальное
решение для хранения настроек пользователя.

В Drupal данный сервис используется лишь одним единственным модулем — contact.
При его активации он сохраняет настройки персональных контактных форм для
каждого пользователя в данное хранилище, не создавая для этого отдельное поле, и
не используя State API.

## Структура UserData

В данном разделе мы разберемся со всеми методами данного сервиса.

### set()

Метод `set()` отвечает за сохранения определенного значения в хранилище. Он
принимает следующие обязательные аргументы:

- `$module`: Машинное название модуля, который сохраняет настройки в хранилище.
- `$uid `: Идентификатор пользователя, которому принадлежит сохраняемое
  значение.
- `$name`: Машинное название для сохраняемой настроки. Иными словами - ключ.
- `$value`: Любое значение, которое вы хотите сохранить.

```php {"header":"Пример"}
$user_data = \Drupal::service('user.data');
$user_data->set('my_module', 1, 'key', [
  'some' => 'value',
  'foo' => 'bar',
]);
```

### get()

Метод `get()` отвечает за получение данных из хранилища.

Принимает следующие аргументы:

- `$module`: Машинное название модуля, чьи данные необходимо получить.
- `$uid`: (опционально) Идентификатор пользователя, данный которого необходимо
  получить.
- `$name`: (опционально) Машинное название настройки, значение которого
  необходимо получить.

Обратите внимание на то, что лишь `$module` является обязательным аргументом. В
зависимости от того, как вы укажите `$uid` и `$name` можно добиться различных
результатов.

```php {"header":"Примеры"}
$user_data = \Drupal::service('user.data');

// Gets value stored by "my_module" for user 1 in key "key".
$value = $user_data->get('my_module', 1, 'key');

// Gets array "key => value" stored by "my_module" for user 1.
$value = $user_data->get('my_module', 1);

// Gets array "uid => value" stored by "my_module" in key "key" for all
// users which has value in storage.
$value = $user_data->get('my_module', NULL, 'key');

// Gets array of arrays "uid => [key => value, ...]" stored by
// "my_module" for every user and key.
$value = $user_data->get('my_module');
```

### delete()

Метод `delete()` отвечает за удаление данных из хранилища.

Он принимает следующие аргументы:

- `$module`: (опционально) Машинное имя модуля, чьи данные необходимо удалить.
- `$uid`: (опционально) Идентификатор пользователя, чьи данные нужно удалить.
- `$name`: (опционально) Название ключа, данные в котором необходимо удалить.

Данные аргументы являются опциональными, поэтому будьте аккуратны, так как вызов
метода без передачи аргумнетов, удалит все данные, всех модулей, для всех
пользователей безвозвратно.

Любой из данных аргументов может быть как точным значением, так и массивом
значений.

Также, обратите внимание на то, что вам не нужно беспокоиться об удалении данных
при удалении пользователя, это будет сделано автоматически, но при этом, вам
необходимо заботиться об удалении данных из хранилища, при удалении вашего
модуля.

```php {"header":"Примеры"}
$user_data = \Drupal::service('user.data');

// Deletes all data for all users and modules.
$user_data->delete();

// Deletes all data for all user stored by module "my_module".
$user_data->delete('my_module');

// Deletes all data for all users stored by modules "my_module" and
// "my_second_module".
$user_data->delete(['my_module', 'my_second_module']);

// Deletes all data for user 1.
$user_data->delete(NULL, 1);

// Deletes all data for users 1, 2 and 3.
$user_data->delete(NULL, [1, 2, 3]);

// Deletes all data for all users of any module which has key "key".
$user_data->delete(NULL, NULL, 'key');

// C-c-c-combo!
// Deletes values stored in keys "key" and "second_key" for users 1, 2
// and 3 by modules "my_modyle" and "my_second_module".
$modules = ['my_module', 'my_second_module'];
$uids = [1, 2, 3];
$keys = ['key', 'second_key'];
$user_data->delete($modules, $uids, $keys);
```

## Пример

В материале про [создание Authentication Provider][d8-authentication-api] в
примере мы сделали базовые поля для хранения API key и API secret у
пользователя, где мне и указали (спасибо andypost), что есть данный сервис и
лучше эти данные хранить именно в нем. Это намного легче и не будет вызывать
ненужных инвалидаций кэша.

Мы сделаем модуль, который будет хранить API key и API secret в user.data
хранилище, выводить их в форме пользователя, и удалять, при деинсталяции модуля.

Первым делом мы создадим в модуле инсталяционный файл dummy.install и напишем в
нем логику генерации ключей для всех пользователей, а также удаление данных при
деинсталяции модуля.

```php {"header":"dummy.install"}
<?php

/**
 * @file
 * Install, update and uninstall functions for the Dummy module.
 */

use Drupal\Component\Utility\Crypt;

/**
 * Implements hook_install().
 */
function dummy_install() {
  // Generates for all existed users their API key and API secret values.
  $user_data = Drupal::service('user.data');
  $users = Drupal::entityQuery('user')->execute();

  foreach ($users as $uid) {
    $api_key = Crypt::randomBytesBase64(16);
    $api_secret = Crypt::randomBytesBase64(16);

    $user_data->set('dummy', $uid, 'api_key', $api_key);
    $user_data->set('dummy', $uid, 'api_secret', $api_secret);
  }
}

/**
 * Implements hook_uninstall().
 */
function dummy_uninstall() {
  $user_data = Drupal::service('user.data');
  // Delete all data for current module.
  $user_data->delete('dummy');
}
```

В `dummy_install()` мы загружаем всех пользователей, проходимся по каждому из
них, генерируем значение для ключа и секрета, а затем сохраняем эти значения для
конкретного пользователя в user.data хранилище, указывая названия нашего модуля
и ключи данных.

В `dummy_uninstall()` мы удаляем вообще все данные что наш модуль мог бы
сохранить. Так мы не оставим за собой мертвых данных. А удалением данных при
удалении пользователя будет заниматься сущность пользователя самостоятельно.

Обратите внимание что это не очень хороший для производительности пример. Он
лишь показыает общий принцип работы. Генерировать и сохранять ключи прямо при
установке плохая идея, так как пользователей может быть очень много. Если
необходимо генерировать каждому пользователю эти данные сразу, не по запросу
самого пользователя, лучше всего воспользоваться [Queue API][d8-queue-api] в
связке с [QueueWorker][d8-queue-worker], тем самым разгрузив процесс включения
модуля, а также исключить падение от нехватки ресурсов или различных лимитов.

Нам также необходимо выводить эту информацию в форме редактирования
пользователя, а также предусмотреть генерацию ключа и секрета при создании новых
пользователей.

```php {"header":"dummy.module"}
<?php

/**
 * @file
 * Primary module hooks for Dummy module.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\ProfileForm;

/**
 * Implements hook_ENTITY_TYPE_insert() for user.
 */
function dummy_user_insert(EntityInterface $entity) {
  $user_data = Drupal::service('user.data');

  $api_key = Crypt::randomBytesBase64(16);
  $api_secret = Crypt::randomBytesBase64(16);

  $user_data->set('dummy', $entity->id(), 'api_key', $api_key);
  $user_data->set('dummy', $entity->id(), 'api_secret', $api_secret);
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function dummy_form_user_form_alter(array &$form, FormStateInterface $form_state, $form_id) {
  $profile = $form_state->getFormObject();

  if ($profile instanceof ProfileForm) {
    $user_data = Drupal::service('user.data');
    /** @var \Drupal\user\UserInterface $user */
    $user = $profile->getEntity();

    $form['dummy_api'] = [
      '#type' => 'fieldset',
      '#title' => 'API',
    ];

    $form['dummy_api']['key'] = [
      '#type' => 'item',
      '#title' => 'Key',
      '#markup' => $user_data->get('dummy', $user->id(), 'api_key'),
    ];

    $form['dummy_api']['secret'] = [
      '#type' => 'item',
      '#title' => 'Secret',
      '#markup' => $user_data->get('dummy', $user->id(), 'api_secret'),
    ];
  }
}
```

В хуке `dummy_user_insert()` мы отлавливаем создание новых пользователей на
сайте и сразу же генерируем для них ключ и секрет, сохраняя в хранилище по
принципу `dummy_install()`.

В хуке `dummy_form_user_form_alter()` мы подключаемся к форме редактирования
пользователя, получаем объект пользователя, кому принадлежит данная форма, и
описываем элементы форм, которые будут выводить наши значения.

Если включить модуль, все пользователи получать свои уникальные ключи и секреты,
и при редактировании своих профилей будут видеть соответствующий раздел в форме.

[d8-queue-api]: ../../../../2015/11/12/d8-queue-api/index.ru.md
[d8-state-api]: ../../../../2015/10/16/d8-state-api/index.ru.md
[d8-authentication-api]: ../../../../2018/01/19/d8-authentication-api/index.ru.md
[d8-queue-worker]: ../../../../2019/04/21/d8-queue-worker/index.ru.md
