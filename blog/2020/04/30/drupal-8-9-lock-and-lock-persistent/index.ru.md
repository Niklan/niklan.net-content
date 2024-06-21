---
id: drupal-8-9-lock-and-lock-persistent
language: ru
title: 'Drupal 8, 9: lock и lock.persistent — блокировка состояния'
created: '2020-04-30T14:13:22'
updated: '2024-05-25T00:00:00'
description: >-
  Drupal 8, 9: блокировка состояния — узнайте, как обеспечить целостность
  данных, предотвратить конфликты при работе с сайтом и избежать «состояние
  гонки».
promo: 'image/michael-chacon-sLFP7UtFiHw-unsplash.jpg'
tags:
  - Drupal
  - 'Drupal 8'
  - 'Drupal 9'
---

«Блокировщики» используются во множестве инструментов и программном обеспечении.
В Drupal мы имеем схожий инструментарий, за который отвечает компонент ядра
Lock.

Задача блокировщика проста — заблокировать или зафиксировать какое-то состояние
системы или подсистемы. Назначение очень сильно зависит от сферы и целей
применения. В качестве примеров могу привести те что я знаю, и, возможно,
знакомы вам:

1. `composer.lock` — специальный файл
   от [менеджера пакетов Composer][drupal-8-composer]. Он фиксирует состояние
   зависимостей проекта на момент последнего запроса зависимости или обновления
   проекта. Он хранит в себе всю необходимую информацию, какие зависимости,
   каких версий, на какое состояние, дату были загружены, а также откуда они
   были загружены (прямой URL). Благодаря данному файлу мы можем повторно
   устанавливать все зависимости проекта. Важным тут является то — что версии
   фиксируются («блокируются») на момент последней генерации этого файла. Таким
   образом, даже спустя год, вызвав команду `composer install`, установятся
   именно те версии пакетов, а не новые. Так проект становится более
   отказоустойчивым и не подвержен аномально появившимся новым релизам. Именно
   поэтому неправильно использовать на продакшене что-то отличное от этой
   команды.
2. `yarn.lock`, `package-lock.json` — аналогичные `composer.json` файлы, только
   менеджера пакетов Yarn и NPM, соответственно.
3. `*.lock` файлы. Такие файлы можно встретить много где. Например, их можно
   встретить в Linux системах. Некоторые пакетные менеджеры систем создают такие
   файлы в процессе работы с репозиториями. Например, когда вы обновляете
   систему. При наличии данного файла, запуск обновления завершиться с ошибкой.
   Таким образом, система предотвращает возможный одновременный запуск
   обновлений, который может привести к фатальным ошибкам.

## Применение блокировщиков

Блокировщики могут помочь не допустить сбоя системы и сохранить целостность,
обезопасить данные, снять нагрузку с сервера путём предотвращения дублированых
операций и т.д. Применение для них можно найти в различных задачах поэтому знать
о них, крайне полезно.

Давайте рассмотрим как и где применяются блокировки в ядре (список не полный):

- **Крон операции.** В момент, когда запускается выполнение крон операций,
  Drupal включает блокировку. Когда все операции завершаются, он снимает эту
  блокировку. Если после блокировки вызвать крон повторно, вы получите
  предупреждение «Attempting to re-run cron while it is already running.». Таким
  образом, крон вызывается только один раз, и пока его обработка не завершится,
  повторные вызовы будут сразу завершаться.
- **Генерация стилей изображений.** Drupal генерирует стили изображения в момент
  запроса к этой самой картинке. Таким образом, если картинки нет, он её
  начинает сразу генерировать и отдаёт когда готова, если она уже имеется, то
  сразу. Процесс генерации также ставит блокировку, на случай когда в процессее
  генерации картинки, к ней попробуют обратиться ещё раз. Для того чтобы не
  запускать тот же самый процесс повторно, не перегружать сервер, не делать
  холостую работу и используется блокирощик. Когда картинка сгенерирована
  процесс разблокируется и будет работать в штатном режиме.
- **Загрузка файла на сервер.** Когда вы загружаете файл на сервер, Drupal
  создаёт для данного URI блокировку, таким образом, по этому пути, пока
  загружается один файл, невозможно загрузить другой. Таким образом сохраняется
  целостность первой загрузки и исключение конфликтов, перезаписи и непонятного
  поведения.
- **Интерфейс Views.** Когда вы редактируете представление, но ещё не сохранили
  изменения, Views ставит блокировку для данного представления с привязкой к UID
  пользователя. Это защищает правки от вмешательства других пользователей. Таким
  образом, если вы вносите правки в представление, и другой пользователь с
  соответствующими правами попытается зайти в редактирование, у него будет
  показано сообщение о том, что «такой-то» пользователь уже вносит правки, и
  интерфейс будет работать в режиме чтения.
