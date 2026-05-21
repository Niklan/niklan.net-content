В Drupal есть стили изображений: вы создаёте стиль, настраиваете последовательность обработки (например, масштабировать до 100×100, сохранив пропорции, заполнить пустоты холстом и конвертировать в WebP), а затем применяете в нужных местах сайта — так из одного оригинала получаются подогнанные картинки. Функционал работает отлично, но недостаток — декларативный подход. Когда стилей не десятки, а сотни, управление превращается в хаос: сложно понять, что реально используется, а что — мусор. Если поправить стиль, непонятно, где его изменение аукнется без изучения использования в кодовой базе.

Детерминированность — плюс, но на практике она порождает проблему: стили оторваны от контекста. Один из подходов к решению — создание дублирующих стилей с одинаковыми настройками, но разными именами: например, `100x100_product` и `100x100_category`. Результат обработки идентичен, но Drupal создаёт два одинаковых файла. Сам факт необходимости создавать дублирующие стили наводит тоску, выжирает ценное время и плодит проблемы на будущее. А иногда вынужденно приходится дублировать стиль из-за технических ограничений — скажем, стиль с WebP нельзя применить в PDF, и появляется `100x100_pdf`. В общем, ситуаций, когда стили начинают плодиться по пустякам и быстро множатся, можно придумать много.

Отдельная боль — необходимость создавать стили под каждый уникальный размер. Когда у вас есть стили для 100×100 и 110×110, а нужно 105×105, появляется нежелание плодить промежуточный стиль ради 5 пикселей. Но эти 5 пикселей могут быть критичны для дизайна. А если тот же размер нужен то с обрезанием, то с холстом, то с вотермарками — количество стилей множится ещё быстрее.

Добавим сюда SDC-компоненты, одна из «фич» которых — возможность верстать с минимальным знанием Drupal. Фронтендеру можно объяснить, как пользоваться фильтром `image_style`, но что делать, если нужного стиля на сайте нет? Ждать разработчиков? А если ещё учесть адаптивные картинки для разных точек остановки и DPI — то, что казалось благом, окончательно выходит из‑под контроля.

Если вы постоянно идёте в админку сверяться с размерами и эффектами[^effects] и не уверены — переиспользовать стиль или создать новый — этот материал для вас. Он убирает головную боль: не нужно гадать и плодить «похожие» стили.

Зачем уходить из кода в админку? Контекст использования уже у вас перед глазами. В админке контекста нет — здесь он есть. Хочется прямо в Twig или PHP написать: «мне нужна картинка 100×100». Технически мы можем сгенерировать картинку при необходимости в процессе подготовки ответа, но генерация стилей изображений в большинстве случаев происходит только при HTTP-запросе — чтобы не блокировать основной поток и сохранить отзывчивость. Поэтому самый практичный канал — URL. Например, `/image.jpg?width=100&height=100`. Проблема: это открытая дверь для атаки, через которую можно генерировать картинки произвольных размеров, пока не кончится дисковое пространство. Значит, нужен защищённый механизм, который не позволит перебрать все возможные варианты.

В этом материале создадим механизм: API, маршруты и Twig-фильтры с семантическим подходом. Например, в Twig: `{{ photo|image_scale_crop(100, 100)|image_convert('avif') }}`. Вы ещё не дошли до технической части, но уже понимаете, что делает это выражение — и для этого не нужно знать Drupal. А главное — в месте вызова сразу виден и контекст (для чего картинка, где будет выведена), и конечный результат (размер, формат). Никаких расследований: что за стиль, что делает, где используется, — всё перед глазами. Всё это мы соберём в модуль `example`.

## Генерация производных изображений

Сердце всего функционала — оркестратор динамических стилей изображений. В его задачи входит:

- построение виртуального стиля с нужными эффектами;
- формирование URL/URI;
- принудительная генерация по запросу;
- сжатие и декомпрессия эффектов;
- генерация защитного токена.

::::: figure
::: figcaption
**Листинг 1** — Генератор производных изображений — `src/DynamicImageStyle/DynamicImageStyle.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\DynamicImageStyle;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\image\ImageStyleInterface;

final readonly class DynamicImageStyle {

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private StreamWrapperManagerInterface $streamWrapperManager,
    private PrivateKey $privateKey,
  ) {}

  public function effect(string $id, array $data = []): DynamicImageStyleBuilder {
    return new DynamicImageStyleBuilder($this)->effect($id, $data);
  }

  /**
   * @param list<array{0: string, 1: array<string, mixed>}> $effects
   */
  public function buildUrl(string $uri, array $effects): string {
    [$scheme, $target, $compressed, $hash] = $this->resolveDerivativePath($uri, $effects);
    $encoded_effects = \urlencode($compressed);
    $base_path = $this->getBaseUrlPath($scheme);
    $itok = $this->generateToken($compressed, $uri);

    return "/$base_path/styles/dynamic/$hash/$scheme/$target?effects=$encoded_effects&itok=$itok";
  }

  /**
   * @return array{string, string}
   */
  private function parseUri(string $uri): array {
    $scheme = StreamWrapperManager::getScheme($uri);
    $target = StreamWrapperManager::getTarget($uri);
    \assert(\is_string($scheme));
    \assert(\is_string($target));
    return [$scheme, $target];
  }

  /**
   * @param list<array{0: string, 1: array<string, mixed>}> $effects
   */
  public function createImageStyle(array $effects): ImageStyleInterface {
    $storage = $this->entityTypeManager->getStorage('image_style');
    $image_style = $storage->create(['name' => 'dynamic']);
    foreach ($effects as [$id, $data]) {
      $image_style->addImageEffect(['id' => $id, 'data' => $data]);
    }
    return $image_style;
  }

  /**
   * @param list<array{0: string, 1: array<string, mixed>}> $effects
   */
  public function compressEffects(array $effects): string {
    $json = \json_encode($effects, flags: \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
    return UrlHelper::compressQueryParameter($json);
  }

  private function hashEffects(string $compressed): string {
    return \substr(Crypt::hashBase64($compressed), 0, 8);
  }

  public function generateToken(string $compressed, string $uri): string {
    return \substr(Crypt::hmacBase64($compressed . ':' . $uri, $this->privateKey->get()), 0, 8);
  }

  private function getBaseUrlPath(string $scheme): string {
    if ($scheme === 'private') {
      return 'system/files';
    }
    $wrapper = $this->streamWrapperManager->getViaScheme($scheme);
    \assert($wrapper instanceof LocalStream);
    return $wrapper->getDirectoryPath();
  }

  /**
   * @param list<array{0: string, 1: array<string, mixed>}> $effects
   */
  public function createDerivative(string $uri, array $effects): bool {
    $derivative_uri = $this->buildUri($uri, $effects);
    $image_style = $this->createImageStyle($effects);
    return $image_style->createDerivative($uri, $derivative_uri);
  }

  /**
   * @param list<array{0: string, 1: array<string, mixed>}> $effects
   */
  public function buildUri(string $uri, array $effects): string {
    [$scheme, $target, , $hash] = $this->resolveDerivativePath($uri, $effects);
    return "$scheme://styles/dynamic/$hash/$scheme/$target";
  }

  /**
   * @param list<array{0: string, 1: array<string, mixed>}> $effects
   * @return array{string, string, string, string}
   */
  private function resolveDerivativePath(string $uri, array $effects): array {
    [$scheme, $target] = $this->parseUri($uri);

    $image_style = $this->createImageStyle($effects);
    $original_extension = \pathinfo($target, \PATHINFO_EXTENSION);
    $derivative_extension = $image_style->getDerivativeExtension($original_extension);
    if ($original_extension !== $derivative_extension) {
      $target .= '.' . $derivative_extension;
    }

    $compressed = $this->compressEffects($effects);
    $hash = $this->hashEffects($compressed);

    return [$scheme, $target, $compressed, $hash];
  }

  /**
   * @return list<array{0: string, 1: array<string, mixed>}>
   */
  public function decompressEffects(string $compressed): array {
    $json = UrlHelper::uncompressQueryParameter($compressed);
    /** @var list<array{0: string, 1: array<string, mixed>}> $effects */
    $effects = \json_decode($json, associative: TRUE, flags: \JSON_THROW_ON_ERROR);
    return $effects;
  }

}
```
:::::

