На этой неделе состоится релиз **Drupal 11.3**. Давайте посмотрим, какие новшества он принесёт.

В этом обзоре я собрал самые интересные для меня изменения. Полный список обновлений вы найдёте на странице
релиза[^drupal-11-3-release].

::: note [Drupal 10 выходит на финишную прямую]
Одновременно с Drupal 11.3 состоится релиз **Drupal 10.6** — это последний минорный релиз для **Drupal 10**.

Скорее всего, в июне 2026 года (а если не успеют — то в декабре) Drupal 10 будет объявлен устаревшим. В это же время
Drupal 11 перейдёт на долгосрочную поддержку (LTS), а также состоится релиз Drupal 12.
:::

## Добавлена поддержка ООП-хуков в темах

Теперь темы поддерживают ООП-хуки.[^oop-hooks-themes] Подход в целом такой же, как в модулях, но есть несколько
особенностей:

- Параметры `$module` и `$order` атрибута `#[Hook]` не поддерживаются. Поэтому темы не могут регистрировать хуки от
  лица других модулей или тем. Точно так же модули не могут регистрировать хуки от лица какой-либо темы.
- В темах недоступны атрибуты `#[ReorderHook]` (нельзя менять порядок выполнения хуков) и `#[RemoveHook]` (нельзя
  удалять сторонние хуки).
- Порядок выполнения хуков фиксированный: модули → базовая тема → основная тема.
- Для темы доступны только те альтер-хуки, которые проходят через `Drupal\Core\Theme\ThemeManagerInterface::alter()` /
  `::alterForTheme()`.

## Представлен объектно-ориентированный API для работы с определёнными типами рендер-массивов

В ядре сделаны первые шаги по переводу [рендер-массивов][render-arrays] на объектно-ориентированный подход. Первыми
элементами, переведёнными на ООП, стали:

- Элементы форм (`#[FormElement]`)[^form-oop-render-array]
- Рендер-элементы (`#[RenderElement]`)[^form-oop-render-array]
- Виджеты полей (`#[FieldWidget]`)[^field-widget-oop-render-array]

Изменение позволяет работать с этими структурами как с классами. Это даёт возможность использовать преимущества ООП в
IDE: подсказки и автодополнение. Полный отказ от рендер-массивов планируется к релизу **Drupal 13**.

**Основная идея:** сервис `Drupal\Core\Render\ElementInfoManagerInterface` теперь предоставляет структуры
рендер-элементов. Они служат основой для форм и активно применяются при их создании.

::::: figure
  ::: figcaption
    **Листинг 1.** Пример использования сервиса `Drupal\Core\Render\ElementInfoManagerInterface` для работы с
    рендер-массивов.
  :::
  ```php
  use Drupal\Core\Render\Element\Details;
  use Drupal\Core\Render\Element\Email;
  use Drupal\Core\Render\Element\Submit;
  use Drupal\Core\Render\Element\Textfield;
  use Drupal\Core\Render\ElementInfoManagerInterface;
  use Drupal\Core\StringTranslation\TranslatableMarkup;
  
  $form_render_array = [
    'foo' => [
      '#type' => 'textfield',
    ],
    'actions' => [
      '#type' => 'actions',
    ],
  ];
  
  $element_info_manager = $container->get(ElementInfoManagerInterface::class);
  
  $form = $element_info_manager->fromRenderable($form_render_array);
  $form->removeChild('foo');
  
  $textfield = $element_info_manager->fromClass(Textfield::class);
  $textfield->default_value = new TranslatableMarkup('Hello, World!');
  $textfield->required = TRUE;
  $form->addChild('bar', $textfield);
  
  $email_wrapper = $form->createChild('email_wrapper', Details::class);
  $email_wrapper->open = TRUE;
  
  $email = $email_wrapper->createChild('email', Email::class);
  $email->placeholder = new TranslatableMarkup('Enter your email address');
  $email->required = TRUE;
  
  $actions = $form->getChild('actions');
  $submit = $actions->createChild('submit', Submit::class);
  $submit->button_type = 'primary';
  $submit->value = new TranslatableMarkup('Submit');
  
  $form_render_array = $form->toRenderable();
  ```
:::::

Чтобы добавить необходимую информацию в собственные рендер-элементы, необходимо указывать свойства элемента в PHPDoc
через `@property`-нотацию.

::::: figure
  ::: figcaption
  **Листинг 2.** Пример `#[RenderElement]`-плагина с описанием свойств через `@property`-нотацию:
  :::
  ```php
  use Drupal\Core\Render\Attribute\RenderElement;
  use Drupal\Core\Render\Element\RenderElementBase;
  
  /**
   * @property string|null $my_property
   * @property array<string> $another_property
   */
  #[RenderElement('my_element')]
  final class MyElement extends RenderElementBase {
  
    public function getInfo(): array {
      return [
        '#my_property' => NULL,
        '#another_property' => [],
      ];
    }
  
  }
  ```
:::::

## Добавлен экспериментальный модуль `mailer` для интеграции Symfony Mailer

Добавлен **экспериментальный** модуль `mailer` для интеграции **Symfony Mailer**[^symfony-mailer-docs] в
Drupal.[^mailer-module] Он позволяет использовать современные транспорты (SMTP, Sendmail и другие), настраивать
параметры отправки через DSN и перехватывать письма в тестах.

Знакомо, не правда ли? Ещё в 2023 году в **Drupal 10.2**[^drupal-10-2-is-available] добавили зависимость
`symfony/mailer` и соответствующий `#[Mail]`-плагин.[^symfony-mailer-component] С тех пор компонент можно было
использовать при необходимости, но его возможности были сильно ограничены.

Новый модуль расширяет возможности, предоставляя сервисы для отправки, модификации сообщений и кастомизации процессов:

- **Основные сервисы для работы с почтой:**
  - `Symfony\Component\Mailer\MailerInterface` — отправка сообщений.
  - Сервис `Symfony\Component\Mailer\Transport\TransportInterface` — прямая отправка в обход
    **Messenger**[^symfony-messenger-docs].
- **Кастомизация транспортов:**
  - Фабрика `Drupal\Core\Mailer\TransportServiceFactoryInterface` для замены или модификации транспортов.
  - Абстрактный сервис `Symfony\Component\Mailer\Transport\AbstractTransportFactory` для создания сторонних транспортов
    через [сервисы с метками][tagged-services] `mailer.transport_factory`.
- **События для обработки писем:**
  - `Symfony\Component\Mailer\Event\MessageEvent`[^symfony-mailer-message-event] — изменение содержимого сообщения перед
    отправкой.
  - `Symfony\Component\Mailer\Event\SentMessageEvent`[^symfony-mailer-sent-message-event] — получение данных об успешной
    отправке: оригинал сообщения, отладочная информация, идентификатор.
  - `Symfony\Component\Mailer\Event\FailedMessageEvent`[^symfony-mailer-failed-message-event] — обработка ошибок с
    информацией о сообщении, тексте ошибки и отладочных данных.
- Новая настройка `mailer_sendmail_commands` ограничивает доступные команды `sendmail`. При попытке использовать
  неразрешённые команды система выбрасывает исключение о небезопасной отправке сообщения.
- Для тестирования добавлен модуль `mailer_capture`, который перехватывает отправленные письма.[^mailer-capture]

::::: figure
::: figcaption
**Листинг 3.** Пример отправки сообщения с использованием нового сервиса `Symfony\Component\Mailer\MailerInterface`.
:::
  ```php
  use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
  use Symfony\Component\Mailer\MailerInterface;
  use Symfony\Component\Mime\Email;
  
  final readonly class Foo {
  
    public function __construct(
      private MailerInterface $mailer,
      private LoggerInterface $logger,
    ) {}
  
    public function doSomething(): void {
      // Прочая логика…
      try {
        $this->sendEmail();
      }
      catch (TransportExceptionInterface $exception) {
        $this->logger->error($exception->getMessage());
      }
    }
  
    private function sendEmail(): void {
      $email = new Email();
      $email->subject('Hello, World!');
      $email->from('webmaster@example.com');
      $email->to('you@example.com');
      $email->text('Hello, World! This is a test email.');
      $email->attachFromPath('/path/to/file.pdf', 'file.pdf', 'application/pdf');
  
      $this->mailer->send($email);
    }
  
  }
  ```
:::::

**Листинг 3** демонстрирует, что отправка сообщений существенно отличается от стандартных методов в Drupal. Это вызывает
закономерные вопросы. Например, как перехватывать письма определённого модуля или типа? Модуль не даёт ответа на этот
вопрос. Вероятно, нам предлагается рассмотреть подход с созданием собственных типизированных Email-классов. Пример:

```php
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\Mime\Email;

final class CommerceOrderReceiptEmail extends Email {

  public function __construct(
    public OrderInterface $order,
  ) {}

}
```

