В Drupal, до сих пор, формирование письма происходит при помощи
хука `hook_mail()`. Если вам требуется формировать больше одного письма, то
функция растёт как на дрожжах, либо провоцирует писать спагетти. Зачастую,
проблема усложняется тем, что письма могут быть сложные, требовать данные или
сервисы для какой-то логики, что делает код ещё более сложным для поддержки.

Пока данный хук официально не заменили на что-то более современное, мы можем это
сделать сами! Благо, это не так сложно.

Нам не нужно ничего придумывать, за нас уже всё придумали разработчики Drupal
Commerce 2. В этом материале мы сделаем то, что они делают в Commerce для
отправки писем. Вы узнаете не только то, как они отправляют письма, но и сможете
применять их подход на своих проектах.

Преимущества их подхода перед оригинальным:

- Возможность задавать письмам стандартные значения параметров.
- Возможность отправлять в качестве содержимого
  письма [рендер массивы][drupal-8-render-arrays].
- Возможность использовать Dependency Injection и сервисы правильно.
- Каждое письмо имеет свой объект, благодаря чему становится проще формировать
  письмо с более сложной логикой, разбивая его на методы и используя DI.
  Разбиение на объекты, также позволяет лучше организовать код в проекте.
- Возможность отсылать письма в разных языках, независимо от текущего.

Далее мы будем писать и разбирать **аналог** того что в Drupal Commerce 2. Если
вы сравните их, они будут немного отличаться, но принцип и подход будет
абсолютно идентичен.

## MailHandler — формирование и отправка писем

Отправкой писем в Drupal занимаются `@Mail` плагины, а ими управляет
менеджер `plugin.manager.mail`. В принципе, этого сервиса достаточно для
отправки почты, но, вероятнее всего, вы не захотите отсылать письма со
стандартными параметрами. Для того чтобы не передавать одни и те же параметры
каждый раз и вводится данный посредник — `MailHandler`.

```php {"header":"src/Mail/MailHandler.php"}
<?php

namespace Drupal\example\Mail;

use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\TranslationManager;

/**
 * Handles the assembly and dispatch of HTML emails.
 */
final class MailHandler {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The language default.
   *
   * @var \Drupal\Core\Language\LanguageDefault
   */
  protected $languageDefault;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  private $stringTranslation;

  /**
   * Constructs a new MailHandler object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Language\LanguageDefault $language_default
   *   The language default.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, LanguageDefault $language_default, TranslationInterface $string_translation) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->languageDefault = $language_default;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Composes and send email message.
   *
   * @param string $to
   *   The email address or addresses where the message will be sent to.
   * @param TranslatableMarkup $subject
   *   The message subject. To be properly translated with body, it must be
   *   TranslatableMarkup when we switch language.
   * @param array $body
   *   A render array representing message body.
   * @param array $params
   *   Parameters to build the email.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   *
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
  public function sendMail(string $to, TranslatableMarkup $subject, array $body, array $params = []): bool {
    $default_params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'id' => 'mail',
      'reply-to' => NULL,
      'subject' => $subject,
      'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
      // The body will be rendered in example_mail().
      'body' => $body,
    ];
    if (!empty($params['cc'])) {
      $default_params['headers']['Cc'] = $params['cc'];
    }
    if (!empty($params['bcc'])) {
      $default_params['headers']['Bcc'] = $params['bcc'];
    }
    $params = array_replace($default_params, $params);

    // Change the active language to ensure the email is properly translated.
    if ($params['langcode'] != $default_params['langcode']) {
      $this->changeActiveLanguage($params['langcode']);
    }

    $message = $this->mailManager->mail('example', $params['id'], $to, $params['langcode'], $params, $params['reply-to']);

    // Revert back to the original active language.
    if ($params['langcode'] != $default_params['langcode']) {
      $this->changeActiveLanguage($default_params['langcode']);
    }

    return (bool) $message['result'];
  }

  /**
   * Changes the active language for translations.
   *
   * @param string $langcode
   *   The langcode.
   */
  protected function changeActiveLanguage($langcode): void {
    if (!$this->languageManager->isMultilingual()) {
      return;
    }
    $language = $this->languageManager->getLanguage($langcode);
    if (!$language) {
      return;
    }
    // The language manager has no method for overriding the default
    // language, like it does for config overrides. We have to change the
    // default language service's current language.
    // @see https://www.drupal.org/project/drupal/issues/3029010
    $this->languageDefault->set($language);
    $this->languageManager->setConfigOverrideLanguage($language);
    $this->languageManager->reset();

    // The default string_translation service, TranslationManager, has a
    // setDefaultLangcode method. However, this method is not present on
    // either of its interfaces. Therefore we check for the concrete class
    // here so that any swapped service does not break the application.
    // @see https://www.drupal.org/project/drupal/issues/3029003
    if ($this->stringTranslation instanceof TranslationManager) {
      $this->stringTranslation->setDefaultLangcode($language->getId());
      $this->stringTranslation->reset();
    }
  }

}
```