- **Экспорт и импорт конфигураций.** Когда вы запускаете процесс импорта или
  экспорта конфигураций, Drupal также включает необходимую блокировку. Так, пока
  процесс не завершится, никто другой не сможет повлиять на состояние системы и
  привести к сбою системы.

А также множество других примеров прямо в ядре!

Какой можно сделать вывод из данных примеров? В Drupal блокировщики используются
для защиты данных и предотвращения повторных вызовов при длительных операциях, и
в местах, где допускается одновременная работа нескольких пользователей,
действия которых могут поломать работу другому.

## Инструменты для блокировки

Drupal предоставляет интерфейс `\Drupal\Core\Lock\LockBackendInterface` который
описывает требования к блокировщикам. Вы можете создавать свои собственные
механизмы блокировки.

Из коробки мы имеем два готовых [сервиса][drupal-8-services] для управления
блокировками `lock` и `lock.persistent`. `lock.persistent` практически 100%
копия `lock`, но есть небольшое, но очень важное отличие между ними:

- `lock` — его блокировки снимаются сразу как только запрос, в котором они были
  созданы, завершается, даже если время блокировки не истекло.
- `lock.persistent` — поддерживает блокировку до конца указанного времени, не
  завися от запроса.

Говоря проще, блокировка при помощи сервиса `lock` живёт пока обрабатывается
запрос, в котором и была вызвана блокировка. Как только обработка запроса
закончится, данная блокировка очистится независимо от оставшегося времени
действия. В случае с `lock.persistent`, запись о блокировке не удаляется по
окончанию запроса, и она будет существовать до тех пор, пока её не разблокируют
или не истечёт время действия.

Важно также понимать, что блокировка `lock` действует для всех запросов, но
живёт пока не закрыта обработка запроса который её создал.

Небольшое пошаговое объяснение как это работает:

- Пользователь сделал запрос к сайту, Drupal сделал блокировку при
  помощи `lock`. Заблокировал операцию на 10 минут и она выполняется.
- Через минуту происходит очередной похожий запрос. `lock` скажет что есть
  блокировка (хотя она от другого запроса).
- Через 2 минуты операция завершилась. Запрос закрылся, блокировка принудительно
  удаляется.
- Можно начинать с пункта 1.

Это работает между запросами по той причине, что эти сервисы хранят информацию о
блокировке в таблице `semaphore`.

На основе этой информации выбирайте какой блокировщик вам больше подходит.

## Обзор возможностей блокировщика

Как я указал в прошлом разделе, для блокировщиков в ядре имеется
интерфейс `\Drupal\Core\Lock\LockBackendInterface`, который должны расширять все
реализации различных блокировщиков. Поэтому можно пройтись по нему и все
описанные возможности будут применимы ко всем типам блокировщиков.

### ::acquire()

`::acquire($name, $timeout = 30.0)` — создаёт блокировку на основе имени и
таймаута:

- `$name`: Машинное название блокировки, по которой будет проверяться её наличие
  или отсутствие. Максимальная длина — 255 символов.
- `$timeout`: Время жизни блокировки в секундах (можно указывать дробные
  значения, например 0.1 - 100ms). Минимальное значение — 0.001 (1ms). Это
  максимальное время жизни данной блокировки. По истечению данного времени, она
  станет доступна для блокировки вновь и информация о ней автоматически
  очистится.

В таймауте вы должны указать максимальное время выполнения, после чего вы
считаете что данная блокировка больше не имеет смысла. Например, крон операции
блокируются на 900 секунд (15 минут). Если крон операции не закончились за 15
минут, следующий вызов запустит все операции снова.

Вы можете использовать данный метод повторно, для того чтобы продлить время
действия блокировки. Но это можно делать только в том запросе, в котором она
была создана.

В качестве результата возвращается булевое значение (данные результаты могут
отличаться в нестандартных реализациях):

- `TRUE`: Возвращается если блокировщик успешно создан или продлён (если
  запрашивался в пределах того же запроса).
- `FALSE`: Возвращается если блокировщик не был создан по каким-то причинам, или
  уже существует (его создали из другого запроса).

Пример создания блокировки с названием `my_lock` на 60 секунд:

```php
$lock->acquire('my_lock', 60.0);
```

### ::lockMayBeAvailable()

`::lockMayBeAvailable($name)` — проверяет, можно ли создать блокировку по
указанному названию.