Однако адаптация всех модулей под такую систему потребует много времени. На какое-то время в контрибах и проектах может
возникнуть путаница. Но для этого и нужен **экспериментальный** модуль. Возможно, в него добавят промежуточные слои для
текущей системы. Для собственных проектов это вполне подходящее решение, которое определённо удобнее и проще в
настройке, чем метод из статьи про [отправку писем с использованием ООП и Dependency Injection][oop-and-di-mail].

К слову о событиях — приведу небольшой пример их использования:

::::: figure
::: figcaption
**Листинг 4.** Пример использования событий для обработки электронных писем.
:::
  ```php
  use Composer\EventDispatcher\EventSubscriberInterface;
  use Drupal\Core\Render\RendererInterface;
  use Psr\Log\LoggerInterface;
  use Symfony\Component\Mailer\Event\FailedMessageEvent;
  use Symfony\Component\Mailer\Event\MessageEvent;
  use Symfony\Component\Mailer\Event\SentMessageEvent;
  use Symfony\Component\Mime\Email;
  
  final readonly class EmailSubscriber implements EventSubscriberInterface {
  
    public function __construct(
      private RendererInterface $renderer,
      private LoggerInterface $logger,
    ) {}
  
    public static function getSubscribedEvents(): array {
      return [
        MessageEvent::class => 'onMessagePrepare',
        SentMessageEvent::class => 'onMessageSent',
        FailedMessageEvent::class => 'onMessageFailed',
      ];
    }
  
    public function onMessagePrepare(MessageEvent $event): void {
      $message = $event->getMessage();
      if (!$message instanceof Email) {
        return;
      }
  
      $email_wrapper = [
        '#theme' => 'email_wrapper',
        '#message' => $message,
      ];
      $email_html = $this->renderer->renderInIsolation($email_wrapper);
      $message->html($email_html);
    }
  
    public function onMessageSent(SentMessageEvent $event): void {
      if (!$event->getMessage()->getOriginalMessage() instanceof Email) {
        return;
      }
  
      $this->logger->error('Email was successfully sent.', ['message_id' => $event->getMessage()->getMessageId()]);
    }
  
    public function onMessageFailed(MessageEvent $event): void {
      if (!$event->getMessage() instanceof Email) {
        return;
      }
  
      $this->logger->error('Email was not sent.', [
        'message' => $event->getMessage(),
        'transport' => $event->getTransport(),
      ]);
    }
  
  }
```
:::::

Пример с HTML-обёрткой в **листинге 4** отправит HTML «как есть» — без инъекции inline-стилей и других преобразований.
Для этого потребуются сторонние решения и зависимости[^twig-inline-css].[^symfony-mailer-html-css]

## Добавлена поддержка HTMX

В ядре началась работа по внедрению HTMX. Постепенно он заменит AJAX-подсистему и возьмёт на себя её функции.
**HTMX**[^htmx][^little-htmx-book] — декларативная система разметки, которая расширяет возможности HTML с помощью
специальных атрибутов.

Основные изменения:

- Новый класс `Drupal\Core\Htmx\Htmx` — для работы с атрибутами и заголовками HTMX.[^htmx-ajax-subsystem]
- Класс `Drupal\Core\Render\Hypermedia\HtmxLocationResponseData` — для управления данными.[^htmx-ajax-subsystem]
- Поддержка HTMX-запросов в `FormBuilder`.[^htmx-ajax-subsystem]
- Интеграция HTMX с AJAX-подсистемой.[^htmx-ajax-subsystem]
- Новый [формат обёртки][main-content-renderer] `drupal_htmx` (`Drupal\Core\Render\MainContent\HtmxRenderer`) — для
  ответов с использованием HTMX.[^htmx-ajax-subsystem]
- Добавлена новая опция маршрута (`_htmx_route`) — для маршрутов, обрабатывающих HTMX-запросы (`drupal_htmx`, упомянутый
  выше).[^htmx-route]
- Добавлен новый трейт `Drupal\Core\Htmx\HtmxRequestInfoTrait`.[^htmx-request-trait] Он помогает классам формировать
  рендер-массивы для HTMX.
- Форма `ConfigSingleExportForm` теперь динамически обновляет URL — это происходит в зависимости от её
  состояния.[^config-single-export-form-htmx]

Пока у меня нет опыта работы с HTMX, поэтому сложно оценить его практическую пользу. Однако инструмент теоретически
хорошо подходит для ленивой подгрузки тяжёлых данных с бэкенда. Принцип прост: когда элемент попадает в область
видимости, он заменяется содержимым. Вся реализация остаётся на бэкенде — писать JavaScript не требуется.

Вот пример: обычная форма становится AJAX-формой. Валидация и отправка происходят без перезагрузки страницы. Если всё
прошло успешно, форма заменяется сообщением об успешной отправке.

::::: figure
::: figcaption
**Листинг 5.** Пример формы, отправка которой будет производиться при помощи HTMX
:::
```php
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Htmx\Htmx;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

final readonly class HtmxForm implements FormInterface {

  public function getFormId(): string {
    return 'example_htmx';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form_url = Url::fromRoute('<current>', options: [
      'query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_htmx'],
    ]);
    (new Htmx())->post($form_url)->target('this')->swapOob(TRUE)->applyTo($form);

    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -1000,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => new TranslatableMarkup('Email address'),
      '#required' => TRUE,
      '#placeholder' => 'example@example.com',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => new TranslatableMarkup('Submit'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getValue('email') === 'example@example.com') {
      return;
    }

    $form_state->setError($form['email'], new TranslatableMarkup('You have entered the wrong email address.'));
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form = [];
    $form['success'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'status' => [
          new TranslatableMarkup('Form successfully submitted.'),
        ],
      ],
      '#status_headings' => [
        'status' => new TranslatableMarkup('Success'),
      ],
    ];
  }

}
```
:::::

::::: figure
:: video [Пример AJAX формы на HTMX](video/htmx-form.mp4){muted autoplay loop}
::: figcaption
**Видео 1.** Демонстрация формы с HTMX обёрткой
:::
:::::

## Добавлена встроенная CLI-утилита и API для экспорта содержимого в YAML-формате

Теперь можно экспортировать контентные сущности в YAML-формате с помощью встроенной CLI-утилиты и
API.[^content-export][^content-export-improvements] Раньше для этого требовался сторонний модуль, например
**Default Content**[^default-content].

Для экспорта конкретных сущностей используйте новую команду:

```bash
php web/core/scripts/drupal content:export <entity_type_id> <entity_id>
```

::: tip [Импорт содержимого]
Функционал импорта доступен начиная с более ранних версий, но отдельной команды для него нет. Чтобы
импортировать содержимое, нужно воспользоваться сервисом `Drupal\Core\DefaultContent\Importer`.
:::

По умолчанию результат выводится в стандартный поток вывода (stdout). Чтобы сохранить его в файл, нужно использовать
перенаправление.

::::: figure
  ::: figcaption
    **Листинг 6.** Пример использования команды `content:export` для экспорта содержимого в YAML-формате и результат
    выполнения.
  :::
  ```bash
  php web/core/scripts/drupal content:export node 19 > node-19.yml
  ```

  ```yaml
  default:
    revision_uid:
      -
        entity: ec6b7ed6-2a25-412b-884b-608493045a7f
    status:
      -
        value: true
    uid:
      -
        entity: ec6b7ed6-2a25-412b-884b-608493045a7f
    title:
      -
        value: 'About Umami'
    promote:
      -
        value: false
    sticky:
      -
        value: false
    moderation_state:
      -
        value: published
    path:
      -
        alias: /about-umami
        langcode: en
    content_translation_source:
      -
        value: und
    content_translation_outdated:
      -
        value: false
    field_body:
      -
        value: '<p>Umami is a fictional food magazine that has been created to demonstrate how you might build a Drupal site using functionality provided ''out of the box''.</p><p>For more information visit <a href="https://www.drupal.org/docs/umami-drupal-demonstration-installation-profile">https://www.drupal.org/docs/umami-drupal-demonstration-installation-profile</a>.</p>'
        format: basic_html
  translations:
    es:
      revision_uid:
        -
          entity: ec6b7ed6-2a25-412b-884b-608493045a7f
      status:
        -
          value: true
      uid:
        -
          entity: ec6b7ed6-2a25-412b-884b-608493045a7f
      title:
        -
          value: 'Acerca de Umami'
      promote:
        -
          value: false
      sticky:
        -
          value: false
      revision_translation_affected:
        -
          value: true
      moderation_state:
        -
          value: published
      path:
        -
          alias: /acerca-de-umami
          langcode: es
      content_translation_source:
        -
          value: und
      content_translation_outdated:
        -
          value: false
      field_body:
        -
          value: '<p> Umami es una revista ficticia de alimentos que se ha creado para demostrar cómo se puede construir un sitio de Drupal con la funcionalidad que se proporciona ''fuera de la caja''. </p> <p> Para obtener más información, visite <a href="https://www.drupal.org/docs/umami-drupal-demonstration-installation-profile">https://www.drupal.org/docs/umami-drupal-demonstration-installation-profile</a>.</p> '
          format: basic_html
  _meta:
    version: '1.0'
    entity_type: node
    uuid: 7889c36d-3ba6-4e32-8f15-d290925c46e0
    bundle: page
    default_langcode: en
    depends:
      ec6b7ed6-2a25-412b-884b-608493045a7f: user
  ```
