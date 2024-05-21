---
id: d8-queue-worker
language: ru
title: 'Drupal 8: Плагин QueueWorker — выполнение очередей по крону'
created: '2019-04-21T17:37:33'
updated: '2023-10-16T18:21:20'
description: 'Автоматическая обработка очередей во время крон операций.'
promo: 'image/black-and-white-cogs-gears-159298.jpg'
tags:
  - Drupal
  - 'Drupal 8'
  - 'Plugin API'
  - 'Queue API'
  - 'Batch API'
---

**QueueWorker** — тип плагина, позволяющий реализовать
выполнение [очередей][d8-queue-api] в cron операциях. Данный тип плагина
предоставляется ядром, поэтому вам не потребуется никаких дополнительных модулей
для его работы.

Данный тип плагина берет на себя всю работу с очередью, предоставляя вам данные
на обработку. Все что вам будет нобходимо делать, это обрабатывать входящие
данные.

## Состав плагина

### Аннотация @QueueWorker

Аннотация плагина имеет следующие значения:

- `id`: Идентификатор плагина. Очень важно, чтобы название плагина было равным
  названию очереди. Именно так будет получаться очередь. Если ваша очередь имеет
  id равный `my_queue`, то и id плагина должен быть именно таким.
- `title`: Человеко-понятное название плагина.
- `cron`: (опционально) Настройки для крона.
  - `time`: Время в секундах, выделяемое на выполнение очереди. Пока данное
    время не истечет, будут производиться попытки получения элементов из очереди
    и отправка их на выполнение плагину. По умолчанию присваивается 15 секунд.

Пример аннотации:

```php
/**
 * @QueueWorker(
 *   id = "my_queue_name_to_process",
 *   title = @Translation("My queue worker"),
 *   cron = {"time" = 60}
 * )
 */
```

### Объект плагина

Плагины данного типа создаются в `src/Plugin/QueueWorker`.

Объект плагина должен расширять `Drupal\Core\Queue\QueueWorkerBase`. Вам
необходимо объявить один единственный метод `processItem($data)`, в котором и
описать всю логику обработки элемента очереди.

В качестве аргумента метод принимает один единственный аргумент, который
содержит элемент очереди, в том виде, как вы его добавили в данную
очередь `$queue->createItem()`.

Метод не должен ничего возвращать, вы просто выполняете необходимую логику и
всё. При этом вы можете вызвать одно из следующих исключений, которые будут
корректно обработаны менеджером плагина:

- `RequeueException`: Прерывает обработку текущего элемента очереди, возвращая
  его на повторную обработку. После чего выполнение очереди данным плагином
  продолжится.
- `SuspendQueueException`: Прерывает обработку текущего элемента очереди,
  возвращая его на повторную обработку. После чего выполнение плагина
  прекращается и вызывается следующий.
- Любое другое исключение будет записано в лог сайта и элемент не удалится из
  очереди.

Пример плагина:

```php
<?php

namespace Drupal\dummy\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Process a queue.
 *
 * @QueueWorker(
 *   id = "my_queue_name_to_process",
 *   title = @Translation("My queue worker"),
 *   cron = {"time" = 60}
 * )
 */
class MyQueueWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Do something with $data.
  }

}
```

## Примеры

В качестве примеров использования мы разберемся с реализацией данного типа
плагинов из ядра.

### Пример №1. aggregator_feeds

Плагин `aggregator_feeds` отвечает за фоновое обновление фидов модуля Aggregator
из ядра.

Очередь с данным id собирается в крон операции `aggregator_cron()`. В днном хуке
производится загрузка всех фидов, которые необходимо обновить. Фиду
устанавливается время текущего запроса, как время когда он добавлен в очередь,
чтобы избежать повторного вызова, а в очередь передается экземпляр сущности
feed (`FeedInterface`). Иными словами, данная очередь, хранит сущности `feed`,
которые нобходимо обработать.

А теперь посмотрим на плагин, который обрабатывает данную очередь:

```php {"header":"core/modules/aggregator/src/Plugin/QueueWorker/AggregatorRefresh.php"}
<?php

namespace Drupal\aggregator\Plugin\QueueWorker;

use Drupal\aggregator\FeedInterface;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Updates a feed's items.
 *
 * @QueueWorker(
 *   id = "aggregator_feeds",
 *   title = @Translation("Aggregator refresh"),
 *   cron = {"time" = 60}
 * )
 */
class AggregatorRefresh extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data instanceof FeedInterface) {
      $data->refreshItems();
    }
  }

}
```