### Конструктор - Dependency Injection

```php
  /**
   * Constructs a new MailHandler object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Language\LanguageDefault $language_default
   *   The language default.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, LanguageDefault $language_default, TranslationInterface $string_translation) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->languageDefault = $language_default;
    $this->stringTranslation = $string_translation;
  }
```

В хендлер производится Dependency Injection четырёх сервисов:

- `plugin.manager.mail`: Менеджер плагинов `@Mail` — которые отвечают за
  отправку писем. Через менеджер данных плагинов мы будем делать запрос на
  отправку письма, а дальше он будет заниматься своим делом — выбирать плагин и
  производить отправку.
- `language_manager`: Менеджер языков системы. Позволит нам получать всю
  необходимую информацию о языках.
- `language.default`: Хранилище информации о текущем языке.
- `string_translation`: Менеджер переводов. Отвечает за то, как будут
  переводиться строки и их непосредственный перевод.

### ::changeActiveLanguage() — смена языка для отправки письма

```php
  /**
   * Changes the active language for translations.
   *
   * @param string $langcode
   *   The langcode.
   */
  protected function changeActiveLanguage($langcode): void {
    if (!$this->languageManager->isMultilingual()) {
      return;
    }
    $language = $this->languageManager->getLanguage($langcode);
    if (!$language) {
      return;
    }
    // The language manager has no method for overriding the default
    // language, like it does for config overrides. We have to change the
    // default language service's current language.
    // @see https://www.drupal.org/project/drupal/issues/3029010
    $this->languageDefault->set($language);
    $this->languageManager->setConfigOverrideLanguage($language);
    $this->languageManager->reset();

    // The default string_translation service, TranslationManager, has a
    // setDefaultLangcode method. However, this method is not present on
    // either of its interfaces. Therefore we check for the concrete class
    // here so that any swapped service does not break the application.
    // @see https://www.drupal.org/project/drupal/issues/3029003
    if ($this->stringTranslation instanceof TranslationManager) {
      $this->stringTranslation->setDefaultLangcode($language->getId());
      $this->stringTranslation->reset();
    }
  }
```

Метод `::changeActiveLanguage()` отвечает за смену текущего языка в момент
выполнения запроса.

Первым делом он проверяет, является ли сайт мультиязычным (имеет два и более
языка). Если нет, сразу завершает свою работу.

Если сайт мультиязычный, он получает информацию о языке, на который требуется
переключить систему. Если информации о языке нет, процесс прерывается.

Далее, если оба условия удовлетворены, происходит процесс переключения языка на
тот, что передан в параметре. Для этого информация о языке задаётся
в `language.default` и сбрасывает текущий внутренний кэш, затем, эта же операция
проводится для `string_translation`, чтобы он понимал, нужно ли переводить и на
какой язык.