Вернёт `TRUE`, если блокировки с таким названием не существует, `FALSE`, если
уже имеется.

Пример проверки блокировки по имени `my_lock`:

```php
$is_available = $lock->lockMayBeAvailable('my_lock');
if ($is_available) {
  // The lock is exists.
}
else {
  // The lock doesn't exists.
}
```

### ::wait()

`::wait($name, $delay = 30)` — проверка наличия блокировки спустя указанное
количество секунд.

При помощи данного метода можно дождаться освобождения блокировки для своих
операций. При вызове запускается ожидание, по окончанию которого будет возвращён
булевый результат. `TRUE` — если блокировка всё ещё присутствует, спустя
указанное время, `FALSE`, если отсутствует.

Пример попытки создания блокировки `my_lock`, но при этом, если она уже имеется,
мы даём 30 секунд дождаться чтобы она освободилась. Если и через 30 секунд
блокировщик существует — выбрасываем исключение.

```php
if (!$lock->acquire('my_lock')) {
   // If lock exists, wait 30 seconds to check availability again.
  $lock->wait('my_lock');
  if (!$lock->acquire('my_lock')) {
    // Still locked.
    throw new Exception("Couldn't acquire lock.");
  }
}
```

### ::release()

`::release($name)` — снимает блокировку по указанному имени.

Данный метод позволяет снять блокировку раньше таймаута. Это необходимо когда
«блокировка» больше не требуется.

Пример снятия блокировки `my_lock`:

```php
$lock->release('my_lock');
```

### ::releaseAll()

`::releaseAll($lockId = NULL)` — снимает все блокировки.

Параметр `$lockId` позволяет указать группу блокировок, которые нужно
разблокировать. По умолчанию значение `NULL` — а это значит что снимутся все
блокировки, за которые отвечает сервис.

Важно отметить что у `lock` блокировок, это случайное значение. Оно генерируется
единожды в пределах запроса. У всех блокировок в пределах одного запроса будут
одинаковые ID. В случае с `lock.persistent`, у всех его блокировок `lockId`
равен `persistent`.

Пример снятия всех блокировок созданных при помощи сервиса `lock.persistent`:

```php
$lock->releaseAll('persistent');
```

### ::getLockId()

`::getLockId()` — возвращает ID блокировки.

Он может потребоваться для будущей чистки группы блокировок.

Пример создания двух блокировок, и их чистка по ID.

```php
$lock->acquire('my_lock');
$lock->acquire('my_lock_2', 60);

$lock_id = $lock->getLockId();
$lock->releaseAll($lock_id);
```

## Пример

Код блокировки и всё что с ним связано, обычно пара строчек, а то, для чего он
делается, многократно больше. Поэтому примеры будут максимально шаблонные, где
главное — показать примеры применения и принцип работы, всё остальное просто
фон.

Так как блокировщики требуют каких-то условий, где их использование будет
оправдано — мы эти условия симулируем. Мы будем генерировать файл на 1 миллион
случайных данных. Это длится 20-30 секунд поэтому это отличное место для
применения блокировщика. Мы не хотим чтобы этот процесс запускался одновременно
дважды и более раз, ведь это преведёт к перегрузу ресурсов, замедлению и,
возможно, падению системы. Особенно если подобные вещи торчат в публичный
доступ (например, генерация PDF), это отличная точка для DDOS атаки, если за
этим не проследить.

Мы также сделаем контроллер и форму, которые будут использовать данный генератор
и блокировать процесс двумя разными способами.

### Создание генератора файла

Прежде всего нам нужно создать сервис генератора файла. Он будет отвечать за всё
что связано с генерацией фейкового файла на 1 миллион данных. Затем мы будем его
переиспользовать в форме и контроллере.

Долго задерживаться тут не будем, и просто приведу его содержание:

```php {"header":"src/BigFileGenerator.php"}
<?php

namespace Drupal\example;

use Drupal\Component\Utility\Random;
use Drupal\Core\File\FileSystemInterface;

/**
 * Provides generator of fake file which takes a bit of time.
 */
final class BigFileGenerator {

  /**
   * The file path directory where files will be stored.
   *
   * @var string
   */
  private $directory = 'public://niklan-example';

  /**
   * The amount if items to generate.
   *
   * @var int
   */
  private $itemsCount = 1000000;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * Constructs a new BigFileGenerator object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * Generates big file with fake data.
   *
   * @param string $filename
   *   The filename without extension.
   * @param bool $force
   *   TRUE if file must be generated even if it exists. The old file will be
   *   overridden.
   */
  public function generate(string $filename, bool $force = FALSE): void {
    if (!$force && $this->fileExists($filename)) {
      return;
    }

    // Make sure directory exists and writable.
    if (!$this->fileSystem->prepareDirectory($this->directory, FileSystemInterface::CREATE_DIRECTORY)) {
      throw new \Exception(sprintf('The %s directory is not writable.', $this->directory));
    }

    // If file exists and we're still here, then $force is set to TRUE and we
    // delete the old file.
    if ($this->fileExists($filename)) {
      $this->fileSystem->unlink($this->buildUri($filename));
    }

    $random = new Random();
    $temp = $this->fileSystem->tempnam('temporary://', 'example');
    // Write new data to file.
    $handle = fopen($temp, 'w');
    for ($i = 0; $i < $this->itemsCount; $i++) {
      $fields = [
        $random->string('255'),
        $random->word('17'),
      ];
      fputcsv($handle, $fields);
    }
    fclose($handle);
    // Move file only when write is finished.
    $this->fileSystem->move($temp, $this->buildUri($filename), FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * Check's whether file with provided filename is exists or not.
   *
   * @param string $filename
   *   The filename without extension.
   *
   * @return bool
   *   TRUE if file presented, FALSE otherwise.
   */
  public function fileExists(string $filename): bool {
    return file_exists($this->buildUri($filename));
  }

  /**
   * Builds URI to the file.
   *
   * @param string $filename
   *   The filename without extension.
   *
   * @return string
   *   The fully qualified URI to the file.
   */
  public function buildUri(string $filename): string {
    return $this->directory . '/' . $filename . '.csv';
  }

}
```

А также, объявим класс как сервис, так как нам нужен Dependency Injection.

```yaml {"header":"example.services.yml"}
services:
  example.big_file_generator:
    class: Drupal\example\BigFileGenerator
    arguments: ['@file_system']
```

### Контроллер

Контроллер будет генерировать файл при обращении. Если файл есть, генерация
пропускается, но если его нету, то будем производить блокировку. В процессе
блокировки мы будем отдавать соответствующий ответ всем последующим запросам, а
саму генерацию пропускать.

Создаём контроллер:

```php {"header":"src/Controller/ExampleController.php"}
<?php

namespace Drupal\example\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Generates big file with locking.
 *
 * The generate process will be processed using lock. That will protect multiple
 * simultaneous calls of this controller and generation init. Only one generate
 * is possible.
 *
 * This example uses lock which will be destroyed after response will be sent to
 * the user. So we don't need to care about unlocking it.
 */
final class ExampleController implements ContainerInjectionInterface {

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The big file generator.
   *
   * @var \Drupal\example\BigFileGenerator
   */
  protected $bigFileGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ExampleController {
    $instance = new static();
    $instance->lock = $container->get('lock');
    $instance->bigFileGenerator = $container->get('example.big_file_generator');
    return $instance;
  }

  /**
   * Builds the response.
   */
  public function build(): array {
    if (!$this->bigFileGenerator->fileExists('controller')) {
      $lock_acquired = $this->lock->acquire('example_controller', 60);
      if (!$lock_acquired) {
        throw new ServiceUnavailableHttpException(3, new TranslatableMarkup('Generation in progress. Try again shortly.'));
      }

      $this->bigFileGenerator->generate('controller');
    }

    $build['content'] = [
      '#type' => 'inline_template',
      '#template' => '<a href="{{ href }}">{{ label }}</a>',
      '#context' => [
        'href' => file_create_url($this->bigFileGenerator->buildUri('controller')),
        'label' => new TranslatableMarkup('Open generated file'),
      ],
    ];

    return $build;
  }

}
```

Контроллер достаточно простой. В качестве зависимостей мы производим Dependency
Injection сервиса `lock` и нашего `example.big_file_generator`.

В данном примере мы будем использовать `lock`, так, когда файл сгенерируется и
будет отдан ответ или произойдёт ошибка, данная блокировка автоматически
сбросится. Но мы даём 60 секунд на генерацию. Это более чем с запасом в данном
кейсе.