Разберёмся, что делает данный класс:

1. **Инициализирует строитель эффектов.** Метод `::effect()` создаёт новый экземпляр `DynamicImageStyleBuilder`, позволяющий через fluent-интерфейс собрать набор эффектов. К этому строителю вернёмся позже.

2. **Создаёт виртуальный стиль изображения.** `::createImageStyle()` создаёт сущность `ImageStyle` без сохранения — исключительно для применения эффектов в рантайме. Каждый дополнительный эффект добавляется через `::addImageEffect()`.

3. **Сжимает и распаковывает эффекты.** `::compressEffects()` сериализует массив эффектов в JSON и сжимает через `UrlHelper::compressQueryParameter()` — чтобы URL оставался компактным. `::decompressEffects()` выполняет обратную операцию при обработке входящего запроса.

4. **Генерирует защитный токен.** `::generateToken()` вычисляет HMAC на основе сжатых эффектов, URI оригинала и приватного ключа сайта (`PrivateKey`). Токен обрезается до 8 символов — этого достаточно для защиты от перебора, но не раздувает URL. Метод публичный, потому что контроллер доставки изображений будет использовать его для валидации входящего `itok` query-параметра.

   Почему не использовать `ImageStyle::getPathToken()`? Этот метод включает имя стиля в ключ HMAC. У нас стиль всегда называется `'dynamic'`, поэтому токен не зависел бы от конкретного набора эффектов — разные наборы эффектов для одного файла давали бы одинаковый `itok`, что разрушает защиту.

5. **Вычисляет общие параметры производного изображения.** Приватный `::resolveDerivativePath()` содержит логику, общую для `::buildUrl()` и `::buildUri()`: разбор URI, создание виртуального стиля для определения расширения (если формат меняется — к имени добавляется новое расширение: `image.jpg.webp`), сжатие эффектов и вычисление хеша через `::hashEffects()`.

6. **Строит URL производного изображения.** `::buildUrl()` принимает URI оригинала и массив эффектов. Общую работу — разбор URI, определение расширения, сжатие и вычисление хеша — делегирует `::resolveDerivativePath()`. Сам метод дополнительно вычисляет `itok` через `::generateToken()` и формирует HTTP-путь вида `/{base_path}/styles/dynamic/{hash}/{scheme}/{target}?effects=…&itok=…`. Это URL для внешнего потребителя — именно он попадает в браузер.

7. **Строит URI производного файла.** `::buildUri()` использует тот же `::resolveDerivativePath()`, но возвращает внутренний URI (`public://styles/dynamic/…`) вместо HTTP-пути. Используется при генерации файла на диск.

8. **Генерирует производный файл.** `::createDerivative()` объединяет всё вместе: строит URI назначения через `::buildUri()`, создаёт виртуальный стиль и вызывает штатный `::createDerivative()` стиля изображения для фактической обработки.

Чтобы не реализовывать загрузку, конфигурирование и применение эффектов вручную, мы «эмулируем» стиль `dynamic` через создание сущности без сохранения — так получаем готовую поддержку любых `#[ImageEffect]` плагинов. Дальше разберём два практических момента, на которых стоит остановиться отдельно. Сначала — **формат кортежа для эффектов**.

Мы намеренно требуем формат кортежа на входе и конвертируем в ожидаемый друпалом. Во-первых, писать `['image_scale', ['width' => 300]]` проще, чем `['id' => 'image_scale', 'data' => ['width' => 300]]` — меньше бойлерплейта. Во-вторых, данные эффектов сжимаются в `effects`. Чем короче массив, тем короче сжатая строка и URL.

Теперь — **путь сохранения**. URL повторяет логику стандартных стилей: `/{base_path}/styles/dynamic/{hash}/{scheme}/{target}?effects=…&itok=…`.

`{base_path}`: для `public://` — реальный путь на диске, обычно `sites/default/files`[^public-path]; для `private://` — захардкоженный префикс `system/files`[^system-files-prefix]. **Разница в способе отдачи**: приватные файлы **обязаны** проходить проверку доступа в Drupal, поэтому nginx не должен знать их физическое расположение. `{base_path}` для приватной схемы — это URL-маркер, который [обработчики путей][inbound-outbound-processor] направляют к нужному контроллеру (например, `system.private_file_download`)[^system-files-prefix]. Публичные файлы nginx при наличии на диске отдаёт напрямую через `try_files $uri @drupal` — без бутстрапа Drupal, поэтому путь в URL должен совпадать с диском.