Этот метод копипаст из коммерца. Фактически, это «костыль» для переключения
текущего языка системы, так как ядро не предоставляет такого API, который бы
комплексно это выполнял. В комментариях приведены ссылки на ишьюсы, если вы в
этом заинтересованны, можно «дать пинка» и добавить такой API в ядро.

Это позволяет отправлять письмо пользователю на одном языке, вызывая его на
другом: Менеджер с английским интерфейсом вызовет отправку письма, для
пользователя с выбранным русским языком.

### ::sendMail() — отправка письма

```php
  /**
   * Composes and send email message.
   *
   * @param string $to
   *   The email address or addresses where the message will be sent to.
   * @param TranslatableMarkup $subject
   *   The message subject. To be properly translated with body, it must be
   *   TranslatableMarkup when we switch language.
   * @param array $body
   *   A render array representing message body.
   * @param array $params
   *   Parameters to build the email.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   *
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
  public function sendMail(string $to, TranslatableMarkup $subject, array $body, array $params = []): bool {
    $default_params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'id' => 'mail',
      'reply-to' => NULL,
      'subject' => $subject,
      'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
      // The body will be rendered in example_mail().
      'body' => $body,
    ];
    if (!empty($params['cc'])) {
      $default_params['headers']['Cc'] = $params['cc'];
    }
    if (!empty($params['bcc'])) {
      $default_params['headers']['Bcc'] = $params['bcc'];
    }
    $params = array_replace($default_params, $params);

    // Change the active language to ensure the email is properly translated.
    if ($params['langcode'] != $default_params['langcode']) {
      $this->changeActiveLanguage($params['langcode']);
    }

    $message = $this->mailManager->mail('example', $params['id'], $to, $params['langcode'], $params, $params['reply-to']);

    // Revert back to the original active language.
    if ($params['langcode'] != $default_params['langcode']) {
      $this->changeActiveLanguage($default_params['langcode']);
    }

    return (bool) $message['result'];
  }
```

Данный метод подготавливает письмо для отправки, задаёт значения по умолчанию и
отсылает их при помощи `plugin.manager.mail`.

В качестве аргументов принимает:

- `string $to`: Адрес электронной почты, куда необходимо отправить письмо.
- `TranslatableMarkup $subject`: Тема письма. Этот аргумент отличается от
  реализации в коммерце. Я намеренно сделал тип `TranslatableMarkup`, для того
  чтобы заголовок не приходил сразу в строке как у Drupal Commerce. Если здесь
  передавать строку, то он не будет переводиться!
- `array $body`: [Рендер массив][drupal-8-render-arrays] с телом письма. Мы будем
  отсылать письма именно через рендер массивы. Это открывает множество
  возможностей, например, формирование письма через
  [hook_theme()][drupal-8-hook-theme].
- `array $params`: Массив параметров для письма, как и в обычной отправке. В
  нашем случае, он также будет использован для возможности изменить параметры по
  умолчанию.

В качестве результата возвращает булевое значение о статусе отправки письма.

Внутри метода первым делом задаются параметры по умолчанию:

- `headers`: По умолчанию все письма будут отправляться как HTML. Для того чтобы
  это корректно работало, вам потребуется какой-то модуль, который умеет
  отправлять HTML, например, swiftmailer (он будет как зависимость примера).
  Если вы не хотите отправлять HTML письма, удалите `Content-Type`.
- `id`: Ключ письма. Это значение придёт в параметр `$key` `hook_mail()` хука.
  Мы задаём занчение по умолчанию `mail`. Вы можете переопределять данное
  значение на то чтобы альтерить письма или для иных целей.
- `reply-to`: Электронная почта, которая будет указана для ответа в письме. По
  умолчанию задаём `NULL`, так как этот параметр мы будем передавать в качестве
  аргумента при отправке. Если вы не переопределите данное значение при
  отправке, Drupal подставит email адрес сайта по умолчанию.
- `subject`: Заголовок письма, берём из аргумента.
- `langcode`: Текущий код языка системы. Необходим для отправки письма, но при
  этом никак не используется из коробки, поэтому мы имеем свой метод для
  переключения языка.