В основном методе мы проверяем файл на наличие. Если он есть, мы формируем и
отдаём результат. Если файла нет, мы пытаемся получить блокировку. И тут мы
возвращаемся к описанию метода `::acquire()`. Мы получим `TRUE`, если мы успешно
создали блокировку, но если кто-то другой, параллельно, пока эта блокировка
существует, опять обратится к контроллеру, то она вернёт `FALSE`. Таким образом,
первый запрос блокирует процесс, все последующие
получат [ошибку о том что нужно повторить попытку через 3 секунды](https://developer.mozilla.org/ru/docs/Web/HTTP/%D0%97%D0%B0%D0%B3%D0%BE%D0%BB%D0%BE%D0%B2%D0%BA%D0%B8/Retry-After)
с причиной «Генерация в процессе. Попробуй еще раз в ближайшее время.».
Например, такое же поведение имеет генерация стилей изображений. После успешной
блокировки мы запускаем генерацию.

Затем мы просто генерируем ссылку до файла и отвечаем.

Не забываем также объявить нашему контроллеру маршрут:

```yaml {"header":"example.routing.yml"}
example.controller:
  path: '/example-controller'
  defaults:
    _title: 'Example'
    _controller: '\Drupal\example\Controller\ExampleController::build'
  requirements:
    _permission: 'access content'
```

Например, я подобный механизм использую при генерации тяжёлых PDF файлов. Если
по каким-то причинам одновременно запросят одну и ту же генерацию, не случится
перегруза системы от лишних действий. Вместо исключения вы можете делать более
мягкие информативные сообщения.

### Форма

Форма будет простая — будет две кнопки: Генерация и Снять блокировку. Поведение
мы сделаем следующим:

- Кнопка «Генерировать» будет создавать файл, принудительно, даже если он есть.
- Кнопка «Генерировать» будет активна только когда отсутствует блокировка.
- Блокировка будет «persistent», а значит по завершению генерации она продолжит
  работать если мы её не снимем. А снимать мы её не будем. Мы сделаем так, что
  она будет длиться 5 минут с момента активации. Таким образом мы ограничим
  частоту генерации раз в пять минут.
- Если блокировка активна, мы будем показывать кнопку принудительного снятия
  блокировки. Таким образом, мы сможем запустить генерацию раньше чем через 5
  минут.

Объявляем такую форму.

```php {"header":"src/FormExampleForm.php"}
<?php

namespace Drupal\example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a example with form that locks actions.
 *
 * The form uses persistent lock, which will be cleared programmatically or
 * when ends timeout.
 *
 * Form generates file and lock it for 5 minutes. This means, the generation
 * can be requested only once per 5 minutes. But we also provide unlock button
 * to forcefully run generate process.
 */
class ExampleForm extends FormBase {

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The big file generator.
   *
   * @var \Drupal\example\BigFileGenerator
   */
  protected $bigFileGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->lock = $container->get('lock.persistent');
    $instance->bigFileGenerator = $container->get('example.big_file_generator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['generate'] = [
      '#type' => 'submit',
      '#value' => new TranslatableMarkup('Generate'),
      '#disabled' => !$this->lock->lockMayBeAvailable($this->getFormId()),
      '#op' => 'generate',
    ];
    $form['actions']['unlock'] = [
      '#type' => 'submit',
      '#value' => new TranslatableMarkup('Force unlock'),
      '#op' => 'unlock',
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
      '#access' => !$this->lock->lockMayBeAvailable($this->getFormId()),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'example_locking_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $triggered_element = $form_state->getTriggeringElement();
    $action = !isset($triggered_element['#op']) ? 'generate' : $triggered_element['#op'];
    switch ($action) {
      case 'generate':
        if (!$this->lock->acquire($this->getFormId(), 300)) {
          return;
        }
        $this->bigFileGenerator->generate('form', TRUE);
        break;

      case 'unlock':
        $this->lock->release($this->getFormId());
        break;
    }
  }

}
```

Всё должно быть понятно и без дополнительных объяснений. Единственное на что
обращу внимание — это процесс запуска генерации. Там так же как и в контроллере
производится получение блокировки с последующей проверкой. В случае если
блокировка уже запущена из другого запроса, текущий завершится без вызова
генерации. Это покрывает тот кейс, когда эту форму могли открыть на двух
устройствах в разное время, когда блокировки ещё не существовало и кнопка
«Генерировать» доступна в обоих случаях. И когда один из двух пользователей
запускает генерацию, на тот момент уже может быть блокировка запущенная вторым
пользователем. Поэтому процесс сбросится. По-хорошему тут нужно написать
объяснение, что процесс был запущен кем-то другим, но это уже не про данную
статью.

Затем добавляем нашей форме маршрут, чтобы можно было потыкаться:

```yaml {"header":"example.routing.yml"}
example.form:
  path: '/example-form'
  defaults:
    _title: 'Form example with locking mechanism'
    _form: 'Drupal\example\Form\ExampleForm'
  requirements:
    _permission: 'access content'
```

## Ссылки

- [Модуль с примером](example)

[drupal-8-services]: ../../../../2017/06/21/drupal-8-services/index.ru.md
[drupal-8-composer]: ../../../../2016/09/03/drupal-8-composer/index.ru.md