:::::

::: note
Обратите внимание что `created`[^default-content-created-ignore] и некоторые другие поля по умолчанию не экспортируется.
:::

Если вы хотите выгрузить данные в определённую директорию, используйте опцию `--dir`:

```php
php web/core/scripts/drupal content:export node 123 --dir=content
```

Эта команда сохранит содержимое по пути `content/node/{UUID}.yml`. Опционально можно добавить опцию
`--with-dependencies`, в таком случае будут также выгружены и все зависимости экспортируемой сущности в формате:
`{dir}/{entity_type_id}/{uuid}.yml`.

Для программного экспорта данных используйте новый сервис `Drupal\Core\DefaultContent\Exporter`.

::::: figure
  ::: figcaption
    **Листинг 7.** Примеры использования сервиса `Drupal\Core\DefaultContent\Exporter` для программного экспорта
    содержимого.
  :::
```php
use Drupal\Core\DefaultContent\Exporter;
use Drupal\node\Entity\Node;

$exporter = $container->get(Exporter::class);
// Экспорт в массив.
$content_structure = $exporter->export($entity_storage->load(19));
// Экспорт в директорию.
$exporter->exportToFile($entity_storage->load(19), 'content');
// Экспорт в директорию со всеми зависимостями.
$exporter->exportWithDependencies($entity_storage->load(19), 'content');
```
:::::

Также добавлено новое событие `Drupal\Core\DefaultContent\PreExportEvent`, позволяющее управлять экспортом данных. Оно
даёт возможность:

- Настраивать включение/исключение ключей сущностей (`uuid`, `langcode`, `status` и т.д.) через метод
  `::setEntityKeyExportable()`;
- Контролировать экспорт отдельных полей с помощью метода `::setExportable()`;
- Влиять на результат экспорта через функцию обратного вызова в методе `::setCallback()`, который обрабатывает каждое
  значение поля отдельно.

Метод `::setCallback()` работает в двух режимах:

- При совпадении параметра `$name_or_data_type` с названием поля — управляет экспортом конкретного поля;
- При указании типа поля через префикс `field_item:` (например, `field_item:entity_reference`) — применяет настройки ко
  всем полям этого типа.

::::: figure
  ::: figcaption
    **Листинг 8.** Пример использования события `Drupal\Core\DefaultContent\PreExportEvent` для управления
    данными при использовании программного выгрузки содержимого.
  :::
  ```php
  use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
  use Drupal\Core\DefaultContent\ExportMetadata;
  use Drupal\Core\DefaultContent\PreExportEvent;
  use Drupal\Core\Field\FieldItemInterface;
  use Symfony\Component\EventDispatcher\EventSubscriberInterface;
  
  final readonly class FooSubscriber implements EventSubscriberInterface {
  
    public static function getSubscribedEvents(): array {
      return [PreExportEvent::class => 'preExport'];
    }
  
    public function preExport(PreExportEvent $event): void {
      $entity = $event->entity;
      if ($entity->getEntityTypeId() !== 'commerce_product_variation') {
        return;
      }
  
      // Обратите внимание, что здесь именно ключ, а не его синоним.
      // @code
      // …
      // entity_keys: [
      //   'owner' => 'uid',
      // ],
      // …
      // @endcode
      $event->setEntityKeyExportable('owner', FALSE);
      // По умолчанию UUID скрывается.
      $event->setEntityKeyExportable('uuid');
  
      $event->setExportable('field_in_stock', FALSE);
      $event->setExportable('field_primary_category');
  
      $event->setCallback('field_item:commerce_price', $this->formatPrice(...));
    }
  
    private function formatPrice(FieldItemInterface $item, ExportMetadata $metadata): void {
      \assert($item instanceof PriceItem);
      return $item->get('formatted')->getValue();
    }
  
  }
  ```
:::::

## Добавлен экспериментальный `MySQLi` драйвер для асинхронных запросов к базе данных