- `body`: Тело письма — рендер массив.

Все эти параметры будут доступны в `hook_mail()`. Если вам нужна какая-то
дополнительная информация при альтере писем, вы можете смело добавить её в
данный массив.

Далее проверяется, передан ли в дополнительных параметрах `cc` значение. Если
передано, устанавливается корректный заголовок в письмо. `Cc` — адрес
электронной почты куда будет выслана копия письма.

Аналогично делается с `bcc`. `Bcc` — адрес электронной почты куда будет выслана
копия письма, но адресат (`$to`) не сможет его увидеть.

Далее, значения `$default_params` объединяются с дополнительными из `$params` и
сохраняются в `$params`. <mark>`$default_params` остаётся неизменным.</mark>

Затем сверяется язык из `$params` и `$default_params`. Если они отличаются
(в `$params` при отправке передали язык отличный от текущего активного), то язык
системы переключается на язык из `$params` при помощи
метода `::changeActiveLanguage()`.

После того как язык системы переключён, производится отправка письма при
помощи `plugin.manager.mail`.

Как только процесс отправки завершится, язык системы переключается обратно,
который был до отправки письма, для того чтобы все последующие операции не
оказались в некорректном языке.

В конце возвращается статус отправки письма полученный от `plugin.manager.mail`.

### Сервис example.mail_handler

```yaml {"header":"example.services.yml"}
services:
  example.mail_handler:
    class: Drupal\example\Mail\MailHandler
    arguments: ['@plugin.manager.mail', '@language_manager', '@language.default', '@string_translation']
```

Нам лишь остаётся объявить наш класс как сервис со всеми необходимыми сервисами
в качестве аргументов.

### hook_mail()

Мы объявили свой сервис `example.mail_handler` для отправки писем в HTML через
рендер массивы и с поддержкой переключения языка. Почти всё готово, нам осталось
реализовать `hook_mail()`.

Хук нам по-прежнему нужен, он должен сформировать сообщение (`$message`) для
отправки.

```php {"header":"example.module"}
/**
 * Implements hook_mail().
 *
 * @see \Drupal\example\Mail\MailHandler
 */
function example_mail(string $key, array &$message, array $params): void {
  /** @var \Drupal\Core\Render\RendererInterface $renderer */
  $renderer = \Drupal::service('renderer');

  if (isset($params['headers'])) {
    $message['headers'] = array_merge($message['headers'], $params['headers']);
  }
  if (!empty($params['from'])) {
    $message['from'] = $params['from'];
  }
  $message['subject'] = $params['subject'];
  $message['body'][] = $renderer->renderPlain($params['body']);
}
``` 

Так как вся основная логика вынесена в наш сервис, мы лишь формируем
финальный `$message`:

- Передаем получившиеся заголовки.
- Устанавливаем, от кого письмо (если задано).
- Указываем заголовок письма.
- Рендерим рендер массив в HTML строку и задаём в качестве тела письма.

Больше нам ничего не нужно. Это полноценно рабочий сервис для отправки писем.

### Как пользоваться

При помощи данного сервиса, теперь можно отправлять HTML письма очень просто:

```php
$subject = new TranslatableMarkup('My first mail!');
$body = [
  '#markup' => '<strong>Hello World!</strong>',
];
$mail_handler->sendMail('example@example.com', $subject, $body);
```

### Формирование писем через объекты

Так как мы упростили `hook_mail()` и ожидаем что письма будут проходить через
наш сервис, а он, в свою очередь, ожидает рендер массивы, нам нужно как-то
формировать эти письма.

В Drupal Commerce письмо — сервис. А это значит, это объект и он имеет поддержку
Dependency Injection. И это крайне удобно!

Это имеет серьезные преимущества:

- Можно делать абстрактные объекты для писем или базовые, расширять их,
  совершенствовать. В общем, применять ООП по полной, чтобы кода стало в итоге
  меньше!