Очень простой и понятный, пройдемся по нему сверху-внизу:

- В аннотации мы видим указание необходимой очереди для обработки, а также, что
  времени на обработку данной очереди в кроне будет выделено 60 секунд.
- В `processItem()` в качестве аргумента, ожидается что будет передан
  экземпляр `FeedInterface`, а значит - объект сущности feed. Если всё
  корректно, у данной сущности вызывается метод `refreshItems()`. В данном
  методе производится обновление фида, а также сброс значения времени, когда он
  добавлен в очередь, чтобы при следующем кроне данная сущность опять была
  обнаружена в `aggregator_cron()` и очередь была собрана заново.

Таким образом, обновление фидов производится полностью в автоматическом режиме и
в фоне, при выполнении крон операций.

### Пример №2. media_entity_thumbnail

Данный плагин занимается обработкой очереди `media_entity_thumbnail`, и отвечает
за обработку превьюшек для медиа элементов.

Возьмем для примера медиа тип Remote Video, который позволяет добавлять на сайт
YouTube и Vimeo видео. По умолчанию опция отложенной загрузки превьюшек
отключена, но вы можете включить её в редактировании данного типа медиа
сущности, установив галочку "Queue thumbnail downloads".

Установив её, после добавления новой сущности данного типа будет
вызван `Media::postSave()` метод сущности. В данном методе производится
проверка, установлена ли данная галочка, и является ли сущность новой. Если
условия удовлетворены, в очередь `media_entity_thumbnail` будет добавлен id
данной сущности на обработку в видео массива: `['id' => $translation->id()]`.

При следующем вызове крон операций будет вызван одноименный плагин.

```php {"header":"core/modules/media/src/Plugin/QueueWorker/ThumbnailDownloader.php"}
<?php

namespace Drupal\media\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process a queue of media items to fetch their thumbnails.
 *
 * @QueueWorker(
 *   id = "media_entity_thumbnail",
 *   title = @Translation("Thumbnail downloader"),
 *   cron = {"time" = 60}
 * )
 */
class ThumbnailDownloader extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\media\Entity\Media $media */
    if ($media = $this->entityTypeManager->getStorage('media')->load($data['id'])) {
      $media->updateQueuedThumbnail();
      $media->save();
    }
  }

}
```

Пройдемся по нему сверху-вниз:

- Первым делом смотрим на аннотацию, где видем, что будет обрабатываться
  очередь `media_entity_thumbnail`, а также, что времени на выполнение данной
  очереди будет выделяться 60 секунд.
- В `processItem()` методе мы видим, что первым делом пытается загрузиться медиа
  сущность с id из очереди. Если она загрузилась, то вызывается её
  метод `updateQueuedThumbnail()`, который, в свою очередь, попробует загрузить
  файл изображения для первью, а затем осхранит сущность.

Таким образом, во время крон операций будут обновляться превьюшки для новых
медиа сущностей.

Также вы можете обратить внимание на то, что данный пример более комплексный
из-за Dependency Injection [сервисов][d8-services].

## Заключение

Данный тип плагина может помочь при обработке очередей по крону, вам не придется
писать свой процесс обработки, вам достаточно создать плагин и описать в нем
процесс обработки элемента очереди, а всю остальную работу возьмет на себя ядро.

Это очень полезнный плагин для обработки неопределенного кол-ва данных, в
неопределенное время. На нем можно спокойно реализовать массовые рассылки,
обновления содержимого (различные импорты) и т.д. Круг применения у него
огромный и по большей части зависит от того, подходят вам очереди или нет. И
если очереди вам подходят для решения задачи, и вы не хотите
использовать [Batch API][d8-batch-api], который требует участия юзера, то данный
плагин поможет вам поставить всё на автоматическую обработку при помощи
стандартных инструментов.

[d8-services]: ../../../../2017/06/21/d8-services/index.ru.md
[d8-queue-api]: ../../../../2015/11/12/d8-queue-api/index.ru.md
[d8-batch-api]: ../../../../2018/09/11/d8-batch-api/index.ru.md