Добавлен **экспериментальный** драйвер **MySQLi**[^mysqli] для **MySQL**/**MariaDB**, использующий PHP-расширение
`mysqli`.[^mysqli-change] Планируется задействовать **Revolt**[^revolt] PHP event loop для ускорения операций (например,
загрузка сущностей с множеством полей, сложных представлений). Драйвер скрыт и предназначен только для тестирования.

::::: figure
::: figcaption
**Листинг 9.** Настройка драйвера MySQLi в `settings.php`
:::
  ```php
    $databases['default']['default'] = [
      // Прочие настройки, такие как подключение к базе данных, неизменны.
      'driver' => 'mysqli',
      'namespace' => 'Drupal\\mysqli\\Driver\\Database\\mysqli',
      'autoload' => 'core/modules/mysqli/src/Driver/Database/mysqli/',
      'dependencies' => [
        'mysql' => [
          'namespace' => 'Drupal\\mysql',
          'autoload' => 'core/modules/mysql/src/',
        ],
      ],
    ];
```
:::::

Если вдруг «зачесались» руки проверить «ускорение» — не тратьте время. Я уже всё проверил.

Грубый **тест производительности** с использованием `demo_umami` профиля
(`siege -c 10 -t 60s --no-parser --no-follow [URL]` с отключёнными кешами: `render`, `entity`, `data`, `page`,
`dynamic_page_cache`):
- **Главная страница:**
  - `mysql` → 235 мс
  - `mysqli` → 283 мс
  - **Итог:** замедление ~20%
- **Страница статей** (`/en/articles`):
  - `mysql` → 219 мс
  - `mysqli` → 260 мс
  - **Итог:** замедление ~18%
- **Страница рецепта** (`/en/recipes/borscht-with-pork-ribs`):
  - `mysql` → 248 мс
  - `mysqli` → 320 мс
  - **Итог:** замедление ~29%

Я также скопировал 10 000 статей с переводами, затем измерил скорость загрузки всех сущностей (повторюсь — без кеша
`entity`).

::::: figure
::: figcaption
**Листинг 10.** Измерение производительности загрузки сущностей
:::
```php
use Drupal\Component\Utility\Timer;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

$iterations = 10;
Timer::start('mysql-v-mysqli');
for ($i = 0; $i < $iterations; $i++) {
  $container->get(EntityTypeManagerInterface::class)->getStorage('node')->loadMultiple();
  $container->get(MemoryCacheInterface::class)->deleteAll();
}
Timer::stop('mysql-v-mysqli');

echo Timer::read('mysql-v-mysqli') / $iterations;
```
:::::

Для `mysql` время выполнения составило 1218 мс, а для `mysqli` — 1533 мс. Это замедление примерно на 25%.

Текущая версия драйвера — основа для будущих оптимизаций. Даже если использовать Event Loop для запросов в вашем коде,
останется проблема множества запросов от Drupal и сторонних модулей. Пока зафиксируем добавление драйвера и подождём
дополнительных улучшений.

## Дополнительные улучшения и изменения

### API и разработка

- Добавлен метод `::mergeWith()` в класс `AjaxResponse`. Он позволяет объединять AJAX-команды из разных экземпляров
  `AjaxResponse` в один ответ.[^ajax-response-merge-with]
- Добавлена поддержка сокращённого синтаксиса `@>` вместо `!service_closure` в YAML-файлах
  [сервисов][services].[^service-closure-shorthand]
- Метод `EntityController::addPage()` теперь ожидает новый параметр `$request`.[^entity-controller-request-param]
- Для AJAX-представлений добавили команду, которая устанавливает URL в браузере без перезагрузки
  страницы.[^views-ajax-window-history]
- Добавлено событие `WorkspaceSwitchEvent` для реагирования на смену активного рабочего пространства.[^workspace-switch-event]
- Класс `Drupal\taxonomy\Form\OverviewTerms` теперь наследуется от `Drupal\Core\Entity\EntityForm`. Это улучшает
  расширяемость формы обзора терминов словарей.[^overview-terms-extends-entity-form]
- Добавлены новые синонимы для маршрутов: `node.add` → `entity.node.add_form` и `node.add_page` →
  `entity.node.add_page`.[^node-route-aliases] Это сделано в рамках стандартизации генерации маршрутов.
- Добавлена фабрика (сервис: `config.importer.factory`) для создания объекта `ConfigImporter`.[^config-importer-factory]
- В интерфейс `Drupal\Core\State\StateInterface` добавлен метод `::getValuesSetDuringRequest()`. Он позволяет отследить,
  как менялись значения в пределах одного HTTP-запроса.[^state-get-values-during-request]
- [Batch API][batch-api] теперь использует `CallableResolver` для обработки функций обратного
  вызова.[^batch-api-callable-resolver] Это позволяет применять сервисы для операций пакетной обработки.
- Form API теперь также использует `CallableResolver` для обработки функций обратного
  вызова.[^form-api-callable-resolver]
- В класс `Drupal\Component\Utility\SortArray` добавлен статический метод `::sortByKeyRecursive()`. Он выполняет
  рекурсивную сортировку массивов по ключам.[^sort-by-key-recursive]
- Обработчики форм в определениях типов сущностей и маршрутах с параметром `_entity_form` теперь должны явно
  реализовывать интерфейс `Drupal\Core\Entity\EntityFormInterface`.[^entity-form-handlers-required-interface]
- Введен новый интерфейс `Drupal\Core\Validation\CompositeConstraintInterface`, который служит мостом между составными
  ограничениями Symfony и Drupal.[^composite-constraint-interface]
- Добавлено новое ограничение `Sequentially` для последовательной валидации с показом только первой
  ошибки.[^sequentially-constraint]
- Добавлен опциональный обработчик `link_target` для типов сущностей, позволяющий единообразно генерировать URL-адреса
  при создании ссылок.[^link-target-handler]
- Добавлены stream wrapper'ы `module://` и `theme://` для удобного обращения к файлам в модулях и темах с автоматической
  проверкой доступности расширений.[^stream-wrappers-module-theme]
- Добавлено событие `PreEntityImportEvent`, позволяющее изменять данные сущности перед созданием в процессе импорта
  содержимого.[^pre-entity-import-event]
- Добавлен новый [плагин автодополнения сущностей][entity-reference-selection] для полей-ссылок на сущности типа
  `block_content`. Теперь блоки, которые не помечены как «Переиспользуемый», автоматически исключаются из
  результатов автодополнения.[^block-content-selection]

### Производительность и кеширование

- Теперь автоматически генерируется класс `Drupal\DrupalInstalled`. Он содержит хеш всех установленных зависимостей
  проекта в константе `VERSIONS_HASH`.[^drupal-installed-hash] Это значение будет использоваться для генерации ID кеша
  контейнера. При изменении кодовой базы и её зависимостей, будет создан новый контейнер.
- Работа Page Cache Middleware была оптимизирована благодаря использованию Service
  Closure.[^page-cache-middleware-service-closure] Это позволяет ускорить обработку кешированных страниц.
- Метод `EntityStorageBase::loadMultiple()` теперь использует Fiber'ы[^fiber-docs] для ленивой загрузки нескольких
  сущностей.[^lazy-load-multiple-entities]
- Удалён устаревший кеш предзагрузки синонимов путей. Вместо него внедрён новый механизм, который основан на
  Fiber'ах[^fiber-docs] и предназначен для оптимизации загрузки синонимов.[^lazy-load-multiple-aliases]
- Введен новый кеш-контейнер (cache bin) — `cache.memory`.[^cache-memory] Он заменяет устаревший `cache.static`.
- Блоки, которые реализуют интерфейс `Drupal\Core\Cache\CacheOptionalInterface`, не будут иметь собственных записей в
  рендер-кеше. Благодаря этому можно будет кешировать страницы с такими блоками в динамическом
  кеше.[^cache-optional-dynamic-cache-bin]
- Метод `ContentEntityStorageBase::loadRevision()` теперь включает механизмы статического и постоянного
  кеширования.[^load-revision-static-and-persistent-cache]
- Логика управления памятью в `MigrateExecutable` была удалена. Теперь для статического кеширования сущностей
  применяется LRU-кеш[^lru-cache].[^migrate-executable-memory-clear]
- Для оптимизации работы с Fiber'ами добавлено перечисление `FiberResumeType`. Оно позволяет указать, что
  приостановленный Fiber может быть немедленно возобновлён.[^fiber-resume-type]
- Теперь метод `MemoryBackend::garbageCollection()` реализует очистку кеша. Он удаляет из памяти элементы, помеченные
  как невалидные (например, с истекшим сроком действия).[^memory-backend-gargage-collection] Раньше этот метод был
  пустым. Из-за этого в оперативной памяти постоянно накапливались неиспользуемые данные — невалидные записи не
  удалялись автоматически.
- Работа системы хуков изменена: теперь хуки не выступают в роли слушателей
  событий.[^oop-hooks-no-longer-event-listeners] Такое изменение помогает улучшить производительность системы.

### Устаревшая функциональность и изменения в обратной совместимости

- Метод `FileSystemInterface::basename()` признан устаревшим. Теперь рекомендуется использовать нативную PHP-функцию
  `basename()`.[^file-system-basename]
- Хук `hook_ranking()` признан устаревшим. В качестве замены введена функция
  `hook_node_search_ranking()`.[^hook-ranking]
- Классы, трейты, плагины и компоненты, связанные с миграцией данных из старых версий Drupal, признаны устаревшими. Это
  сделано в рамках подготовки к полному удалению модуля Drupal Migrate в 
  Drupal 12.[^drupal-migrate-deprecations][^migrate-field-plugins-deprecations]
- Модуль **Migrate Drupal UI**, предоставляющий пользовательский интерфейс для миграции данных из предыдущих версий
  Drupal, объявлен устаревшим.[^migrate-drupal-ui-deprecated]
- Многочисленные [плагины миграции][migrate-api] объявлены устаревшими.[^legacy-migrate-deprecations] Они предназначены
  для переноса данных с устаревших версий Drupal.
- Библиотеки `comment/drupal.comment-new-indicator` и `comment/drupal.node-new-comments-link` объявлены устаревшими и 
  заменены на аналоги из модуля history.[^comment-libraries-deprecation]
- Рефакторинг модуля Responsive Image: устаревшие функции заменены методами сервиса `ResponsiveImageBuilder` для
  улучшения структуры кода и перехода на ООП-подход.[^responsive-image-builder]
- Маршрут `comment.new_comments_node_links` и метод контроллера `Drupal\comment\Controller\CommentController::renderNewCommentsNodeLinks`
  объявлены устаревшими.[^render-new-comments-node-links] Вместо них рекомендуется использовать новый маршрут
  `history.new_comments_node_links` и метод `Drupal\history\Controller\HistoryController::renderNewCommentsNodeLinks`.
- Метод `WorkspaceManagerInterface::purgeDeletedWorkspacesBatch()` объявлен устаревшим без
  замены.[^workspace-purge-deleted-removed]
- Функция `_system_default_theme_features()` объявлена устаревшей.[^system-default-theme-features-deprecated]
- Метод `theme_get_setting()` объявлен устаревшим; вместо него нужно использовать сервис
  `Drupal\Core\Extension\ThemeSettingsProvider`.[^theme-get-setting-deprecated]
- Методы `ModuleHandler::addProfile()` и `ModuleHandler::addModule()` больше не выполняют никаких действий и
  запланированы к удалению в Drupal 12.[^module-handler-add-profile-module]
- Функция `file_managed_file_submit()` объявлена устаревшей и заменена методом
  `\Drupal\file\Element\ManagedFile::submit()`.[^file-managed-file-submit]
- Объявлено устаревшим расширение `.engine` для шаблонизаторов. Теперь шаблонизаторы должны реализовываться как
  сервисы с меткой `theme_engine`.[^engine-extension-deprecated]
- Сервис `workspaces.association` объявлен устаревшим, добавлен новый сервис
  `workspaces.tracker`.[^workspace-association-deprecated]
- Вызов метода `Drupal\Core\Entity\EntityTypeBundleInfo::getBundleInfo()` с нестроковым идентификатором типа сущности
  (например, `NULL`) объявлен устаревшим.[^get-bundle-info-string-only]
- Объявлено устаревшим сопоставление `NULL`-значений через пустой ключ в плагине `StaticMap` без указания значения по
  умолчанию или опции bypass.[^static-map-null-map]
- Объявлен устаревшим модуль **Field Layout**.[^field-layout-deprecated]
- Метод `Drupal\comment\CommentManagerInterface::getCountNewComments()` объявлен устаревшим. Рекомендуется использовать
  `Drupal\history\HistoryManager::getCountNewComments()`.[^get-count-new-comments-deprecated] Токен `comment-count-new`
  перемещён из модуля comment в модуль history.
- Удален кастомный валидатор `AtLeastOneOfConstraintValidator` в пользу стандартной реализации
  Symfony.[^at-least-one-of-constraint-validator]
- Пакет `doctrine/annotations` был форкнут в ядро Drupal для поддержки устаревшего функционала аннотаций до версии
  Drupal 13.[^doctrine-annotations-fork]
- Следующие классы объявлены устаревшими: `ArchiverException`, `ArchiverInterface`, `ArchiverManager`, `Tar`,
  `Zip`.[^archive-manager-deprecated] Класс `ArchiveTar` (обёртка вокруг PEAR Archive_Tar) остаётся доступным для
  использования в интерфейсе импорта и экспорта конфигурации.
- Обращение к `$this->container` в функциональных тестах теперь объявлено устаревшим.[^functional-test-magic-container]
- Вызов метода `FieldStorageDefinitionInterface::getPropertyDefinition()` с параметром `$name`, равным `NULL`, теперь
  объявлен устаревшим.[^field-storage-definition-get-property-definition]
- Функция `content_translation_field_sync_widget` и файл `content_translation.admin.inc` объявлены устаревшими. Теперь
  следует использовать сервис `FieldSyncWidget`.[^field-sync-widget]
- Метод `ImageStyle::getReplacementID()` объявлен устаревшим, поскольку он не
  используется.[^image-style-get-replacement-id] Теперь для получения ID замены нужно обращаться к хранилищу сущностей.
- Единый хук `hook_requirements()` объявлен устаревшим. Теперь для проверок системных требований на разных этапах работы
  сайта введены отдельные хуки и интерфейс.[^hook-requirements-deprecated]
- Модуль **Contact** больше не входит в состав **Standard**[^standard-profile-contact] и
  **Demo Umami**[^demo-umami-profile-contact] профилей. В будущем его планируют пометить как устаревший и перевести в
  разряд контрибных модулей.[^contact-contrib]

### Пользовательский интерфейс и UX

- Поля «Помещено на главную страницу» (Promoted) и «Закреплять вверху списков» (Sticky) по умолчанию скрыты для всех
  новых типов содержимого.[^promoted-sticky-hidden] При этом сами поля не изменились — вы можете вернуть их в форму
  редактирования материала через раздел «Управление отображением формы» для нужного типа материала.
- Теперь можно управлять порядком элементов в верхней панели (top bar в navigation).[^top-par-item-weight]
- Если ограничение размера файла не задано, его отображение при загрузке удалено.[^no-size-limit-file-upload]
- Появился новый фильтр Entity Links и плагин для CKEditor 5. Они позволяют создавать ссылки на сущности через
  автодополнение.[^ckeditor-5-entity-links]
- Для Single-Directory Components (SDC) добавлено свойство `noUi`. Оно позволяет скрывать компоненты в интерфейсах
  конструкторов страниц.[^sdc-noui]

### Темы и фронтенд

- Добавлена возможность помечать устаревшие suggestions для [тем-хуков][hook-theme] с указанием сообщения об их замене
  или удалении в будущих версиях.[^theme-hook-suggestions-deprecation]
- Добавлена поддержка рендер-массивов и `MarkupInterface` внутри Twig-тега `{% trans %}`.[^twig-trans] Теперь эти типы
  данных можно безопасно использовать в переводах — раньше это вызывало ошибки и предупреждения.
- Переход на использование `yield` в рендеринге Twig — это подготовка к Twig 4 и поддержка асинхронной
  обработки.[^twig-yield]
- Объявлены устаревшими функции предварительной обработки шаблонов (`template_preprocess_HOOK`), а также ключи `file` и
  `includes` в определениях [hook_theme()][hook-theme].[^template-preprocess-deprecated]
- Введено новое средство для разрешения доступа Twig-шаблонов к методам объектов — атрибут
  `#[TwigAllowed]`.[^twig-allowed]
- Для панели инструментов Navigation и верхней панели административного интерфейса добавлен
  CSS-сброс.[^navigation-css-reset] Это поможет предотвратить влияние стилей внешней темы на их оформление.

### Содержимое и структура данных

- Улучшено обновление временных меток: при публикации рабочего пространства (модуль Workspace) временная метка изменения
  автоматически обновляется для всех связанных сущностей.[^workspace-publish-updated]

### Модули и расширяемость

- Валидация для конфигурации `core.extension` улучшена — добавлены ограничения для проверки корректности
  данных.[^core-extension-constraints]
- Теперь хуки-функции относятся к текущему модулю, а не к наиболее специфичному соответствию, как было
  раньше.[^legacy-hooks-attribution]
- Создание плагинов стало проще благодаря внедрению автосвязывания.[^plugin-base-autowire] Теперь для указания
  зависимостей можно использовать аттрибуты вместо явного определения метода `create()`.[^poor-people-plugin-autowire]
- Добавлена система **Workspace Provider**. Это плагин-подобная архитектура, основанная на
  [сервисах с метками][tagged-services], которая позволяет создавать пользовательские типы рабочих
  пространств (workspaces) с особым поведением.[^workspace-provider]
- Теперь плагины, которые используются сущностями с коллекцией плагинов, могут реагировать на удаление зависимостей
  этих сущностей.[^plugin-on-collection-dependency-removal]
- Модуль **Navigation** стал стабильным.[^navigation-stable]

### Конфигурация и развертывание

- Добавлена возможность отключить автоматическое создание файлов `.htaccess` с помощью параметра
  `$settings['auto_create_htaccess'] = FALSE`.[^auto-create-htaccess] Это пригодится для сайтов, которые не используют
  Apache или уже имеют защиту директорий (например, `sites/default/files`) на уровне сервера.
- Добавлена возможность исключать отдельные пакеты из процесса очистки с помощью плагина `drupal/core-vendor-hardening`
  в [Composer][drupal-composer].[^vendor-hardening-skip-cleaning]
- Удалена возможность настраивать пути к Composer и rsync через конфигурацию (`package_manager.settings:executables`).
  Вместо этого в `settings.php` добавлены новые параметры — `package_manager_composer_path` и
  `package_manager_rsync_path`. Они позволяют явно указывать пути, что повышает
  безопасность.[^composer-rsync-path-settings]
- Библиотека `justinrainbow/json-schema` теперь считается полноценной зависимостью, а не только зависимостью для
  разработки.[^justinrainbow-json-schema]
- Теперь при переключении сайта в режим обслуживания или выходе из него в лог по умолчанию (`default`) записывается
  соответствующее сообщение.[^maintenance-mode-switch-log]
- Экспортёр стандартного содержимого (default content) теперь по умолчанию пропускает поля типа `created` при генерации
  экспорта.[^default-content-created-ignore]

### Тестирование и качество кода

- Введено обязательное требование использовать атрибут `#[RunTestsInSeparateProcesses]` для всех Kernel, Functional и
  FunctionalJavascript тестов.[^run-tests-in-separate-processes]
- Добавлена возможность использования hook-атрибутов для объявления реализаций хуков в 
  Kernel-тестах.[^kernel-hook-attributes]
- Удалена поддержка PHPUnit 10 — теперь для тестирования можно использовать только PHPUnit 11.[^phpunit-10-removed]

### Доступ и разрешения

- Теперь доступно новое разрешение `rebuild node access permissions`, которое позволяет перестроить разрешения для типов
  материалов (node).[^rebuild-node-access] Раньше для этого нужно было иметь разрешение `administer nodes`.
- Введено новое разрешение `administer node published status`. Оно даёт пользователям определённой роли возможность
  переключать флажок «Опубликовано» при редактировании материалов.[^administer-node-published-status]

### Многоязычность и интернационализация

- Атрибут `hreflang` в блоке переключения языков заменён на `data-drupal-language`.[^data-drupal-language]

### Системные требования и совместимость

- Выполнены работы по обеспечению совместимости кодовой базы с PHP 8.5[^php-8-5].
- Рекомендованная версия PHP — 8.4[^php-8-4].[^php-8.4-recommendation]
- Классы подключения к базе данных (БД) для PHP 8.4+ обновлены. Для обновления использовались PDO-драйверы: `Pdo\Mysql`,
  `Pdo\Pgsql`, `Pdo\Sqlite`.[^pdo-drivers] Благодаря этому устранены предупреждения об устаревании в PHP 8.5 и
  обеспечена совместимость
- Несовместимость Single Directory Components (SDC) с Windows устранена. Для этого в именах файлов кеша Twig двоеточия
  заменили на подчёркивания.[^sdc-for-windows]

[render-arrays]: ../../../../2020/02/05/drupal-8-render-arrays/article.ru.md
[tagged-services]: ../../../../2019/05/05/drupal-8-tagged-services/article.ru.md
[oop-and-di-mail]: ../../../../2020/05/29/drupal-8-9-sending-emails-using-oop-and-dependency-injection/article.ru.md
[hook-theme]: ../../../../2017/06/26/drupal-8-hook-theme/article.ru.md
[services]: ../../../../2017/06/21/drupal-8-services/article.ru.md
[drupal-composer]: ../../../../2016/09/03/drupal-8-composer/article.ru.md
[main-content-renderer]: ../../../../2019/09/05/drupal-8-main-content-renderer/article.ru.md
[batch-api]: ../../../../2018/09/11/drupal-8-batch-api/article.ru.md
[entity-reference-selection]: ../../../../2019/08/30/drupal-8-entity-reference-selection-plugin/article.ru.md
[migrate-api]: ../../../../2017/10/22/drupal-8-migrate-api/article.ru.md

[^drupal-11-3-release]: [Drupal 11.3.0](https://www.drupal.org/project/drupal/releases/11.3.0). _Релизы Drupal._ На момент публикации материала страница ещё не
  существует.
[^mysqli]: [Улучшенный модуль MySQL (MySQL Improved)](https://www.php.net/manual/ru/book.mysqli.php). _Руководство по PHP_.
[^mysqli-change]: [A new database driver (mysqli) for MySQL/MariaDB for parallel queries](https://www.drupal.org/node/3516913).
  _История изменений Drupal Core_. 2025-06-19.
[^revolt]: [Revolt - The rock-solid event loop for PHP](https://revolt.run/). _Официальный сайт и документация._
[^form-oop-render-array]: [New Object oriented approach for working with form/render arrays](https://www.drupal.org/node/3532720).
  _История изменений Drupal Core_. 2025-06-27.
[^field-widget-oop-render-array]: [Widget elements can be written using object oriented approach](https://www.drupal.org/node/3532733).
  _История изменений Drupal Core_. 2025-06-27.
[^auto-create-htaccess]: [Automatic creation of .htaccess files can be disabled](https://www.drupal.org/node/3525119).
  _История изменений Drupal Core_. 2025-06-30.
[^content-export]: [content:export command added to help with recipe development](https://www.drupal.org/node/3533854).
  _История изменений Drupal Core_. 2025-07-15.
[^default-content]: [Default Content](https://www.drupal.org/project/default_content). _Проект на Drupal.org._
[^promoted-sticky-hidden]: [Promoted/Sticky fields are hidden by default for new Node Types](https://www.drupal.org/node/3518643).
  _История изменений Drupal Core_. 2025-07-30.
[^content-export-improvements]: [Support exporting content and its dependencies to a folder structure on disk](https://www.drupal.org/project/drupal/issues/3532951).
  _Задача на Drupal.org_.
[^mailer-module]: [Experimental Symfony Mailer Module](https://www.drupal.org/node/3519253). _История изменений Drupal
  Core_. 2025-05-13.
[^symfony-mailer-component]: [Symfony mailer component added as a composer dependency](https://www.drupal.org/node/3369935).
  _История изменений Drupal Core_. 2023-10-20.
[^symfony-mailer-docs]: [Sending Emails with Mailer](https://symfony.com/doc/current/mailer.html).
  _Официальная документация компонента `symfony/mailer`._
[^symfony-mailer-message-event]: [MessageEvent](https://symfony.com/doc/current/mailer.html#messageevent).
  _Официальная документация компонента `symfony/mailer`._
[^symfony-mailer-sent-message-event]: [SentMessageEvent](https://symfony.com/doc/current/mailer.html#sentmessageevent).
  _Официальная документация компонента `symfony/mailer`._
[^symfony-mailer-failed-message-event]: [FailedMessageEvent](https://symfony.com/doc/current/mailer.html#failedmessageevent).
 _Официальная документация компонента `symfony/mailer`._
[^symfony-messenger-docs]: [Messenger: Sync & Queued Message Handling](https://symfony.com/doc/current/messenger.html).
  _Официальная документация компонента `symfony/messenger`._
[^drupal-10-2-is-available]: [Drupal 10.2 is now available](https://www.drupal.org/blog/drupal-10-2-0).
  _Блог разработчиков Drupal_. 2025-12-15.
[^symfony-mailer-html-css]: [Twig: HTML & CSS](https://symfony.com/doc/current/mailer.html#twig-html-css).
  _Официальная документация компонента `symfony/mailer`._
[^theme-hook-suggestions-deprecation]: [Theme suggestions can now be deprecated](https://www.drupal.org/node/3535678).
  _История изменений Drupal Core_. 2025-08-05.
[^ajax-response-merge-with]: [Add mergeWith() to AjaxResponse for merging with another response](https://www.drupal.org/node/3486330).
  _История изменений Drupal Core_. 2025-06-20.
[^service-closure-shorthand]: [Added support for @> as a shorthand for !service_closure in services.yml files](https://www.drupal.org/node/3527390).
  _История изменений Drupal Core_. 2025-08-06.
[^vendor-hardening-skip-cleaning]: [The vendor hardening plugin can be configured to skip cleaning certain packages](https://www.drupal.org/node/3536166).
  _История изменений Drupal Core_. 2025-07-16.
[^drupal-installed-hash]: [Drupal Scaffold composer plugin generates a new \Drupal\DrupalInstalled class](https://www.drupal.org/node/3531162).
  _История изменений Drupal Core_. 2025-09-09.
[^composer-rsync-path-settings]: [Package Manager's path to Composer is no longer configurable](https://www.drupal.org/node/3540264).
  _История изменений Drupal Core_. 2025-09-09.
[^workspace-publish-updated]: [Publishing a workspace will update the changed time for its entities](https://www.drupal.org/node/3531039).
  _История изменений Drupal Core_. 2025-07-19.
[^htmx]: [</> htmx high power tools for HTML](https://htmx.org/). _Официальный сайт_.
[^little-htmx-book]: [Little HTMX Book](https://littlehtmxbook.com/). _Краткое руководство по HTMX_. 2025-09-11.
[^htmx-ajax-subsystem]: [Ajax subsystem now includes HTMX](https://www.drupal.org/node/3539472).
  _История изменений Drupal Core_. 2025-09-11.
[^htmx-renderer]: [Wrapper format to use HtmxRenderer added](https://www.drupal.org/node/3544666).
  _История изменений Drupal Core_. 2025-09-11.
[^twig-trans]: [{% trans %} Twig tag can contain rendered expressions that return render arrays and MarkupInterface objects](https://www.drupal.org/node/2615198).
  _История изменений Drupal Core_. 2025-09-16.
[^pdo-drivers]: [Using specific PDO drivers instead of PDOConnection on PHP 8.4+](https://www.drupal.org/node/3547277).
  _История изменений Drupal Core_. 2025-09-18.
[^file-system-basename]: [FileSystemInterface::basename() deprecated, use PHP native basename() instead](https://www.drupal.org/node/3530869).
  _История изменений Drupal Core_. 2025-09-19.
[^hook-ranking]: [hook_ranking() has been renamed to hook_node_search_ranking()](https://www.drupal.org/node/2690393).
  _История изменений Drupal Core_. 2025-09-19.
[^twig-yield]: [Twig rendering now uses yield](https://www.drupal.org/node/3546663).
  _История изменений Drupal Core_. 2025-09-22.
[^page-cache-middleware-service-closure]: [Page Cache Middleware uses Service Closure to speed up serving cached pages](https://www.drupal.org/node/3538740).
  _История изменений Drupal Core_. 2025-09-25.
[^drupal-migrate-deprecations]: [Migrate source plugins for legacy upgrade are deprecated](https://www.drupal.org/node/3533564).
  _История изменений Drupal Core_. 2025-09-25.
[^comment-libraries-deprecation]: [The comment/drupal.comment-new-indicator and comment/drupal.node-new-comments-link libraries have been deprecated](https://www.drupal.org/node/3537055).
  _История изменений Drupal Core_. 2025-09-25.
[^responsive-image-builder]: [_responsive_image_build_source_attributes(), responsive_image_get_image_dimensions(), responsive_image_get_mime_type(), _responsive_image_image_style_url() replaced with ResponsiveImageBuilder](https://www.drupal.org/node/3548329).
  _История изменений Drupal Core_. 2025-09-25.
[^fiber-docs]: [Официальная документация файберов](https://www.php.net/manual/ru/language.fibers.php).
[^lazy-load-multiple-entities]: [Lazy load multiple entities at a time using fibers](https://www.drupal.org/project/drupal/issues/1237636).
  _Задача на Drupal.org_. 2025-09-29.
[^lazy-load-multiple-aliases]: [The path alias preload cache has been removed](https://www.drupal.org/node/3532412).
  _История изменений Drupal Core_. 2025-09-30.
[^legacy-hooks-attribution]: [Legacy hook functions are now attributed to the current module instead of the most specific match](https://www.drupal.org/node/3548085).
  _История изменений Drupal Core_. 2025-10-08.
[^migrate-field-plugins-deprecations]: [Migrate field plugins are deprecated](https://www.drupal.org/node/3533566).
  _История изменений Drupal Core_. 2025-10-08.
[^oop-hooks-no-longer-event-listeners]: [Hooks are no longer event listeners](https://www.drupal.org/node/3550627).
  _История изменений Drupal Core_. 2025-10-11.
[^entity-controller-request-param]: [EntityController::addPage now requires the $request parameter](https://www.drupal.org/node/3546628).
  _История изменений Drupal Core_. 2025-10-11.
[^render-new-comments-node-links]: [The comment.new_comments_node_links route and CommentController::renderNewCommentsNodeLinks are deprecated](https://www.drupal.org/node/3543039).
  _История изменений Drupal Core_. 2025-10-13.
[^run-tests-in-separate-processes]: [#[RunTestsInSeparateProcesses] attribute is required for all Kernel, Functional and FunctionalJavascript tests](https://www.drupal.org/node/3548485).
  _История изменений Drupal Core_. 2025-10-13.
[^htmx-request-trait]: [New trait assists classes building render arrays for HTMX.](https://www.drupal.org/node/3549174).
  _История изменений Drupal Core_. 2025-10-14.
[^rebuild-node-access]: [Access to rebuild node permissions now requires the "rebuild node access permissions" permission](https://www.drupal.org/node/3521446).
  _История изменений Drupal Core_. 2025-10-14.
[^plugin-base-autowire]: [PluginBase provides create() factory method with autowired parameters](https://www.drupal.org/node/3542837).
  _История изменений Drupal Core_. 2025-10-14.
[^poor-people-plugin-autowire]: Реализация, как и в случае с контроллерами, осуществляется через трейт
   `AutowiredInstanceTrait` и рефлексию в рантайме. По сути, это автосвязывание для бедных — иначе не назвать. При этом
   автосвязывание будет работать только с сервисами. Другие варианты автосвязывания, включая простой
   `#[Autowire(param: 'app.root')]`, уже не поддерживаются.

    **💡 Pro Tip:** вместо того чтобы использовать `AutowireTrait` для контроллеров и форм, просто регистрируйте их как
    сервис. Так вы получите больше возможностей, сократите объём кода и повысите скорость работы.
[^administer-node-published-status]: [New permission available to control the Published status of Nodes](https://www.drupal.org/node/3528500).
  _История изменений Drupal Core_. 2025-10-15.
[^views-ajax-window-history]: [Add AJAX command to Views module that sets the browser URL without refreshing the page.](https://www.drupal.org/node/3552223).
  _История изменений Drupal Core_. 2025-10-16.
[^template-preprocess-deprecated]: [template_preprocess_HOOK, and the file and includes keys in hook_theme definitions have been deprecated.](https://www.drupal.org/node/3549500).
  _История изменений Drupal Core_. 2025-10-16.
[^justinrainbow-json-schema]: [Promote justinrainbow/json-schema from dev-dependency to full dependency](https://www.drupal.org/project/drupal/issues/3365985).
  _Задача на Drupal.org_. Дата обращения: 2025-10-20.
[^core-extension-constraints]: [Add validation constraints to core.extension](https://www.drupal.org/project/drupal/issues/3432353).
  _Задача на Drupal.org_. Дата обращения: 2025-10-20.
[^standard-profile-contact]: [Remove Contact module from the Standard profile](https://www.drupal.org/project/drupal/issues/3535775).
  _Задача на Drupal.org_. Дата обращения: 2025-10-20.
[^contact-contrib]: [[meta] Tasks to deprecate the Contact module](https://www.drupal.org/project/drupal/issues/3520460).
  _Задача на Drupal.org_. Дата обращения: 2025-10-20.
[^top-par-item-weight]: [Be able to control the order of top bar items](https://www.drupal.org/project/drupal/issues/3538221).
  _Задача на Drupal.org_. Дата обращения: 2025-11-05.
[^no-size-limit-file-upload]: [Don't display size limit when there is no limit configured for file upload](https://www.drupal.org/project/drupal/issues/2557319).
  _Задача на Drupal.org_. Дата обращения: 2025-11-05.
[^demo-umami-profile-contact]: [Remove Contact module from the Umami profile](https://www.drupal.org/project/drupal/issues/3551375).
  _Задача на Drupal.org_. Дата обращения: 2025-10-20.
[^workspace-provider]: [New Workspace Provider system](https://www.drupal.org/node/3553089).
  _История изменений Drupal Core_. 2025-10-21. Дата обращения: 2025-11-05.
[^plugin-on-collection-dependency-removal]: [Plugins used in entities with plugin collections can react when the entities' dependencies are removed](https://www.drupal.org/node/3549101).
  _История изменений Drupal Core_. 2025-10-22. Дата обращения: 2025-11-05.
[^cache-memory]: [New cache.memory cache bin, replaces cache.static, MemoryCacheInterface alias deprecated](https://www.drupal.org/node/3546856).
  _История изменений Drupal Core_. 2025-10-22. Дата обращения: 2025-11-06.
[^workspace-purge-deleted-removed]: [WorkspaceManagerInterface::purgeDeletedWorkspacesBatch() has been deprecated](https://www.drupal.org/node/3553582).
  _История изменений Drupal Core_. 2025-10-23. Дата обращения: 2025-11-10.
[^workspace-switch-event]: [New WorkspaceSwitchEvent event added](https://www.drupal.org/node/3553871).
  _История изменений Drupal Core_. 2025-10-23. Дата обращения: 2025-11-10.
[^system-default-theme-features-deprecated]: [_system_default_theme_features is deprecated](https://www.drupal.org/node/3554127).
  _История изменений Drupal Core_. 2025-10-24. Дата обращения: 2025-11-10.
[^theme-get-setting-deprecated]: [theme_get_setting() is deprecated](https://www.drupal.org/node/3035289).
  _История изменений Drupal Core_. 2025-10-24. Дата обращения: 2025-11-10.
[^overview-terms-extends-entity-form]: [Drupal\taxonomy\Form\OverviewTerms now extends Drupal\Core\Entity\EntityForm](https://www.drupal.org/node/3528300).
  _История изменений Drupal Core_. 2025-10-24. Дата обращения: 2025-11-10.
[^kernel-hook-attributes]: [Kernel tests can use hook attributes to test hooks](https://www.drupal.org/node/3553794).
  _История изменений Drupal Core_. 2025-10-26. Дата обращения: 2025-11-10.
[^cache-optional-dynamic-cache-bin]: [Block plugins implementing CacheOptionalInterface will not have their own render cache entries](https://www.drupal.org/node/3554070).
  _История изменений Drupal Core_. 2025-10-26. Дата обращения: 2025-11-10.
[^oop-hooks-themes]: [Hooks in themes can now be OOP](https://www.drupal.org/node/3551652).
  _История изменений Drupal Core_. 2025-10-27. Дата обращения: 2025-11-10.
[^phpunit-10-removed]: [Removed support for PHPUnit 10](https://www.drupal.org/node/3546970).
  _История изменений Drupal Core_. 2025-10-28. Дата обращения: 2025-11-10.
[^config-single-export-form-htmx]: [ConfigSingleExportForm now has a dynamically updated URL](https://www.drupal.org/node/3546732).
  _История изменений Drupal Core_. 2025-10-28. Дата обращения: 2025-11-12.
[^module-handler-add-profile-module]: [ModuleHandler addProfile and addModule no longer do anything.](https://www.drupal.org/node/3550193).
  _История изменений Drupal Core_. 2025-10-29. Дата обращения: 2025-11-12.
[^file-managed-file-submit]: [file_managed_file_submit() is deprecated](https://www.drupal.org/node/3534091).
  _История изменений Drupal Core_. 2025-10-30. Дата обращения: 2025-11-12.
[^htmx-route]: [Route option added for routes designed to serve HTMX requests](https://www.drupal.org/node/3547745).
  _История изменений Drupal Core_. 2025-10-31. Дата обращения: 2025-11-12.
[^node-route-aliases]: [node.add and node.add_page routes have new aliases](https://www.drupal.org/node/3555319).
  _История изменений Drupal Core_. 2025-10-31. Дата обращения: 2025-11-13.
[^config-importer-factory]: [New ConfigImporterFactory service](https://www.drupal.org/node/3394638).
  _История изменений Drupal Core_. 2025-11-01. Дата обращения: 2025-11-13.
[^state-get-values-during-request]: [Method getValuesSetDuringRequest() added to Drupal\Core\State\StateInterface](https://www.drupal.org/node/3519307).
  _История изменений Drupal Core_. 2025-11-01. Дата обращения: 2025-11-13.
[^maintenance-mode-switch-log]: [Log changes to maintenance mode](https://www.drupal.org/project/drupal/issues/229778).
  _Задача на Drupal.org_. Дата обращения: 2025-11-13.
[^engine-extension-deprecated]: [The .engine extension has been deprecated. Use tagged services instead.](https://www.drupal.org/node/3547356).
  _История изменений Drupal Core_. 2025-11-04. Дата обращения: 2025-11-13.
[^load-revision-static-and-persistent-cache]: [Loading revisions now uses the static and persistent cache like](https://www.drupal.org/node/3553211).
  _История изменений Drupal Core_. 2025-11-12. Дата обращения: 2025-11-13.
[^php-8.4-recommendation]: [Recommend PHP 8.4 for Drupal 11.3 and 10.6](https://www.drupal.org/project/drupal/issues/3554384).
  _Задача на Drupal.org_. Дата обращения: 2025-11-17.
[^data-drupal-language]: [Invalid attributes are changed in language switcher block HTML](https://www.drupal.org/node/3556699).
  _История изменений Drupal Core_. 2025-11-08. Дата обращения: 2025-11-17.
[^lru-cache]: Least Recently Used — «наименее недавно использованный». Его особенность в том, что он сам удаляет данные,
  которые не использовались дольше всего, когда кеш достигает лимита по объёму. Это позволяет автоматически поддерживать
  оптимальный объём занимаемой памяти без ручного вмешательства.

    В **Drupal 11.2** была добавлена реализация `Drupal\Core\Cache\MemoryCache\LruMemoryCache`, которая используется
    для сервиса `entity.memory_cache`. По умолчанию лимит составляет 1 000 элементов. При необходимости его можно
    изменить в файле `services.yml` проекта через параметр `entity.memory_cache.slots`.
[^migrate-executable-memory-clear]: [Memory management removed from MigrateExecutable](https://www.drupal.org/node/3139212).
  _История изменений Drupal Core_. 2025-11-10. Дата обращения: 2025-11-17.
[^workspace-association-deprecated]: [The workspaces.association service has been replaced by workspaces.tracker](https://www.drupal.org/node/3551450).
  _История изменений Drupal Core_. 2025-11-10. Дата обращения: 2025-11-17.
[^batch-api-callable-resolver]: [Convert batch callbacks to CallableResolver](https://www.drupal.org/project/drupal/issues/3539919).
  _Задача на Drupal.org_. Дата обращения: 2025-11-17.
[^get-bundle-info-string-only]: [Do not call \Drupal\Core\Entity\EntityTypeBundleInfo::getBundleInfo() with a NULL value](https://www.drupal.org/node/3557136).
  _История изменений Drupal Core_. 2025-11-14. Дата обращения: 2025-11-18.
[^static-map-null-map]: [\Drupal\migrate\Plugin\migrate\process\StaticMap::transform() cannot map NULL values unless there is a default value or bypass is set](https://www.drupal.org/node/3557003).
  _История изменений Drupal Core_. 2025-11-14. Дата обращения: 2025-11-18.
[^default-content-created-ignore]: ["Created" fields are excluded from default content by default](https://www.drupal.org/node/3557689).
  _История изменений Drupal Core_. 2025-11-14. Дата обращения: 2025-11-18.
[^sort-by-key-recursive]: [Add a recursive sort-by-key function to SortArray](https://www.drupal.org/project/drupal/issues/3556987).
  _Задача на Drupal.org_. Дата обращения: 2025-11-18.
[^field-layout-deprecated]: [Field Layout module is deprecated](https://www.drupal.org/node/3557095).
  _История изменений Drupal Core_. 2025-11-14. Дата обращения: 2025-11-18.
[^migrate-drupal-ui-deprecated]: [Migrate Drupal UI is deprecated](https://www.drupal.org/node/3533901).
  _История изменений Drupal Core_. 2025-11-14. Дата обращения: 2025-11-18.
[^entity-form-handlers-required-interface]: [Classes used in entity form handlers must implement Drupal\Core\Entity\EntityFormInterface](https://www.drupal.org/node/3528495).
  _История изменений Drupal Core_. 2025-11-14. Дата обращения: 2025-11-18.
[^archive-manager-deprecated]: [ArchiverManager and other archive management code is deprecated](https://www.drupal.org/node/3556927).
  _История изменений Drupal Core_. 2025-11-17. Дата обращения: 2025-11-18.
[^twig-allowed]: [New TwigAllowed method attribute](https://www.drupal.org/node/3551699).
  _История изменений Drupal Core_. 2025-11-17. Дата обращения: 2025-11-20.
[^get-count-new-comments-deprecated]: [Deprecate CommentManagerInterface::getCountNewComments](https://www.drupal.org/project/drupal/issues/3543035).
  _Задача на Drupal.org_. Дата обращения: 2025-11-20.
[^composite-constraint-interface]: [\Drupal\Core\Validation\CompositeConstraintInterface added to bridge Symfony's Composite constraints to Drupal](https://www.drupal.org/node/3558184).
  _История изменений Drupal Core_. 2025-11-18. Дата обращения: 2025-11-20.
[^at-least-one-of-constraint-validator]: [AtLeastOneOfConstraintValidator has been replaces by the default Symfony implementation](https://www.drupal.org/node/3558133).
  _История изменений Drupal Core_. 2025-11-18. Дата обращения: 2025-11-20.
[^sequentially-constraint]: [New Sequentially constraint added to core](https://www.drupal.org/node/3521594).
  _История изменений Drupal Core_. 2025-11-18. Дата обращения: 2025-11-20.
[^ckeditor-5-entity-links]: [A new Entity Links Filter format and CKEditor 5 plugin has been added](https://www.drupal.org/node/3524296).
  _История изменений Drupal Core_. 2025-11-20. Дата обращения: 2025-11-20.
[^link-target-handler]: [Entity Type definitions can now optionally provide a "link_target" handler](https://www.drupal.org/node/3350853).
  _История изменений Drupal Core_. 2025-11-20. Дата обращения: 2025-11-20.
[^stream-wrappers-module-theme]: [module:// and theme:// stream wrappers added to core](https://www.drupal.org/node/2352923).
  _История изменений Drupal Core_. 2025-11-22. Дата обращения: 2025-11-24.
[^sdc-noui]: [New noUi property allowing page builders to exclude SDCs](https://www.drupal.org/node/3542594).
  _История изменений Drupal Core_. 2025-11-22. Дата обращения: 2025-11-24.
[^doctrine-annotations-fork]: [doctrine/annotations has been forked into core](https://www.drupal.org/node/3551049).
  _История изменений Drupal Core_. 2025-11-22. Дата обращения: 2025-11-24.
[^pre-entity-import-event]: [Dispatch an event for manipulating entity data during content import](https://www.drupal.org/project/drupal/issues/3522779).
  _Задача на Drupal.org_. Дата обращения: 2025-11-24.
[^functional-test-magic-container]: [Accessing \$this->container from functional tests is deprecated](https://www.drupal.org/node/3492500).
  _История изменений Drupal Core_. 2025-11-20. Дата обращения: 2025-12-02.
[^navigation-stable]: [Mark Navigation as a stable module](https://www.drupal.org/project/drupal/issues/3557578).
   _Задача на Drupal.org_. Дата обращения: 2025-12-02.
[^field-storage-definition-get-property-definition]: [Calls to \Drupal\Core\Field\FieldStorageDefinitionInterface::getPropertyDefinition() will trigger a deprecation if $name is not a string](https://www.drupal.org/node/3557373).
  _История изменений Drupal Core_. 2025-11-17. Дата обращения: 2025-12-02.
[^field-sync-widget]: [content_translation_field_sync_widget has been deprecated](https://www.drupal.org/node/3548573).
  _История изменений Drupal Core_. 2025-11-27. Дата обращения: 2025-12-02.
[^mailer-capture]: [Add a way to capture mails sent through the mailer transport service during tests](https://www.drupal.org/project/drupal/issues/3397420).
  _Задача на Drupal.org_. Дата обращения: 2025-12-02.
[^fiber-resume-type]: [FiberResumeType enum introduced to allow fiber suspensions to indicate the intent](https://www.drupal.org/node/3556785).
  _История изменений Drupal Core_. 2025-11-28. Дата обращения: 2025-12-02.
[^block-content-selection]: [Block content entity reference fields now use the BlockContentSelection plugin by default](https://www.drupal.org/node/3521459).
  _История изменений Drupal Core_. 2025-11-28. Дата обращения: 2025-12-02.
[^image-style-get-replacement-id]: [ImageStyle::getReplacementID is deprecated](https://www.drupal.org/node/3520914).
  _История изменений Drupal Core_. 2025-11-28. Дата обращения: 2025-12-02.
[^legacy-migrate-deprecations]: [Migrate process plugins for legacy upgrade are deprecated](https://www.drupal.org/node/3533560).
  _История изменений Drupal Core_. 2025-11-28. Дата обращения: 2025-12-02.
[^navigation-css-reset]: [CSS reset added to Navigation module's toolbar and top bar](https://www.drupal.org/node/3560492).
  _История изменений Drupal Core_. 2025-11-28. Дата обращения: 2025-12-02.
[^sdc-for-windows]: [Single Directory Components incompatible with Windows](https://www.drupal.org/project/drupal/issues/3479427).
  _Задача на Drupal.org_. Дата обращения: 2025-12-02.
[^memory-backend-gargage-collection]: [MemoryBackend::garbageCollection() now removes invalid items from memory](https://www.drupal.org/node/3559908).
  _История изменений Drupal Core_. 2025-11-27. Дата обращения: 2025-12-02.
[^form-api-callable-resolver]: [Form API callbacks now support callables supported by the CallableResolver](https://www.drupal.org/node/3548821).
  _История изменений Drupal Core_. 2025-12-03. Дата обращения: 2025-12-04.
[^hook-requirements-deprecated]: [hook_requirements deprecated in favor of separate runtime and update hooks and install-time requirements checks](https://www.drupal.org/node/3549685).
  _История изменений Drupal Core_. 2025-12-03. Дата обращения: 2025-12-04.
[^twig-inline-css]: [inline_css — Filters](https://twig.symfony.com/doc/3.x/filters/inline_css.html).
  _Документация Twig._
[^php-8-5]: [PHP 8.5](https://www.php.net/releases/8.5/ru.php).
  _Официальный сайт PHP._
[^php-8-4]: [PHP 8.4](https://www.php.net/releases/8.4/ru.php).
  _Официальный сайт PHP._