- Можно легко находить ответственные объекты за письма.
- Разные письма не мешаются в одном файле. И вообще, файл для письма проще
  найти, чем функцию в простыне кода.
- Dependency Injection позволяет правильно и удобно подключать все требуемые
  зависимости и применять их для формирования письма.
- Сложные письма можно формировать с разбивкой на методы — письма будет легче
  править в будущем. Минус спагетти.
- Проще реализовывать похожие письма с минимальными отличиями.
- Удалять такие письма, опять же, проще — так как всё в одном файле, а все
  зависимости явно прослеживаются через DI.
- Легко находить где высылается конкретное письмо, опять же, из-за DI.

И это только то, с чем я столкнулся на реальной практике, перейдя на такой
вариант отправки писем из кастомных модулей.

### Простой пример

```php
<?php

namespace Drupal\example\Mail;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Simple email message class.
 */
final class SimpleMail {

  /**
   * The mail handler.
   *
   * @var \Drupal\example\Mail\MailHandler
   */
  protected $mailHandler;

  /**
   * Constructs a new UserLoginEmail object.
   *
   * @param \Drupal\example\Mail\MailHandler $mail_handler
   *   The mail handler.
   */
  public function __construct(MailHandler $mail_handler) {
    $this->mailHandler = $mail_handler;
  }

  /**
   * Sends email.
   *
   * @return bool 
   *   The message status.
   */
  public function send(): bool {
    $subject = new TranslatableMarkup('My first mail!');
    $body = [
      '#markup' => '<strong>Hello World!</strong>',
    ];

    return $this->mailHandler->sendMail('example@example.com', $subject, $body);
  }

}
```

Это минимум, что необходимо для формирования письма в качестве сервиса. Ему
потребуется наш сервис `example.mail_handler` и какой-то метод для отправки
письма.

Вы можете создавать интерфейсы и развивать идею дальше, для того чтобы
стандартизовать конкретные письма.

## Пример письма и его отправки

Сейчас рассмотрим чуть более конкретный пример. Всё будет крайне просто. При
авторизации на сайте, пользователю будет отправлять уведомление на почту, что
под его аккаунтом вошли на таком-то сайте и с таким юзер агентом. Мы также будем
учитывать язык пользователя для отправки письма.

### UserLoginMail — объект (сервис) письма

```php {"header":"src/Mail/UserLoginMail.php"}
<?php

namespace Drupal\example\Mail;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Notifies user about successful authentication.
 */
final class UserLoginMail {

  /**
   * The mail handler.
   *
   * @var \Drupal\example\Mail\MailHandler
   */
  protected $mailHandler;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new UserLoginEmail object.
   *
   * @param \Drupal\example\Mail\MailHandler $mail_handler
   *   The mail handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(MailHandler $mail_handler, RequestStack $request_stack, ConfigFactoryInterface $config_factory) {
    $this->mailHandler = $mail_handler;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * Sends email to user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account which authenticated.
   *
   * @return bool
   *   The message status.
   */
  public function send(UserInterface $account): bool {
    $to = $account->getEmail();
    $user_agent = $this->requestStack->getCurrentRequest()->headers->get('User-Agent');
    $subject = new TranslatableMarkup('Logged in to your @site account from @user_agent', [
      '@site' => $this->configFactory->get('system.site')->get('name'),
      '@user_agent' => $user_agent,
    ]);

    $body = [
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => new TranslatableMarkup('We detected that your @account_name account has been logged in. Was it you?', [
          '@account_name' => $account->getAccountName(),
        ]),
      ],
      'device' => [
        '#markup' => new TranslatableMarkup('Device: @user_agent', [
          '@user_agent' => $user_agent,
        ]),
      ],
    ];

    $params = [
      'id' => 'user_login',
      'langcode' => $account->getPreferredLangcode(),
    ];

    return $this->mailHandler->sendMail($to, $subject, $body, $params);
  }

}
```

#### Конструктор