Следующий сегмент пути — `{hash}`: это хеш от сжатых эффектов (без URI), обрезанный до 8 символов. Все производные с одним набором эффектов попадают в одну поддиректорию, независимо от исходника. Зачем встраивать хеш в путь, если данные эффектов уже передаются в query-параметре `effects`? Потому что nginx при проверке `try_files $uri @drupal` **не учитывает query-строку**. Без уникального сегмента пути разные наборы эффектов для одного исходника давали бы одинаковый `$uri` и, как следствие, претендовали бы на один и тот же файл на диске — хранить их раздельно не получилось бы. В итоге nginx каждый раз проваливался бы в Drupal, даже когда файл уже сгенерирован. Чтобы он мог отдавать готовые производные напрямую, путь должен различаться для каждого набора эффектов.

Почему нельзя просто вставить сами сжатые `effects` в путь? Сжатая строка может превысить лимит длины имени директории в 255 байт, а обрезать её нельзя — декодирование сломается. Хеш фиксированной длины (8 символов) решает обе проблемы: путь остаётся коротким и уникальным, а полные данные эффектов Drupal получает из query-параметра при генерации.

Защитный `itok` — HMAC от пары `сжатые_эффекты + URI_оригинала` через `::generateToken()`. Почему `itok` — отдельный query-параметр, а не в пути? Технически можно встроить HMAC как сегмент пути вместо `{hash}`. Но тогда каждая уникальная пара «эффекты+изображение» создаёт отдельную поддиректорию — число директорий растёт как произведение изображений на наборы эффектов, что может исчерпать inodes[^inode-overhead] или лимит поддиректорий[^subdirectory-limits]. Текущий подход — `{hash}` только от эффектов — группирует все производные одного «стиля» в одну директорию и безопаснее по умолчанию. Перенос `itok` в путь не стоит того: выигрыш — всего 14 символов в URL. Если всё же нужно, убедитесь, что ваши окружения не подвержены этим ограничениям.

::: warning [`itok` как защита от path traversal]
HMAC неявно закрывает и path traversal. Атакующий может вручную подставить `../../private/secret.jpg` вместо реального пути, надеясь выйти за пределы публичной директории. Но для любого URI нужен валидный `itok` — HMAC от пары `сжатые_эффекты + URI` на приватном ключе сайта. Без знания `PrivateKey` вычислить токен для произвольного URI невозможно: запрос завершится 404 на проверке `hash_equals()` в `::validateRequest()`, не дойдя до файловой системы.