```php
  /**
   * Constructs a new UserLoginEmail object.
   *
   * @param \Drupal\example\Mail\MailHandler $mail_handler
   *   The mail handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(MailHandler $mail_handler, RequestStack $request_stack, ConfigFactoryInterface $config_factory) {
    $this->mailHandler = $mail_handler;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }
```

В конструкторе мы принимаем три сервиса:

- `example.mail_handler`: Наш сервис для отправки писем. Он обязательно нужен.
- `request_stack`: Стэк текущих запросов. Из него мы получим текущий запрос
  и `User-Agent` клиента.
- `config.factory`: Конфиг фактори нам потребуется для получения названия сайта.

#### ::send()

```php
  /**
   * Sends email to user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account which authenticated.
   *
   * @return bool
   *   The message status.
   */
  public function send(UserInterface $account): bool {
    $to = $account->getEmail();
    $user_agent = $this->requestStack->getCurrentRequest()->headers->get('User-Agent');
    $subject = new TranslatableMarkup('Logged in to your @site account from @user_agent', [
      '@site' => $this->configFactory->get('system.site')->get('name'),
      '@user_agent' => $user_agent,
    ]);

    $body = [
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => new TranslatableMarkup('We detected that your @account_name account has been logged in. Was it you?', [
          '@account_name' => $account->getAccountName(),
        ]),
      ],
      'device' => [
        '#markup' => new TranslatableMarkup('Device: @user_agent', [
          '@user_agent' => $user_agent,
        ]),
      ],
    ];

    $params = [
      'id' => 'user_login',
      'langcode' => $account->getPreferredLangcode(),
    ];

    return $this->mailHandler->sendMail($to, $subject, $body, $params);
  }
```

Единственный метод у письма `::send()` — формирует письмо для отправки.

В качестве параметра мы будем требовать `UserInterface`. Так, тот кто хочет
отправить данное письмо, должен передать нам объект пользователя, для которого
данное письмо предназначается. Это одно из преимуществ формирования письма через
объекты. Вы можете требовать конкретные данные, и это позволит сделать работу с
письмами более конкретной.

Так как мы требуем юзера в качестве аргумента, то и почту куда отправлять
письмо, мы получаем напрямую из сущности.

Далее мы формируем заголовок и примитивное тело письма. А в параметрах
задаём `id` письма `user_login`, чтобы при необходимости его можно было
идентифицировать в `hook_mail_alter()`, а также `langcode`, чтобы письмо ушло на
том языке, который выбран у пользователя в настройках, не важно с какой языковой
версии он авторизовывается.

После чего мы отправляем письмо через наш сервис. Всё!

Нам осталось объявить наш объект письма как сервис, и вызвать его.

### Объявляем сервис для письма

```yaml {"header":"example.services.yml"}
  example.user_login_mail:
    class: Drupal\example\Mail\UserLoginMail
    arguments: ['@example.mail_handler', '@request_stack', '@config.factory']
```

Таким образом, для отправки нашего письма, надо лишь обратиться запросить
сервис `example.user_login_mail`.

### Отправляем письмо

Так как событий на авторизацию не завезли, воспользуемся
хуком `hook_user_login()`.

```php {"header":"example.module"}
/**
 * Implements hook_user_login().
 */
function example_user_login(UserInterface $account) {
  /** @var \Drupal\example\Mail\UserLoginEmail $login_mail */
  $login_mail = \Drupal::service('example.user_login_mail');
  $login_mail->send($account);
}
```

В нём мы просто получаем наш сервис, и вызываем его метод `::send()`, передавая
сущность авторизованного пользователя, которая приходит в качестве аргумента в
хук!

После чего, можно пробовать авторизоваться.

:: video [oop-mail.mp4] (video/oop-mail.mp4)

## Ссылки

- [Исходный код модуля с примером](example)

[drupal-8-hook-theme]: ../../../../2017/06/26/drupal-8-hook-theme/article.ru.md
[drupal-8-render-arrays]: ../../../../2020/02/05/drupal-8-render-arrays/article.ru.md