Для сравнения: `ImageStyleDownloadController` ядра явно проверяет наличие `..` в компонентах пути — это фикс [SA-CORE-2023-005](https://www.drupal.org/sa-core-2023-005). Там эта проверка критична, потому что настройка `image.settings:allow_insecure_derivatives` позволяет полностью отключить валидацию токена — и тогда явная проверка `..` становится единственным барьером. В нашем модуле такой настройки нет: `itok` обязателен всегда, обходного пути не существует. Если вы решите сделать токен опциональным или убрать его вовсе — добавьте явную проверку `..` в `::extractUri()`, иначе path traversal станет возможным.
:::

Два одинаковых динамических стиля для одного исходника дают идентичные `{hash}` и `itok` — файл создаётся один раз. Это даже **эффективнее стандартных стилей**: для настроек `100_100_product` и `100_100_category` Drupal создал бы два идентичных файла просто потому, что у них разные `{style_id}`.

::: note [`temporary://` — только программная генерация, без HTTP-URL]
`::createDerivative()` универсален: ядровый `ImageStyle::createDerivative()` не зависит от маршрутов и пайплайна доставки. Поэтому метод корректно работает с `temporary://`-источниками без каких-либо доработок.

HTTP-URL для `temporary://` — другая история: в ядре не зарегистрирован маршрут `image.style_temporary` (`PathProcessorImageStyles` обрабатывает только public и private префиксы, `ImageStyleRoutes::routes()` создаёт только `image.style_public`). Мы по той же причине не заводим маршрут для temporary в этом модуле.

Практический сценарий, для которого этого достаточно: производный файл создаётся, тут же потребляется и удаляется в рамках одной операции — например, миниатюра для отправки во внешний API или вложение в письмо:

```php
$source_uri = 'temporary://upload.jpg';
$builder = $dynamic_image_style->effect('image_scale_and_crop', ['width' => 100, 'height' => 100]);
$builder->createDerivative($source_uri);
$derivative_uri = $builder->buildUri($source_uri);
// Используем $derivative_uri (вложение, передача в API) — и удаляем сразу после.
```

Если нужен HTTP-URL для temporary — реализуйте собственный маршрут и обработчик пути по аналогии с private.
:::

## Контроллер доставки изображений

Теперь, когда структура URL ясна, посмотрим, что происходит при обращении к нему. За обработку запроса отвечает контроллер — он валидирует входные данные, извлекает эффекты и генерирует производное изображение.

::::: figure
::: figcaption
**Листинг 2** — Контроллер доставки изображений — `src/Controller/DynamicImageStyleController.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\example\DynamicImageStyle\DynamicImageStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final readonly class DynamicImageStyleController {

  public function __construct(
    private DynamicImageStyle $dynamicImageStyle,
    private ImageFactory $imageFactory,
    private StreamWrapperManagerInterface $streamWrapperManager,
    private ModuleHandlerInterface $moduleHandler,
    #[Autowire(service: 'lock')]
    private LockBackendInterface $lock,
  ) {}

  public function __invoke(Request $request): Response {
    [$original_uri, $effects, $compressed] = $this->validateRequest($request);

    $is_public = StreamWrapperManager::getScheme($original_uri) !== 'private';
    $headers = $this->checkFileAccess($original_uri, $is_public);

    $derivative_uri = $this->dynamicImageStyle->buildUri($original_uri, $effects);
    if (!\file_exists($derivative_uri)) {
      $this->generateDerivative($original_uri, $effects, $compressed);
    }

    return $this->deliverFile($derivative_uri, $headers, $is_public);
  }

  /**
   * @return array{string, list<array{0: string, 1: array<string, mixed>}>, string}
   */
  private function validateRequest(Request $request): array {
    $compressed = $request->query->getString('effects');
    $itok = $request->query->getString('itok');
    if ($compressed === '' || $itok === '') {
      throw new NotFoundHttpException();
    }

    $original_uri = $this->extractUri($request, $compressed);
    if ($original_uri === NULL) {
      throw new NotFoundHttpException();
    }

    if (!\hash_equals($this->dynamicImageStyle->generateToken($compressed, $original_uri), $itok)) {
      throw new NotFoundHttpException();
    }

    if (!\file_exists($original_uri)) {
      throw new NotFoundHttpException();
    }

    return [$original_uri, $this->tryDecompressEffects($compressed), $compressed];
  }

  /**
   * @return list<array{0: string, 1: array<string, mixed>}>
   */
  private function tryDecompressEffects(string $compressed): array {
    try {
      return $this->dynamicImageStyle->decompressEffects($compressed);
    }
    catch (\JsonException | \TypeError) {
      throw new NotFoundHttpException();
    }
  }

  /**
   * @return array<string, string>
   */
  private function checkFileAccess(string $uri, bool $is_public): array {
    if ($is_public) {
      return [];
    }

    $headers = $this->moduleHandler->invokeAll('file_download', [$uri]);
    if (\in_array(-1, $headers) || $headers === []) {
      throw new AccessDeniedHttpException();
    }

    return $headers;
  }

  /**
   * @param list<array{0: string, 1: array<string, mixed>}> $effects
   */
  private function generateDerivative(string $original_uri, array $effects, string $compressed): void {
    $lock_name = 'dynamic_image_style:' . Crypt::hashBase64($compressed . ':' . $original_uri);
    if (!$this->lock->acquire($lock_name)) {
      throw new ServiceUnavailableHttpException(
        retryAfter: 3,
        message: 'Image generation in progress. Try again shortly.',
      );
    }

    try {
      $success = $this->dynamicImageStyle->createDerivative($original_uri, $effects);
    }
    finally {
      $this->lock->release($lock_name);
    }

    if (!$success) {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Extracts the original image URI from the request path.
   *
   * URL: /{base}/styles/dynamic/{hash}/{scheme}/{target}[.{converted_ext}]
   * Result: {scheme}://{target} (without appended extension)
   */
  private function extractUri(Request $request, string $compressed): ?string {
    $path = $request->getPathInfo();
    $prefix = $this->detectPrefix($path);
    if ($prefix === NULL) {
      return NULL;
    }

    // Parse: {hash}/{scheme}/{target}.
    $parts = \explode('/', \substr($path, \strlen($prefix)), 3);
    if (\count($parts) !== 3) {
      return NULL;
    }
    [, $scheme, $target] = $parts;

    $target = $this->stripDerivativeExtension($target, $compressed);

    return $scheme . '://' . $target;
  }

  private function stripDerivativeExtension(string $target, string $compressed): string {
    try {
      $effects = $this->dynamicImageStyle->decompressEffects($compressed);
      $image_style = $this->dynamicImageStyle->createImageStyle($effects);
      $current_extension = \pathinfo($target, \PATHINFO_EXTENSION);
      $original_extension = \pathinfo(\pathinfo($target, \PATHINFO_FILENAME), \PATHINFO_EXTENSION);

      if ($original_extension !== '' && $image_style->getDerivativeExtension($original_extension) === $current_extension) {
        return \substr($target, 0, -(\strlen($current_extension) + 1));
      }
    }
    catch (\JsonException | \TypeError) {
      // Invalid effects — return target as-is, validation will fail later.
    }

    return $target;
  }

  private function detectPrefix(string $path): ?string {
    $wrapper = $this->streamWrapperManager->getViaScheme('public');
    \assert($wrapper instanceof LocalStream);
    $public_prefix = '/' . $wrapper->getDirectoryPath() . '/styles/dynamic/';
    if (\str_starts_with($path, $public_prefix)) {
      return $public_prefix;
    }

    $private_prefix = '/system/files/styles/dynamic/';
    if (\str_starts_with($path, $private_prefix)) {
      return $private_prefix;
    }

    return NULL;
  }

  private function deliverFile(string $derivative_uri, array $headers = [], bool $is_public = TRUE): BinaryFileResponse {
    $image = $this->imageFactory->get($derivative_uri);
    $uri = $this->streamWrapperManager->normalizeUri($derivative_uri);

    $headers += [
      'Content-Type' => $image->getMimeType(),
      'Content-Length' => $image->getFileSize(),
    ];

    return new BinaryFileResponse(
      file: $uri,
      status: Response::HTTP_OK,
      headers: $headers,
      public: $is_public,
    );
  }

}
```
:::::

1. **`__invoke()`** — оркестрирует процесс: валидация → проверка доступа → поиск/генерация → отдача.

2. **`::extractUri()`** и **`::stripDerivativeExtension()`** — извлекают URI оригинала из пути. Первый разбирает путь, пропускает `{hash}`, достаёт схему и путь до оригинала; второй обрабатывает частный случай: если эффекты меняют формат (например, `.jpg → .webp`), путь оканчивается на `image.jpg.webp`, и метод срезает лишнее расширение → `image.jpg`.

3. **`::tryDecompressEffects()`** — оборачивает декомпрессию в try/catch: невалидный JSON → 404.

4. **`::validateRequest()`** — собирает всё вместе: проверяет, что `effects` и `itok` переданы, что URI оригинала удалось извлечь, что оригинал физически существует, и что `itok` совпадает с HMAC от пары `сжатые_эффекты + URI`.

5. **`::detectPrefix()`** — определяет, это запрос для `public://` или `private://`.

6. **`::checkFileAccess()`** — публичные файлы пропускает; для приватных вызывает `hook_file_download`, собирает заголовки или бросает 403.

7. **`::generateDerivative()`** — захватывает [блокировку][Lock API] по хешу запроса; если занят — 503 с `Retry-After: 3`; освобождает через `finally`.

8. **`::deliverFile()`** — возвращает `BinaryFileResponse` с `Content-Type` и `Content-Length`; для приватных файлов добавляет заголовки из хука.

Как видно из методов выше, контроллер следует той же цепочке, что и стандартный `\Drupal\image\Controller\ImageStyleDownloadController`: валидация → проверка доступа → генерация → отдача. Отличия:

- URI оригинала извлекается из пути запроса, а не из query-параметров;
- эффекты приходят из query-параметра `effects`, а не из конфигурации стиля;
- токен — собственный HMAC от пары «сжатые эффекты + URI» через `::generateToken()`, а не `ImageStyle::getPathToken()`;
- демо-изображения не нужны, так как стиль виртуальный и не настраивается из UI;
- логирование не добавлено, чтобы не усложнять код (но при необходимости его легко внедрить);
- `allow_insecure_derivatives` опущено по той же причине — у нас нет сценариев небезопасной генерации, и реализация намеренно упрощена.

## Маршруты

Теперь, когда контроллер готов, направим запросы к нему через собственные маршруты — так мы не затронем стандартные маршруты Drupal.

::::: figure
::: figcaption
**Листинг 3** — Регистрация маршрутов — `src/Routing/RouteProvider.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Routing;

use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\example\Controller\DynamicImageStyleController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final readonly class RouteProvider {

  public function __construct(
    private StreamWrapperManagerInterface $streamWrapperManager,
  ) {}

  public function __invoke(): RouteCollection {
    $routes = new RouteCollection();

    $wrapper = $this->streamWrapperManager->getViaScheme('public');
    \assert($wrapper instanceof LocalStream);
    $routes->add('example.dynamic_image_style.public', new Route(
      path: '/' . $wrapper->getDirectoryPath() . '/styles/dynamic',
      defaults: [
        '_controller' => DynamicImageStyleController::class,
        '_disable_route_normalizer' => TRUE,
      ],
      requirements: [
        '_access' => 'TRUE',
      ],
      options: [
        'no_cache' => TRUE,
      ],
    ));

    $routes->add('example.dynamic_image_style.private', new Route(
      path: '/system/files/styles/dynamic',
      defaults: [
        '_controller' => DynamicImageStyleController::class,
        '_disable_route_normalizer' => TRUE,
      ],
      requirements: [
        '_access' => 'TRUE',
      ],
      options: [
        'no_cache' => TRUE,
      ],
    ));

    return $routes;
  }

}
```
:::::

Первый маршрут `example.dynamic_image_style.public` — на `/{base_path}/styles/dynamic`, в стандартной установке это `/sites/default/files/styles/dynamic`. Второй, `example.dynamic_image_style.private`, — на `/system/files/styles/dynamic`. Оба обрабатывает наш контроллер. Приватный маршрут корректно матчится только при наличии обработчика пути из следующего раздела.

::: note [Модуль redirect и нормализатор маршрутов]
При использовании модуля [drupal/redirect](https://www.drupal.org/project/redirect) обоим маршрутам нужен `'_disable_route_normalizer' => TRUE` в `defaults`. Без него route normalizer обнаружит несоответствие URL и пути маршрута и выдаст HTTP 301 — хвост `{hash}/{scheme}/{target}` потеряется. Модуль через `RouteSubscriber` изменяет известные маршруты (`image.style_public`, `image.style_private`, `system.files`), добавляя флаг автоматически. Наши маршруты он не знает — флаг нужен явно. Без модуля свойство игнорируется — его можно оставить навсегда, и при установке redirect проблемы не возникнут.
:::

## Перехват пути

Чтобы обеспечить совпадение пути с зарегистрированным маршрутом, обработчик путей перехватывает входящие запросы до ядровых обработчиков и обрезает хвост `/{hash}/{scheme}/{target}`.

::::: figure
::: figcaption
**Листинг 4** — Обработчик входящих путей — `src/PathProcessor/DynamicImageStylePathProcessor.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rewrites dynamic image style URLs before core's PathProcessorImageStyles.
 *
 * Intercepts /{base_path}/styles/dynamic/... URLs and rewrites to the internal
 * route. The effects data and token are in query parameters, so no extraction
 * is needed — just a path rewrite to avoid core's image style routing.
 */
#[AutoconfigureTag('path_processor_inbound', ['priority' => 301])]
final readonly class DynamicImageStylePathProcessor implements InboundPathProcessorInterface {

  public function __construct(
    private StreamWrapperManagerInterface $streamWrapperManager,
  ) {}

  public function processInbound($path, Request $request): string {
    if (!$request->query->has('effects')) {
      return $path;
    }

    // Public files: /sites/default/files/styles/dynamic/...
    $wrapper = $this->streamWrapperManager->getViaScheme('public');
    \assert($wrapper instanceof LocalStream);
    $directory_path = $wrapper->getDirectoryPath();
    $public_prefix = '/' . $directory_path . '/styles/dynamic/';
    if (\str_starts_with($path, $public_prefix)) {
      return '/' . $directory_path . '/styles/dynamic';
    }

    // Private files: /system/files/styles/dynamic/...
    // Set 'file' query param to block PathProcessorFiles (it skips processing
    // when 'file' is already present), then rewrite to the registered route.
    $private_prefix = '/system/files/styles/dynamic/';
    if (\str_starts_with($path, $private_prefix)) {
      $request->query->set('file', 'dynamic');
      return '/system/files/styles/dynamic';
    }

    return $path;
  }

}
```
:::::

Наш обработчик имеет приоритет 301 — выше, чем у `PathProcessorImageStyles` (300) и `PathProcessorFiles` (200), — поэтому он получает управление первым. Он срабатывает только при наличии параметра `effects` в query, и его задача — обеспечить корректную маршрутизацию путём обрезки хвоста `/{hash}/{scheme}/{target}`. В результате путь точно совпадает с одним из зарегистрированных маршрутов.

**Для публичных файлов:** путь `/{base_path}/styles/dynamic/{hash}/{scheme}/{target}` переписывается в `/{base_path}/styles/dynamic`. `PathProcessorImageStyles` не вмешивается (в остатке `dynamic`, 0 слешей при требуемых минимум 2[^path-processor-image-style-2]), и до роутинга доходит зарегистрированное значение.

При обработке **приватных файлов** возникает специфическая проблема: если просто обрезать путь до `/system/files/styles/dynamic`, `PathProcessorFiles` попытается обработать его, что приведёт к ошибке 404[^path-processor-files-404]. Чтобы избежать этого, наш обработчик заранее устанавливает параметр `file` в query (`$request->query->set('file', 'dynamic')`). Благодаря этому `PathProcessorFiles` пропускает путь, а роутинг корректно матчит `/system/files/styles/dynamic`.

`image.style_private` избегает этого через `PathProcessorImageStyles`, который разбирает `/system/files/styles/{style}/{scheme}/{file}`, устанавливает `file` в query, возвращает `/system/files/styles/{style}/{scheme}` и блокирует `PathProcessorFiles` (проверяет `!$request->query->has('file')`).

Наш обработчик применяет тот же приём — перед переписыванием пути выставляет `$request->query->set('file', 'dynamic')`. Важен факт присутствия `file` в query: `PathProcessorFiles` пропускает путь, и роутинг корректно матчит `/system/files/styles/dynamic`.

Важно, что наш контроллер получает путь через `$request->getPathInfo()` — то, что запросил пользователь, а не внутренний путь. Это позволяет извлекать путь до картинки универсально, прямо из запроса, независимо от типа файла (публичный или приватный). Такой подход исключает зависимость от параметра `file` в query (который есть только для приватных файлов) и упрощает логику контроллера.

## Иммутабельный строитель

Чтобы сократить объём кода при повторном использовании одних и тех же наборов эффектов, вместо прямой передачи массива в `DynamicImageStyle` создадим иммутабельный строитель. Он позволит собрать базовый пресет один раз и переиспользовать его, добавляя новые эффекты через цепочку вызовов.

::::: figure
::: figcaption
**Листинг 5** — Иммутабельный строитель — `src/DynamicImageStyle/DynamicImageStyleBuilder.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\DynamicImageStyle;

final readonly class DynamicImageStyleBuilder implements \Stringable {

  private const string DEFAULT_FORMAT = 'webp';

  /**
   * @param list<array{0: string, 1: array<string, mixed>}> $effects
   */
  public function __construct(
    private DynamicImageStyle $dynamicImageStyle,
    private array $effects = [],
    private ?string $uri = NULL,
  ) {}

  #[\Override]
  public function __toString(): string {
    if ($this->uri === NULL) {
      return '';
    }
    return $this->dynamicImageStyle->buildUrl($this->uri, $this->resolveEffects());
  }

  public function effect(string $id, array $data = []): self {
    return new self($this->dynamicImageStyle, [...$this->effects, [$id, $data]], $this->uri);
  }

  public function buildUrl(string $uri): string {
    return $this->dynamicImageStyle->buildUrl($uri, $this->resolveEffects());
  }

  public function buildUri(string $uri): string {
    return $this->dynamicImageStyle->buildUri($uri, $this->resolveEffects());
  }

  public function createDerivative(string $uri): bool {
    return $this->dynamicImageStyle->createDerivative($uri, $this->resolveEffects());
  }

  public function getUri(): ?string {
    return $this->uri;
  }

  /**
   * @return list<array{0: string, 1: array<string, mixed>}>
   */
  public function getEffects(): array {
    return $this->effects;
  }

  /**
   * @return list<array{0: string, 1: array<string, mixed>}>
   */
  private function resolveEffects(): array {
    if (\array_any($this->effects, static fn (array $effect): bool => $effect[0] === 'image_convert')) {
      return $this->effects;
    }
    return [...$this->effects, ['image_convert', ['extension' => self::DEFAULT_FORMAT]]];
  }

}
```
:::::

Для генерации URL или создания производного изображения нужно передать URI на финальном этапе — с помощью методов `::buildUrl()`, `::buildUri()` или `::createDerivative()`. Например:

```php
$url = $dynamic_image_style
  ->effect('image_scale_and_crop', ['width' => 100, 'height' => 100])
  ->buildUrl($uri);
```

**Зачем нужна иммутабельность?** Метод `::effect()` не изменяет текущий экземпляр, а возвращает новый — с добавленным эффектом. Это позволяет безопасно переиспользовать частично сконфигурированный строитель: добавление нового эффекта никогда не изменит уже существующий экземпляр.

Практический пример — базовый пресет для миниатюр:

```php
$thumbnail = $dynamic_image_style
  ->effect('image_desaturate')
  ->effect('image_scale_and_crop', ['width' => 100, 'height' => 100]);

// $thumbnail не изменяется — каждый вызов возвращает новый экземпляр.
$webp_url = $thumbnail->effect('image_convert', ['extension' => 'webp'])->buildUrl($uri);
$avif_url = $thumbnail->effect('image_convert', ['extension' => 'avif'])->buildUrl($uri);
```

Без иммутабельности вызов `->effect(...)` менял бы состояние `$thumbnail`, и последующее добавление другого эффекта давало бы неожиданный результат — накапливало оба эффекта в одном экземпляре.

Помимо защиты от побочных эффектов, строитель даёт ещё одно преимущество: возможность централизованно управлять обязательными операциями — например, автоматически конвертировать все изображения в нужный формат или очищать метаданные.

Принудительная конвертация реализована в методе `::resolveEffects()`. Он проверяет, включён ли эффект `image_convert` в цепочку. Если нет, он автоматически добавляет конвертацию в формат WebP. Такой подход — необязательная, но полезная возможность: его можно отключить или заменить на другую универсальную операцию, например, очистку метаданных — эффект `image_effects_strip_metadata` из контриб-модуля [drupal/image_effects](https://www.drupal.org/project/image_effects) удаляет EXIF и другие встроенные данные из файла.

Интерфейс `\Stringable` позволяет неявно преобразовать экземпляр строителя в строку — в нашем случае это URL производной картинки с применёнными эффектами. Например, при передаче объекта в контекст Twig‑шаблона достаточно просто вывести переменную — без вызова дополнительных методов: система автоматически вызовет `__toString()`, и мы получим готовый URL.

Важный нюанс: для работы `__toString()` URI должен быть передан в конструктор при создании экземпляра строителя. В чистом PHP-коде это необязательно — там мы вызываем `buildUrl($uri)` напрямую. В шаблонах Twig, наоборот, удобнее начинать с URI: сначала передаём его в строитель, а затем добавляем эффекты. Логика работы идентична, отличается лишь порядок вызовов.

В итоге, независимо от способа работы, реализация `\Stringable` заметно упрощает код в шаблонах. Вам больше не нужно помнить о дополнительных методах — всё происходит автоматически.

## Интеграция с Twig

Чтобы применять динамические стили изображений прямо в шаблонах Twig, добавим несколько удобных [Twig‑фильтров][twig-extension].

::::: figure
::: figcaption
**Листинг 6** — Регистрация Twig-фильтров — `src/Twig/DynamicImageStyleExtension.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Twig;

use Drupal\example\DynamicImageStyle\DynamicImageStyle;
use Drupal\example\DynamicImageStyle\DynamicImageStyleBuilder;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

#[AutoconfigureTag('twig.extension')]
final class DynamicImageStyleExtension extends AbstractExtension {

  public function __construct(
    private readonly DynamicImageStyle $dynamicImageStyle,
  ) {}

  public function getFilters(): array {
    return [
      new TwigFilter('dynamic_image_style', $this->dynamicImageStyle(...)),
      new TwigFilter('image_scale_crop', $this->imageScaleCrop(...)),
      new TwigFilter('image_scale', $this->imageScale(...)),
      new TwigFilter('image_convert', $this->imageConvert(...)),
    ];
  }

  /**
   * @param list<array{0: string, 1: array<string, mixed>}> $effects
   */
  public function dynamicImageStyle(string|DynamicImageStyleBuilder $input, array $effects = []): DynamicImageStyleBuilder {
    $builder = $this->ensureBuilder($input);
    foreach ($effects as [$id, $data]) {
      $builder = $builder->effect($id, $data);
    }
    return $builder;
  }

  public function imageScaleCrop(string|DynamicImageStyleBuilder $input, int $width, int $height): DynamicImageStyleBuilder {
    return $this->ensureBuilder($input)->effect('image_scale_and_crop', ['width' => $width, 'height' => $height]);
  }

  public function imageScale(string|DynamicImageStyleBuilder $input, ?int $width = NULL, ?int $height = NULL): DynamicImageStyleBuilder {
    $data = \array_filter(['width' => $width, 'height' => $height], static fn ($v): bool => $v !== NULL);
    return $this->ensureBuilder($input)->effect('image_scale', $data);
  }

  public function imageConvert(string|DynamicImageStyleBuilder $input, string $extension): DynamicImageStyleBuilder {
    return $this->ensureBuilder($input)->effect('image_convert', ['extension' => $extension]);
  }

  private function ensureBuilder(string|DynamicImageStyleBuilder $input): DynamicImageStyleBuilder {
    if ($input instanceof DynamicImageStyleBuilder) {
      return $input;
    }
    return new DynamicImageStyleBuilder($this->dynamicImageStyle, uri: $input);
  }

}
```
:::::

Этот класс регистрирует в Twig четыре фильтра. **Главный из них — `dynamic_image_style`** — прямой мостик к `DynamicImageStyle`, позволяющий применять любые эффекты к изображениям. Сделать это можно двумя способами: передать все эффекты сразу либо вызвать фильтры по цепочке:

```twig
{# Вариант 1 #}
{% set image_url = image_uri|dynamic_image_style([
  ['image_scale_and_crop', {'width': 100, 'height': 100}],
  ['image_desaturate', {}]
]) %}
<img src="{{ image_url }}">

{# Вариант 2 #}
{% set image_url = image_uri
  |dynamic_image_style([['image_scale_and_crop', {'width': 100, 'height': 100}]])
  |dynamic_image_style([['image_desaturate', {}]]) %}
<img src="{{ image_url }}">
```

Обратите внимание: в первом варианте мы передаём массив эффектов, а во втором — вызываем фильтр несколько раз. Такой подход возможен благодаря тому, что `$input` принимает как строку с URI, так и готовый экземпляр строителя. Первый фильтр получает строку, все последующие — уже строитель с сохранённым внутри URI. За такое поведение отвечает `::ensureBuilder()`: видит строитель — возвращает без изменений, видит строку — создаёт новый строитель. Поэтому URI в конструкторе строителя стоит последним и опциональным — он нужен только в момент старта цепочки.

Иммутабельность строителя работает и в Twig: каждый фильтр возвращает новый экземпляр, не трогая исходный. Например:

```twig
{# Базовый пресет: десатурация + обрезка #}
{% set thumbnail = image_uri
  |dynamic_image_style([['image_desaturate', {}]])
  |image_scale_crop(100, 100) %}

{# thumbnail не изменяется — каждый фильтр возвращает новый экземпляр #}
{% set webp_url = thumbnail|image_convert('webp') %}
{% set avif_url = thumbnail|image_convert('avif') %}
```

Разобравшись с базовым фильтром, посмотрим, как упростить типовые задачи. Часто используемые эффекты удобно обернуть в отдельные фильтры с тем же названием — как мы поступили с `image_scale_crop`, `image_scale` и `image_convert`.

```twig
{# image_scale_crop #}
{{ image_uri|dynamic_image_style([['image_scale_and_crop', {'width': 100, 'height': 100}]]) }}
{{ image_uri|image_scale_crop(100, 100) }}

{# image_scale #}
{{ image_uri|dynamic_image_style([['image_scale', {'width': 200}]]) }}
{{ image_uri|image_scale(200) }}

{# image_scale (только ширина) #}
{{ image_uri|dynamic_image_style([['image_scale', {'height': 150}]]) }}
{{ image_uri|image_scale(null, 150) }}

{# image_convert #}
{{ image_uri|dynamic_image_style([['image_convert', {'extension': 'avif'}]]) }}
{{ image_uri|image_convert('avif') }}
```

Таким образом, количество бойлерплейта сократилось: такие конструкции проще запоминать и читать, а создание обёртки требует минимальных усилий. Этот принцип можно применять и к другим эффектам — добавляя удобные фильтры и задавая значения по умолчанию.

Поскольку все фильтры возвращают `DynamicImageStyleBuilder`, реализующий `\Stringable`, результат можно выводить напрямую — без промежуточного `{% set %}`:

```twig
<img src="{{ image_uri|image_scale_crop(100, 100) }}">
<img src="{{ image_uri|image_scale(200)|image_convert('avif') }}">
```

::: note [Управление форматом по умолчанию]
Автоматическая конвертация реализована в `::resolveEffects()` — она срабатывает, только если ни один фильтр явно не указал формат. Чтобы получить PNG или другой формат, добавьте `|image_convert('png')` в конец цепочки либо удалите этот автоматический эффект из строителя.
:::

## Регистрация сервисов

Последний штрих — зарегистрировать все необходимые классы в [сервис-контейнере][services].

::::: figure
::: figcaption
**Листинг 7** — Регистрация сервисов — `src/ExampleServiceProvider.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\example\Controller\DynamicImageStyleController;
use Drupal\example\DynamicImageStyle\DynamicImageStyle;
use Drupal\example\PathProcessor\DynamicImageStylePathProcessor;
use Drupal\example\Routing\RouteProvider;
use Drupal\example\Twig\DynamicImageStyleExtension;
use Symfony\Component\DependencyInjection\Definition;

final readonly class ExampleServiceProvider implements ServiceProviderInterface {

  #[\Override]
  public function register(ContainerBuilder $container): void {
    $autowire = static fn (string $class): Definition => $container
      ->autowire($class)
      ->setPublic(TRUE)
      ->setAutoconfigured(TRUE);

    $container->setParameter('example.skip_procedural_hook_scan', TRUE);

    $autowire(DynamicImageStyle::class);
    $autowire(DynamicImageStyleController::class);
    $autowire(RouteProvider::class);

    $autowire(DynamicImageStylePathProcessor::class);
    $autowire(DynamicImageStyleExtension::class);
  }

}
```
:::::

После регистрации всех сервисов модуль полностью готов. Всё, что нужно для работы динамических стилей, — в одном модуле: оркестратор, контроллер, обработчик путей, строитель и Twig-расширение. Включите модуль — и всё готово.

## Что учесть при использовании в продакшене

Модуль намеренно не содержит механизма очистки производных изображений. В большинстве случаев это не проблема и делать ничего не нужно. Если проблемы всё же возникают — подход к решению нужно подбирать под специфику проекта, так как они могут быть разными и универсального решения тут нет.

**Производные не устаревают**, что приводит к накоплению мусора. URL динамического стиля включает хеш от комбинации применённых эффектов. Изменился набор эффектов — изменился хеш — изменился путь. Браузер запрашивает новый URL, а старый файл остаётся на диске и больше не используется. То же происходит, если удалить оригинал: контроллер уже не отдаст производную (проверяет существование оригинала), но файл никуда не девается. Оба случая — смена комбинации эффектов в коде и удаление исходника — со временем накапливают мусор на диске.

Сам по себе мусор не так страшен, но на определённых масштабах это может привести к **росту потребления дискового пространства**. Каждая уникальная комбинация эффектов и исходника создаёт отдельный файл. Универсальное решение для обеих проблем с накоплением — автоматическая очистка по времени модификации: cron-задача, удаляющая файлы в `styles/dynamic/` старше N дней. Отслеживайте размер директории, чтобы подобрать подходящий интервал ротации, или используйте точечный update-хук при деплое для немедленной очистки.

Есть и другой сценарий роста диска, связанный не с новыми комбинациями эффектов, а с повторными обращениями по однажды созданным URL.

**Повторная генерация по старым URL.** `itok` подтверждает, что URL был выдан этим сайтом, но не «отзывает» его. Если комбинация эффектов удалена из кода, а оригинальный файл по-прежнему существует, старый URL остаётся технически валидным. При обращении он снова создаст производную на диске. Это может произойти через веб-архив, поисковый кеш, внешние ссылки или закладки.

Практический риск — нежелательный рост диска при массовом обходе старых URL. На большинстве проектов это маловероятное пересечение условий, но если управление жизненным циклом токенов становится реальной задачей — стоит отвязаться от `PrivateKey` и использовать собственный ключ в `State`. Тогда его ротация затронет только динамические стили, без побочных эффектов на остальной сайт. Ротация же `PrivateKey` или смена `hash_salt` — крайние меры: они затрагивают CSRF-токены, стандартные стили изображений, media iFrame и хеши прав пользователей.

## Ресурсы

- [Готовый модуль](examples/example)
- [Модуль с демо-контроллером](examples/example_demo) — может пригодиться как пример и для проверки, что всё работает корректно.

[Lock API]: ../../../../2020/04/30/drupal-8-9-lock-and-lock-persistent/article.ru.md
[inbound-outbound-processor]: ../../../../2018/05/30/drupal-8-inbound-outbound-processor/article.ru.md
[twig-extension]: ../../../../2017/08/08/drupal-8-how-to-create-a-custom-twig-extension/article.ru.md
[services]: ../../../../2017/06/21/drupal-8-services/article.ru.md

[^effects]: Эффект — операция (плагин `#[ImageEffect]`) над исходным изображением. Эффекты вызываются последовательно в порядке, заданном стилем. Они не обязательно меняют визуал: например, могут удалять метаданные, не влияющие на картинку.

[^public-path]: Значение по умолчанию — `sites/default/files`, но может быть изменено через `$settings['file_public_path']` в settings.php. Поэтому путь всегда нужно получать динамически, а не хардкодить.

[^system-files-prefix]: `/system/files` — это **URL-контракт** для приватных файлов в Drupal, а не физический путь. Реальное расположение задаётся через `$settings['file_private_path']` (обычно вне web root). Конфигурация nginx по умолчанию не пытается отдавать файлы с этим префиксом напрямую — запрос уходит в Drupal, где обработчики путей передают его контроллерам `system.files` или `system.private_file_download`. Те уже проверяют права доступа, читают содержимое и отдают файл через PHP. За `system/files` нет статической директории — это лишь зарезервированное URL-пространство. Такой подход сохраняет консистентность с `image.style_private` и другими механизмами приватных файлов ядра.

[^inode-overhead]: Каждая директория в пути занимает отдельный inode. Для пути `{token}/public/image.jpg` это 2 директории + 1 файл = **3 inode** на пару «эффекты+изображение». Глубже: `{token}/public/2026-01/image.jpg` → **4 inode**. При текущем подходе (`{hash}` только от эффектов) два изображения с одним набором эффектов используют общие директории: `{hash}/public/foo.jpg` и `{hash}/public/bar.jpg` → **2 директории + 2 файла = 4 inode**. Если бы `{token}` входил в путь, те же два файла дали бы **4 директории + 2 файла = 6 inode** — `public/` уже нельзя разделить.

[^subdirectory-limits]: В Linux с **ext4** лимит поддиректорий по умолчанию — [**64 998**](https://docs.kernel.org/filesystems/ext4/inodes.html#:~:text=there%20cannot%20be%20more%20than%2064%2C998%20subdirectories%20in%20a%20directory), и на сайтах с каталогами товаров его легко превысить. Технически через `dir_nlink` лимит можно поднять до ~4,29 млрд, но после пары миллионов директорий производительность ФС начинает деградировать.

    65 000 звучит много, но в контексте адаптивных изображений это небольшой лимит. Одно исходное изображение, адаптированное под разные breakpoints и DPI, даёт ~10 производных. С учётом вариантов «тизер/полное представление» — уже ~20. При тысяче исходников (скромный каталог) получается 20 000 производных — и это только простейший сценарий.

[^path-processor-image-style-2]: Условие `substr_count($rest, '/') >= 2` гарантирует, что после обрезки префикса в пути остались все три компонента: `{image_style_id}`, `{scheme}` и `{file}` (например, `dynamic/public/image.jpg` → 2 слеша).

      Если слешей меньше двух (как в `dynamic` или `dynamic/public`), путь считается неполным. `PathProcessorImageStyles` возвращает исходный путь и пропускает обработку.

      Актуально для [Drupal 10+](https://git.drupalcode.org/project/drupal/-/blob/645ffba8cc5ed74e3c1eaa63bfacc2c3a7c6ee92/core/modules/image/src/PathProcessor/PathProcessorImageStyles.php#L62-75).

[^path-processor-files-404]: `PathProcessorFiles` увидит путь с `/system/files/` и отсутствие `file` в query. Он извлечёт `styles/dynamic`, положит в `$request->query->set('file', 'styles/dynamic')` и вернёт `/system/files`. Роутинг матчит `system.files`, который попытается отдать `private://styles/dynamic` — 404.