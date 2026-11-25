Пагинация в Drupal существует с первых релизов и, вероятно, с тех пор почти не менялась. Работает она неплохо — нейтрально и универсально, хотя, возможно, и неэффективно.

«Проблемы» начинаются, когда на проекте появляется SEO-специалист. Сколько специалистов — столько и мнений о том, как должна работать и выглядеть пагинация. Почти на каждом моём проекте её пытались «исправить». В этом материале я покажу, какие решения обычно применяются, объясню их логику и какую «проблему» они закрывают.

Но сразу предостерегу: не стоит слепо копировать всё, что здесь написано. Решения, которые я покажу, — собирательный образ из множества рекомендаций, прошедших через меня, своего рода золотая середина без крайностей.

Мнений много, и кто прав — вопрос открытый. Польза предложенных решений субъективна, поэтому оцените каждый случай и решите, что подходит вам. Может оказаться, что пригодится одно и не пригодится другое. А возможно, все советы пролетят мимо — и вас вполне устроит стандартное поведение, ведь оно неплохое.

::: note [Несколько пагинаторов на странице]
Если на странице используется несколько пагинаторов (`?page=1,2,,4`), текущий код их не поддерживает: `PageNumberPathProcessor` схлопывает значение через `getInt('page')`, а `InvalidPageNotFound` отвергает запятую. Вам потребуется самостоятельно доработать части кода, которые должны её учитывать. Но, честно, я такого не использовал и ни разу не видел на живых проектах, поэтому счёл нецелесообразным даже добавлять поддержку и усложнять примеры ради этого.
:::

## Где улучшения не применяются

Для некоторых решений потребуется ограничить набор путей, где улучшения не должны срабатывать. Чтобы не объяснять эту логику в каждом разделе, она вынесена в отдельный — можете вернуться к нему, когда понадобится.

::::: figure
::: figcaption
**Листинг 1** — Политика отключения улучшений — `src/Pager/PaginationPathPolicy.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Pager;

final class PaginationPathPolicy {

  private const array EXCLUDED_SEGMENTS = [
    '/admin/',
    '/jsonapi/',
    '/sitemap.xml/',
  ];

  public static function isExcluded(string $path): bool {
    $path = \strtolower(\rawurldecode($path)) . '/';

    return \array_any(
      array: self::EXCLUDED_SEGMENTS,
      callback: static fn (string $segment): bool => \str_contains($path, $segment),
    );
  }

}
```
:::::

Это простой вспомогательный класс с единственным методом `::isExcluded()`, который проверяет, нужно ли отключать обработчики для текущего пути. Список исключений вынесен в константу `EXCLUDED_SEGMENTS`, а проверка идёт через `array_any()`.

Я обычно исключаю административные страницы — большинство улучшений из этой статьи на них не нужны (но работать будут и там — решайте сами). Дополняйте этот список исходя из потребностей вашего проекта. Разберём, как устроена проверка:

- Путь декодируется и приводится к нижнему регистру: Drupal открывает `/ADMIN/content` и `/%61dmin/content` так же, как `/admin/content`, и без этого такие адреса обошли бы проверку.[^route-normalizer]

- Фрагменты из списка (`/admin/`, `/jsonapi/`, `/sitemap.xml/`) ищутся как подстроки, а не в начале пути: на мультиязычных сайтах перед ними может стоять языковой префикс (`/en/admin/content`).

- К пути добавляется `/` в конце — это даёт сразу два следствия. Во-первых, под проверку попадают как `/admin` без завершающего слэша, так и `/admin/`, так что отдельные условия для них не нужны.[^route-normalizer] Во-вторых, искомые фрагменты заканчиваются слэшем, поэтому путь должен совпасть с сегментом целиком: `/administrator` или `/admin-panel` под проверку не попадают. Без слэша в конце искомого фрагмента `str_contains($path, '/admin')` сработал бы и на них.

Побочный эффект такого упрощения: алиас вида `/blog/admin/foo` тоже окажется в исключениях. Поэтому убедитесь, что у вас нет подобных коллизий.

::: note [Почему не по маршруту]
Страницы надёжнее определять по маршруту, но во входящих обработчиках путей и в подписчиках на `KernelEvents::REQUEST` из этой статьи он недоступен: обработчики вызываются провайдером маршрутов в процессе поиска маршрута, а подписчики срабатывают раньше маршрутизатора. Единственное, что известно в этих местах, — путь, поэтому во всех остальных используется та же проверка по пути.
:::

### Исключение sitemap.xml для drupal/simple_sitemap

Если вы используете этот модуль, исключение `/sitemap.xml/` обязательно; если нет — его можно убрать из списка.

Контроллер для `sitemap.xml` использует query-параметр `page` для собственных нужд — в обход пагинации Drupal и её менеджера: он трактует значение как дельту чанка, а не как страницу. `SITEMAP_CHUNK_FIRST_DELTA = 1`, поэтому `/sitemap.xml` для него — дельта 0, а `/sitemap.xml?page=1` — дельта 1.

### Исключение JSON:API

С JSON:API (`?page[limit]=5&page[offset]=10`) улучшения из этой статьи конфликтуют: `page` там массив, поэтому проверка из раздела про HTTP 404 ответит на такие запросы HTTP 404, а смещение номера страницы для API не имеет смысла. Поэтому `/jsonapi/` уже входит в список исключений из листинга 1; если JSON:API у вас нет, его можно убрать. Можно и не исключать его целиком, а научить код работать с массивом `page`, но это придётся делать самостоятельно: в статье такой вариант не разбирается. JSON:API я не использую, но если бы использовал — пошёл бы по пути исключения.

## Конвертация нумерации: 0-based ↔ 1-based

Самая распространённая «проблема»: Drupal использует нумерацию страниц с нуля. Это значит, что `?page=0` — первая страница, `?page=1` — вторая, и так далее. Пользователям, скорее всего, без разницы, тем более что браузеры сейчас часто не показывают URL. Но некоторых это сбивает с толку, и даже у владельцев сайтов порой возникают вопросы. SEO-специалисты же утверждают, что поисковики тоже обращают на это внимание. Спорить тут не о чем: для технически неподготовленных пользователей нумерация с нуля действительно нелогична.

Пытаться изменить внутреннее устройство пагинации в Drupal бессмысленно — вы тут же увязнете в ворохе проблем. Где-то номера страниц жёстко привязаны к тому, что 0 — первая страница. Вспомните любые range-запросы с `range(self::LIMIT * $current_page, self::LIMIT)` — и масштаб бедствия станет ясен: зависимость от нумерации с нуля выходит далеко за пределы простого декоратора [сервиса][services] и уходит в реализации потребителей сервиса. Решение при этом простое — [входящие и исходящие обработчики путей][inbound-outbound-processor]. Этот API и создан для таких задач. Мы не будем трогать внутреннее устройство пагинации, а сосредоточимся на том, что уходит наружу и возвращается обратно. На клиенте всё будет работать как надо, поведение останется нетронутым — только это и важно. Поэтому на выходе будем прибавлять 1 (`?page=0` → `?page=1`), а на входе — вычитать (`?page=1` → `?page=0`).

::::: figure
::: figcaption
**Листинг 2** — Обработчик для смещения номера страницы — `src/Pager/PageNumberPathProcessor.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Pager;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('path_processor_inbound', ['priority' => 1000])]
#[AutoconfigureTag('path_processor_outbound', ['priority' => -1000])]
final readonly class PageNumberPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  public function processInbound($path, Request $request): string {
    if (PaginationPathPolicy::isExcluded($path)) {
      return $path;
    }

    // Guard against double processing: Url::createFromRequest() (used by
    // tokens and Metatag) calls processInbound() again on the same request,
    // which would decrement the already-converted page number a second time.
    if ($request->query->has('page') && !$request->attributes->has('_page_number_processed')) {
      $page_internal = \max(0, $request->query->getInt('page') - 1);
      $request->query->set('page', $page_internal);
      $request->attributes->set('_page_number_processed', value: TRUE);
    }

    return $path;
  }

  /**
   * @param array{query?: array<string, mixed>} $options
   */
  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL): string {
    if (PaginationPathPolicy::isExcluded($path)) {
      return $path;
    }

    $page = $options['query']['page'] ?? NULL;
    if (!\is_int($page) && !\is_string($page)) {
      return $path;
    }

    $page = (int) $page;
    if ($page === 0) {
      unset($options['query']['page']);
    }
    elseif ($page > 0) {
      $options['query']['page'] = $page + 1;
    }

    return $path;
  }

}
```
:::::

Как во входящем, так и в исходящем обработчике потребуется `PaginationPathPolicy` из раздела «Где улучшения не применяются» — дальше всё просто:

- `::processInbound()` — обновляет номер страницы во входящем запросе для внутреннего использования. Первым делом проверяем через `PaginationPathPolicy::isExcluded()`, нужно ли пропустить обработку. Затем смотрим, есть ли в запросе значение query-параметра `page` и не установлен ли атрибут `_page_number_processed`. Если параметр есть, а атрибута нет, вычисляем внутренний номер страницы — вычитаем 1, не позволяя результату опуститься ниже 0. Перезаписываем значение `'page'` и устанавливаем атрибут `_page_number_processed` на запрос.

  - `_page_number_processed` служит защитой от двойной и более обработки. Это критически важно для входящих запросов (за исходящими я такого не замечал, поэтому защиты нет), которые могут проходить повторную обработку сторонним кодом. Повторный вызов случается, например, в `Url::createFromRequest()`: его используют `drupal/token` и Metatag[^metatag], и он заново запускает входящие обработчики на том же запросе. Если на входе получили `?page=10`, для внутреннего потребления это `?page=9`, но повторный вызов сделал бы уже `?page=8`. Поэтому мы используем атрибут запроса как индикатор того, что смещение уже произошло, и пропускаем повторное смещение.

- `::processOutbound()` — обновляем номер страницы для ответа клиенту. Сначала проверяем `PaginationPathPolicy::isExcluded()`: если страница исключена — выходим. Иначе проверяем, задан ли `page` в опциях URL (`['query']['page']`). Если нет — ничего не делаем; если задан — обновляем опции: при значении 0 (первая страница для Drupal) убираем параметр совсем, иначе `/foo?page=0` станет чистым дублем `/foo`; при значении больше нуля — увеличиваем его на 1.

Регистрируем наш класс как сервис — и… хотелось бы сказать, что всё готово. Но, увы: проверив работу, вы заметите, что при прямых запросах всё работает корректно, а стоит кликнуть на ссылку в пагинаторе — и что-то не так. Смещение вроде есть и работает, но ссылка его не учитывает. В следующей секции разберёмся, почему так.

::: note [Переключатель языков]
На мультиязычном сайте с языковыми префиксами ссылки переключателя языков окажутся сдвинутыми: со страницы `/en/foo?page=2` ссылка на другой язык поведёт на `/es/foo?page=3`. Плагин `LanguageNegotiationUrl` (про [определение языка][language-negotiation] я писал отдельно) собирает их из `$request->getQueryString()`, то есть из исходной строки запроса, а не из изменённого нами `$request->query`, и исходящий обработчик прибавляет единицу повторно. Исправить это можно в `hook_language_switch_links_alter()`, вычтя единицу из `$links[$langcode]['query']['page']`.
:::

## Обработка исходящих URL в шаблонах пагинатора

Если вы вмешиваетесь в обработку путей и это должно учитываться в пагинаторе — например, [делаете красивые ЧПУ для фильтров](http://xandeadx.ru/blog/drupal/965) (`/category?price=123-321` → `/category/price-123-321`) или применяете смещение из предыдущей секции, — тут есть нюансы. В обоих случаях ссылки в пагинаторе не проходят через исходящие обработчики путей.

Проблема кроется в рендер-элементе `\Drupal\Core\Render\Element\Pager`, который отвечает за вывод пагинатора в Drupal. Его стандартная настройка в `::getInfo()` для `'#route_name'` имеет значение `<none>`. Это значение используется как относительный маршрут для построения ссылок пагинатора, и поведение зависит от того, как его использует тот или иной модуль, отвечающий за вывод пагинатора.

При стандартном значении `'<none>'` (у которого установлена опция `_no_path`) генератор URL вернёт `?page=N` — по сути, относительный путь в виде query-параметра для текущей страницы. Что куда важнее, `_no_path` служит триггером раннего выхода в `\Drupal\Core\Routing\UrlGenerator::generateFromRoute()`, а значит, результат вернётся до вызова `::processPath()` (ведь `$path` тут пустая строка) — того самого, который прогоняет путь через обработчики путей.

Это касается вообще всех исходящих обработчиков. Чтобы увидеть это на практике, возьмите случай с ЧПУ: на странице ЧПУ с пагинатором ссылки слетают — `/category?price=123-321&page=2` вместо `/category/price-123-321?page=2`. То есть проблема куда шире, но реализация нужна, только если вы уже столкнулись с ней. Делать её просто на всякий случай смысла нет.

И вот мы получаем проблему (при активном смещении): `/foo?page=2` в случае `'<none>'` превращается в `?page=1`, а во всех остальных случаях остаётся `/foo?page=2`. Поведение непоследовательное.

Первое, что приходит на ум: поменять значение по умолчанию для `'#route_name'` с `'<none>'` на `'<current>'`? Но не так всё просто: `'#route_name'`, будучи параметром рендер-элемента, можно передать при его вызове — и в ядре есть кейсы вроде `!empty($this->view->live_preview) ? '<current>' : '<none>'`, — так что наше значение по умолчанию уйдёт. Но даже это не главная проблема: пагинатор по сути является [тем-хуком][theme-hooks] `pager`, и сторонние разработчики могут выводить его напрямую, в обход рендер-элемента-обёртки. Пример опять под рукой — пагинатор для Views (`\Drupal\views\Plugin\views\pager\Full::render()`) рендерит пагинатор в обход рендер-элемента, собирая тем-хук самостоятельно.

Решение — [препроцессор][hooks] для тем-хуков `pager` и `views_mini_pager`: он даёт больше контроля (можно отключать поведение на определённых маршрутах и т.д.) и проверен годами на разных проектах. Поэтому подойдём с этой стороны и принудительно прогоним ссылки через `Url` для маршрутов со значением `'<none>'`.

::::: figure
::: figcaption
**Листинг 3** — Препроцессор для стандартного пагинатора (`pager`) и мини-пагинатора Views (`views_mini_pager`) — `src/Hook/PreprocessPager.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Url;

#[Hook('preprocess_pager')]
#[Hook('preprocess_views_mini_pager')]
final readonly class PreprocessPager {

  /**
   * @param array{pager?: array{'#route_name': string}, items?: mixed} $variables
   */
  public function __invoke(array &$variables): void {
    if (!isset($variables['items']) || !\is_array($variables['items'])) {
      return;
    }

    $variables['#cache']['tags'][] = 'pager:rendered';

    // The "pager" theme hook is defined with the "pager" render element,
    // which provides "#route_name". The Views-only "views_mini_pager" theme
    // hook has no such element and builds its links with
    // Url::fromRoute('<current>').
    // Only '<none>' pagers of the "pager" theme hook bypass path processors
    // (the '_no_path' route option). Other routes are already processed by the
    // URL generator, so handling them again would shift the page number twice.
    if (($variables['pager']['#route_name'] ?? NULL) !== '<none>') {
      return;
    }

    \array_walk_recursive($variables['items'], $this->processHref(...));
  }

  private function processHref(mixed &$value, string|int $key): void {
    if ($key !== 'href' || !\is_string($value)) {
      return;
    }

    $value = Url::fromRoute('<current>', [], [
      'query' => UrlHelper::parse($value)['query'],
    ])->toString();
  }

}
```
:::::

- `::__invoke()` — точка входа в наш хук. Делаем ранний выход, если `'items'` не установлен или не является массивом. У тем-хука `pager` `'items'` появляется только при пагинаторе с более чем одной страницей, у тем-хука `views_mini_pager` — даже при одной странице; проверка `is_array()` защищает от произвольных данных и сужает тип для PHPStan. Иначе добавляем к странице кеш-тег `pager:rendered`: он маркирует страницу, на которой отрендерен пагинатор, и пригодится в разделе про HTTP 404. Затем проверяем `#route_name`: он есть только у тем-хука `pager` (у `views_mini_pager` элемента `pager` нет, поэтому доступ идёт через `?? NULL`). Если маршрут не `'<none>'`, выходим; если `'<none>'` — рекурсивно обходим каждый элемент массива `'items'` с колбеком на `::processHref()`.

- `::processHref()` — обработчик для рекурсивного обхода массива. Нас интересуют только значения по ключу `'href'`, которые являются строкой. Если условия выполняются, прогоняем текущее значение `'href'` через `Url` с базовым маршрутом `<current>` — это спровоцирует вызов исходящих обработчиков. В итоге `?page=1` станет `/foo?page=2` в пагинаторе.

Эта обработка охватывает сразу все массивы внутри `'items'`, а их там прилично: `'first'`, `'previous'`, `'pages'`, `'next'` и `'last'`.

Но у этой реализации есть и обратная сторона. Если вы читали внимательно, то могли заметить `!empty($this->view->live_preview) ? '<current>' : '<none>'` и понять: `'<none>'` в ядре ставят осознанно. Там, где так сделано, мы теперь принудительно конвертируем ссылки и тем самым меняем поведение — что именно может сломаться, я не знаю, так что сюрпризы возможны. Впрочем, риск минимален: он касается только страниц с пагинатором, и его можно даже свести к минимуму. Самое простое решение — перенести реализацию хука из модуля в активную тему оформления: тогда на административных страницах он не будет вызываться и вмешиваться. Как и в случае с предыдущей секцией, я не хочу переусложнять пример ради edge-case сценариев.

## Токен URL с номером страницы для rel="canonical"

Обычно тег `rel="canonical"` заполняется с помощью [токена][token] `[current-page:url]`. Этот токен возвращает абсолютный URL текущей страницы, отбрасывая все query-параметры: `https://example.com/foo?page=3&bar=baz` → `https://example.com/foo`.

Однако некоторые SEO-специалисты считают, что, раз содержимое страниц отличается, каноничный URL для страниц с пагинацией должен включать номер страницы. То есть для страницы номер 3 canonical должен быть `https://example.com/foo?page=3` (при условии, что применено 1-based смещение), а не `https://example.com/foo` — даже если в исходном URL остались лишние параметры вроде `&bar=baz`. Важное уточнение: я не утверждаю, что так нужно делать, но часто слышу мнение, что это полезно.

Решим задачу просто: введём новый токен `[url:absolute-with-page]`. Он будет работать точно так же, как `[url:absolute]`, но с одним отличием — добавит query-параметр `page` из текущего запроса, не перенося в URL остальные его query-параметры.

::::: figure
::: figcaption
**Листинг 4** — Реализация токена `[url:absolute-with-page]` — `src/Hook/UrlWithPageToken.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Provides [url:absolute-with-page] token.
 */
final readonly class UrlWithPageToken {

  public function __construct(
    private PagerManagerInterface $pagerManager,
    private TranslationInterface $translation,
  ) {}

  /**
   * @return array<string, mixed>
   */
  #[Hook('token_info')]
  public function info(): array {
    return [
      'tokens' => [
        'url' => [
          'absolute-with-page' => [
            'name' => $this->translation->translate('Absolute URL with page query if present'),
          ],
        ],
      ],
    ];
  }

  /**
   * @param array<string, string> $tokens
   * @param array<string, mixed> $data
   * @param array<string, mixed> $options
   *
   * @return array<string, string>
   */
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    if ($type !== 'url' || !isset($tokens['absolute-with-page'])) {
      return [];
    }

    $url = $data['url'];
    \assert($url instanceof Url);
    $url = clone $url;

    $bubbleable_metadata->addCacheContexts(['url.query_args.pagers:0']);
    $current_page = $this->pagerManager->findPage();
    if ($current_page > 0) {
      $url->setOption('query', ['page' => $current_page] + (array) $url->getOption('query'));
    }

    return [
      $tokens['absolute-with-page'] => $url->setAbsolute()->toString(),
    ];
  }

}
```
:::::

Всё просто: берём текущий `Url` из данных и клонируем его, чтобы не поломать другие токены, которые зависят от этого экземпляра. Затем добавляем в query-опцию параметр `page` со значением из запроса — внутренним номером с нумерацией от нуля, — не удаляя уже имеющиеся параметры `Url`. Мы модифицируем `Url`, а не собираем строку конкатенацией, поэтому `::toString()` пропускает результат через все обработчики путей. Например, `PageNumberPathProcessor` превращает внутренний номер `1` в `?page=2`. Если позже вы захотите заменить `/foo?page=2` на `/foo/page-2`, достаточно поправить обработчик — токен менять не придётся.

Результат токена зависит от `?page=` в запросе, поэтому добавляем [кеш-контекст][cache-metadata] `url.query_args.pagers:0`. Контекст сужен до пагинатора с индексом 0, чтобы не плодить варианты кеша для остальных пагинаторов; `::findPage()` без аргументов как раз читает пагинатор с индексом 0.

### Почему номер страницы берётся из запроса, а не из пагинатора

Казалось бы, логичнее спросить у инициализированного пагинатора: `::getPager()?->getCurrentPage()`. У этого подхода два плюса. Пока пагинатор не создан, Pager Manager возвращает `NULL`, и это спасает от дублирующих «канонических» страниц: `/article/foo?page=123` на странице без пагинации получит значение `/article/foo`. А если пагинатор есть, но страница выходит за диапазон (`?page=123` при 100 страницах), пагинатор приведёт её к последней существующей.

Но у подхода есть условие: токен должен вычисляться уже после создания пагинатора. Когда `drupal/metatag` вычисляет токены, зависит от типа страницы:

- **Страницы, которые не выводят сущность через её view builder.** Это обычные контроллеры и Views (например, стандартная страница термина таксономии, где Views подменяет canonical-маршрут сущности). Токены вычисляются в `hook_page_attachments()`, после рендера основного содержимого, и пагинатор к этому моменту уже создан.

- **Страницы отображения сущности.** Токены вычисляются раньше, в `hook_entity_view_alter()` (`metatag_entity_view_alter()`), который вызывается из `#pre_render` строителя представления сущности (`EntityViewBuilder::build()`) в конце сборки. Токен увидит пагинатор, только если тот создан на этапе сборки: в форматтере поля (например, у комментариев), в extra field или в `hook_entity_view()`. Если пагинатор создаётся при рендере (препроцесс шаблона сущности, `#pre_render` дочернего элемента, представление, встроенное через `#type => 'view'`, `#lazy_builder`), `::getPager()` в токене вернёт `NULL`.

Какой из этих случаев сработает, зависит от того, как на проекте собираются страницы, — результат может отличаться даже на разных страницах одного проекта. Например, если листинг товаров на странице термина строится в препроцессе его шаблона, пагинатор создаётся при рендере, и `canonical` на всех страницах с пагинацией молча остаётся без `?page=`.

`::findPage()` читает номер страницы напрямую из запроса и от порядка вызовов не зависит, поэтому используем его. Платим за это двумя вещами. Во-первых, токен больше не знает, существует ли страница на самом деле: для `?page=5` на странице без пагинатора он вернёт URL с `?page=5`. Во-вторых, страница вне диапазона не приводится к последней. Защита от дублей переезжает в 404-обработчики из раздела «HTTP 404 для несуществующих страниц»: невалидный номер, номер вне диапазона и `?page=` на странице без пагинатора отдают 404, и ни такой URL, ни его canonical не попадают в индекс. Поэтому подход безопасен, пока эти обработчики работают. Если вы их не используете, обратитесь к альтернативному варианту в следующей секции.

### Альтернатива: подменять canonical после сборки страницы

Если не хочется читать номер страницы из запроса и полагаться на 404-обработчики, поправьте готовый canonical в `hook_page_attachments_alter()`. Он вызывается после рендера основного содержимого, когда пагинатор уже создан — в препроцессе шаблона, в `#pre_render`, во встроенном представлении, — поэтому `::getPager()` вернёт настоящее значение. Исключение — пагинатор в `#lazy_builder`: он создаётся ещё позже (подробнее в конце подраздела). Metatag к этому моменту разместил свои теги в `$attachments['#attached']['html_head']`, а canonical лежит там под ключом `canonical_url`.

::::: figure
::: figcaption
**Листинг 5** — Подмена canonical после сборки страницы
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds ?page=N to canonical URL of paginated pages.
 */
#[Hook('page_attachments_alter')]
final readonly class CanonicalWithPage {

  public function __construct(
    private PagerManagerInterface $pagerManager,
    private RequestStack $requestStack,
  ) {}

  /**
   * @param array{'#cache'?: array{contexts?: list<string>}, '#attached'?: array{html_head?: array<int, array{0: array{'#attributes': array<string, string>}, 1: string}>}} $attachments
   */
  public function __invoke(array &$attachments): void {
    $request = $this->requestStack->getCurrentRequest();
    if ($request !== $this->requestStack->getMainRequest()) {
      return;
    }

    $pager = $this->pagerManager->getPager();
    if ($pager === NULL) {
      return;
    }

    $attachments['#cache']['contexts'][] = 'url.query_args.pagers:0';
    if ($pager->getCurrentPage() < 1) {
      return;
    }

    $href = Url::fromRoute('<current>', [], [
      'absolute' => TRUE,
      'query' => ['page' => $pager->getCurrentPage()],
    ])->toString();

    $attachments['#attached']['html_head'] = $this->replaceCanonical($attachments['#attached']['html_head'] ?? [], $href);
  }

  /**
   * @param array<int, array{0: array{'#attributes': array<string, string>}, 1: string}> $html_head
   *
   * @return array<int, array{0: array{'#attributes': array<string, string>}, 1: string}>
   */
  private function replaceCanonical(array $html_head, string $href): array {
    foreach ($html_head as $key => [$element, $name]) {
      if ($name !== 'canonical_url') {
        continue;
      }

      $element['#attributes']['href'] = $href;
      $html_head[$key][0] = $element;
    }

    return $html_head;
  }

}
```
:::::

Что делает хук:

- Работает только в главном запросе. Страница 404 — это подзапрос, который наследует состояние Pager Manager от неудавшегося главного запроса; без этой проверки canonical 404-страницы получил бы номер последней существующей страницы.

- Если пагинатора нет или открыта первая страница, canonical не меняется.

- Собирает адрес из текущего маршрута с единственным параметром `page` — адрес проходит через обработчики путей, поэтому 1-based смещение применяется само, а остальные query-параметры отбрасываются.

- Добавляет кеш-контекст `url.query_args.pagers:0`, но только когда пагинатор существует.

Цена у подхода такая:

- Подменяется уже готовая строка: если шаблон canonical в Metatag (в настройках по умолчанию или в поле конкретного материала) намеренно отличается от URL страницы, на страницах с пагинацией этот шаблон теряется.

- Хук зависит от внутреннего ключа Metatag `canonical_url` — а это не публичный контракт.

- Пагинатор в `#lazy_builder` хук не увидит: плейсхолдеры заменяются уже после `hook_page_attachments()`. Токен с `::findPage()` справляется и с этим случаем.

Поэтому в статье основной вариант — токен, а хук — запасной, на случай, если 404-обработчиков у вас нет.

***

Дальше используем новый токен — вероятнее всего, это будет `[current-page:url:absolute-with-page]`. Суффикс `:absolute-with-page` будет доступен и для других `url`-токенов: `[node:url:absolute-with-page]`, `[term:url:absolute-with-page]` и т. д.

Не добавляйте его в глобальную конфигурацию Metatag — подставляйте точечно, в шаблонах тех типов страниц, где ожидается пагинатор (например, у термина с листингом или у страницы списка). Токен читает `?page=` из запроса и не проверяет, существует ли пагинатор. Поэтому в глобальном шаблоне на странице без пагинатора, но с `?page=` в запросе, он добавил бы номер в canonical — и риск утечки номера страницы растёт.

## Убираем дубли первой страницы пагинации через 301-редирект

По умолчанию страницы `/foo` и `/foo?page=0` — прямые дубли: обе показывают содержимое первой страницы. Если вы реализовали 1-based смещение, дублей станет три: `/foo`, `/foo?page=0` и `/foo?page=1`. Canonical при этом остаётся `/foo` (с дефолтным значением `[current-page:url]`) для всех трёх вариантов: с 1-based смещением `?page=0` и `?page=1` дают внутренний `0`, а токен не добавляет `page`. Но дубли всё равно остаются дублями: они тратят краулинговый бюджет, поэтому 301 не повредит.

Есть и ещё один повод исправить поведение: такие страницы засоряют кеш. Drupal считает их валидными — отвечает и кеширует, — а кеш-контексты `url.query_args.pagers` и `url.query_args` создают для каждой отдельный вариант кеша, хотя, как уже говорилось, по содержанию они абсолютно идентичны.

Решение простое: подписаться на [событие][events] `KernelEvents::REQUEST`, определить дублирующие страницы и сделать HTTP 301-редирект на основную. Так останется одна-единственная копия первой страницы — без мусора в кеше и странного поведения.

::::: figure
::: figcaption
**Листинг 6** — Подписчик с редиректом для дублей первой страницы — `src/Pager/FirstPageRedirect.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Pager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class FirstPageRedirect implements EventSubscriberInterface {

  public function pageRedirect(RequestEvent $event): void {
    $request = $event->getRequest();
    if (!$event->isMainRequest() || PaginationPathPolicy::isExcluded($request->getPathInfo())) {
      return;
    }

    $query = $request->query->all();
    if (!\in_array($query['page'] ?? NULL, ['0', '1'], strict: TRUE)) {
      return;
    }

    $query = \array_diff_key($query, ['page' => TRUE]);
    $url = $request->getBaseUrl() . $request->getPathInfo();
    if ($query !== []) {
      $url .= '?' . \http_build_query($query, encoding_type: \PHP_QUERY_RFC3986);
    }
    $event->setResponse(new RedirectResponse($url, 301));
  }

  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => [
        // Higher priority than RedirectRequestSubscriber (33) to skip
        // unnecessary redirect processing on duplicate pager URLs.
        ['pageRedirect', 40],
      ],
    ];
  }

}
```
:::::

- `::pageRedirect()` — основной метод с логикой:

  - Делаем ранний выход, если это не основной запрос или `PaginationPathPolicy::isExcluded()` вернул `TRUE` (из раздела «Где улучшения не применяются»).

  - Если страница подходящая, проверяем значение query-параметра `page` на вхождение в список страниц для редиректа (подписчик срабатывает раньше маршрутизатора, поэтому входящие обработчики путей ещё не отработали и в `page` лежит исходное значение пользователя, то есть в 1-based нумерации). Если значение — не `'0'` и не `'1'`, выходим. Если вы не делали 1-based смещение, замените список страниц на `['0']`: без смещения `'1'` отвечает за вторую страницу. Pager Manager мы не используем, потому что `::findPage()` возвращает `0` и для `/foo`, и для `/foo?page=0` — мы не отличим их друг от друга и получим бесконечный редирект. Важно именно наличие query-параметра `page` с определённым значением, а не номер страницы.

- `::getSubscribedEvents()` — регистрируем наш подписчик. Приоритет должен быть строго больше 33 — столько у `RedirectRequestSubscriber` из `drupal/redirect`. Тогда наш редирект сработает раньше, чем `drupal/redirect` начнёт делать запросы к базе данных, и мы сэкономим на бесполезных вычислениях.

::: note
Если вы реализовали и препроцессор для шаблона пагинатора из раздела «Обработка исходящих URL в шаблонах пагинатора», и очистку нулевого `page` через `unset($options['query']['page']);` в исходящем обработчике (в примере — из раздела «Конвертация нумерации: 0-based ↔ 1-based»), дополнительно ничего править не нужно — достаточно уже реализованного подписчика на `KernelEvents::REQUEST`. Если чего-то из этого нет, вернитесь в соответствующий раздел: без препроцессора ссылки пагинатора не проходят через исходящие обработчики, а без `unset` первая страница останется ссылкой на `?page=0`, ведущей на HTTP 301.

В обоих случаях нужны два элемента: препроцессор и исходящий обработчик с очисткой `page=0`. Разница только в обработчике: при 1-based смещении он ещё прибавляет 1 для `page > 0`, а без смещения достаточно только `unset` для нулевой страницы.
:::

## HTTP 404 для несуществующих страниц

Другая проблема пагинации Drupal — возможность ввести произвольное значение страницы и получить валидный ответ. Например, `?page=999` (когда у вас нет и десяти страниц), отрицательные значения вроде `?page=-100` или произвольные строки и числа: `abc`, `+5`, `2abc`, `1.5`, `1e3`, `05`, ` 5` (пробел перед цифрой). Единственный случай, на который Drupal отвечает HTTP 400, — `?page[]=X`, и то лишь когда код страницы реально читает pager-параметр; остальные такие запросы он принимает. Поведение при этом разнится: где-то вы получите реальную страницу, но без результатов, где-то — последнюю или первую, а в самом неудачном варианте, где нет должной проверки входных данных, — HTTP 500 из-за фатальной ошибки. Итог непредсказуем и зависит от того, как контроллер конкретной страницы обрабатывает эти данные.

Отвечать на такие «битые» запросы чем-то более внятным, чем HTTP 200 или тем более HTTP 500, вполне разумно. В предыдущем разделе мы занимались склейкой дублей через HTTP 301 — перенаправляли на первую страницу. Здесь поступим иначе — ответим HTTP 404: таких страниц по факту не существует, и мы прямо об этом говорим, не пытаясь «сгладить углы», как в ситуации с двойственностью первой страницы. Там мы перенаправляли на единственно верный вариант, а тут запрос некорректен — страницы нет. Есть и дополнительная выгода: `?page=N` — один из типовых способов загрязнения кеша, такие URL часто перебирают поисковые роботы и боты, и каждый ответ HTTP 200 оставляет в кеше мусор. 404 закрывает и эту лазейку.

Проблем здесь три. «Битые» значения (`abc`, `-1`) можно отсечь сразу. Выход за пределы диапазона можно определить, только когда известно количество страниц. И наконец, страница без пагинации вообще не должна принимать `page`: `/contact?page=2` или `/?page=2` отдают HTTP 200. Поэтому потребуются подписчик на события `KernelEvents::REQUEST`, `KernelEvents::RESPONSE` и декоратор Pager Manager.

::::: figure
::: figcaption
**Листинг 7** — Подписчик для невалидных и несуществующих страниц — `src/Pager/InvalidPageNotFound.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Pager;

use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class InvalidPageNotFound implements EventSubscriberInterface {

  public function __construct(
    private PagerManagerInterface $pagerManager,
  ) {}

  public function onRequest(RequestEvent $event): void {
    $request = $event->getRequest();
    if (!$event->isMainRequest() || PaginationPathPolicy::isExcluded($request->getPathInfo())) {
      return;
    }

    $page = $request->query->all()['page'] ?? '0';
    if (!\is_string($page) || !\preg_match('/^(?:0|[1-9]\d{0,8})\z/', $page)) {
      $request->query->remove('page');
      throw new NotFoundHttpException();
    }
  }

  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();
    if (!$event->isMainRequest() || !$response instanceof HtmlResponse || $response->getStatusCode() !== 200) {
      return;
    }

    if (PaginationPathPolicy::isExcluded($event->getRequest()->getPathInfo())) {
      return;
    }

    if ($this->pagerManager->findPage() === 0) {
      return;
    }

    if (\in_array('pager:rendered', $response->getCacheableMetadata()->getCacheTags(), strict: TRUE)) {
      return;
    }

    throw new NotFoundHttpException();
  }

  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest', 39],
      KernelEvents::RESPONSE => ['onResponse', 100],
    ];
  }

}
```
:::::

Первый обработчик, `::onRequest()`, подписан на `KernelEvents::REQUEST`. На этом событии уже доступно значение query-параметра `page`, по которому можно на ранних этапах заметить невалидные значения и выбросить HTTP 404. Это позволяет избежать лишних запросов к БД от `drupal/redirect`, а также рендера основного содержимого со всеми вытекающими запросами и сборкой страницы. Чем раньше выбросим — тем лучше.

Логика `::onRequest()` простая:

- Проверяем, что это основной запрос и что страница не исключена (`PaginationPathPolicy::isExcluded()` из раздела «Где улучшения не применяются»).

- Далее получаем query-параметр «как есть»; если его нет, считаем значением `'0'`. Явные `'0'` и `'1'` обработает `FirstPageRedirect`; значение здесь тоже исходное: подписчик срабатывает раньше маршрутизатора, поэтому `PageNumberPathProcessor` ещё не отработал.

- Затем проверяем, нужно ли бросать HTTP 404; для этого у нас две проверки:

  1. `!\is_string($page)` — отбрасываем всё, что не является строкой. Это защита от массивов вроде `?page[]=1`.

  2. `!\preg_match('/^(?:0|[1-9]\d{0,8})\z/', $page)` — проверяем, что `$page` — целое число без знака и пробелов, без ведущих нулей, длиной не более 9 цифр[^php-int-max]. Встроенные функции для этого не подходят. `is_numeric()` пропустит `1.5`, `1e3`, `+5` и `02`, а `is_int()` на строке из query всегда вернёт `FALSE`. `filter_var($page, FILTER_VALIDATE_INT)` ближе всего, но тоже не подходит: он отвергает дроби, экспоненту и ведущие нули, зато принимает пробелы по краям и знак, поэтому `?page=+5` (в query `+` превращается в пробел) и `?page=-5` для него валидны. Длину числа он не ограничивает, подойдёт любое значение до `PHP_INT_MAX`. Закрыть это дополнительными опциями и проверками можно, но выйдет длиннее и запутаннее регулярки.

- Если хотя бы одна проверка обнаружила проблему, мы удаляем битое значение из запроса и бросаем исключение — это приведёт к HTTP 404. Удаление битого значения здесь полезно для подзапросов, которые Drupal выполняет при 404. Например, если так не сделать, `?page[]=1` уйдёт в подзапрос, и любой код страницы 404, который читает `page` (например, токен `paged-text` в `<title>`), приведёт к HTTP 400: Symfony не позволяет прочитать массив как строку.

Приоритет подписчика должен быть ниже, чем у `\Drupal\example\Pager\FirstPageRedirect`: тот обработает «первые страницы» быстрее и надёжнее. При этом приоритет должен оставаться больше 33, чтобы не конфликтовать с `drupal/redirect`.

Второй обработчик, `::onResponse()`, отвечает за страницы без пагинации. Опираться на состояние пагинатора здесь нельзя: при ответе из Dynamic Page Cache он не создаётся, и «страница без пагинатора» неотличима от «страницы из кеша». Важно, что `::findPage()` — это не состояние пагинатора: он читает `page` из текущего запроса (входящий обработчик уже сделал значение 0-based), поэтому работает и для кешированного ответа. Недоступен только `::getPager()`. Зато кеш помнит, из чего состоит страница: её метаданные хранятся вместе с ответом и доступны и для свежего ответа, и для ответа из кеша. Остаётся пометить страницы с пагинатором. Для этого препроцессоры тем-хуков `pager` и `views_mini_pager` из листинга 3 добавляют кеш-тег `pager:rendered`. Тег никогда не сбрасывается и служит только маркером.

- Прекращаем обработку, если: запрос не основной; ответ не `HtmlResponse` или его код не HTTP 200 (редиректы, JSON и AJAX-ответы не затрагиваются); путь подпадает под наши исключения (из раздела «Где улучшения не применяются»); запрошена первая страница. Исключения проверяем до `::findPage()`: на исключённых путях `page` не санитизируется, а `::findPage()` на массиве приведёт к HTTP 400.

- Ищем у ответа тег `pager:rendered`. Если нашли — страница пагинируется, выходим. Готовые кеш-контексты для этой цели не годятся. Автоматически `url.query_args.pagers:N` добавляет только рендер-элемент `pager`, а Views выводит тем-хук напрямую, минуя элемент. Вручную его добавляют и другие места — например, токен canonical из этой статьи, причём независимо от наличия пагинатора. К тому же ядро при вычислении ключа кеша отбрасывает `url.query_args.pagers:N` как избыточный, если есть общий `url.query_args`; общий контекст добавляет ядерный препроцессор тем-хука (`PagerPreprocess` для тем-хука `pager` и `\Drupal\views\Hook\ViewsThemeHooks::preprocessViewsMiniPager()` для тем-хука `views_mini_pager`), а не листинг 3. Сам же `url.query_args` есть у любой страницы, которая зависит от параметров запроса (фильтры, форма), и о наличии пагинатора не говорит.

- Иначе бросаем исключение: страница не пагинируется, а `page` в запросе есть.

Приоритет подписчика на ответ должен быть больше 7 — такой приоритет у `DynamicPageCacheSubscriber`. Если приоритет нашего подписчика будет таким же или ниже, то ответ HTTP 200 успеет сохраниться в Dynamic Page Cache до того, как мы бросим исключение. Клиент всё равно получит HTTP 404: на хите Dynamic Page Cache подписчик срабатывает снова, но на каждый `?page=N` в кеше останется бесполезная запись. (Хит Internal Page Cache вообще не доходит до ядра, но 200-запись для невалидного `?page=N` туда не попадает: первый ответ — уже 404.) Принцип тут такой же, как у входящего запроса: чем раньше мы прервём дальнейшее выполнение, тем лучше. Приоритет 100 покроет больше подобных пограничных случаев «с запасом».

А вот с потенциально валидными значениями, например `?page=999` при десяти страницах, всё сложнее. На момент запроса контроллер ещё не отработал, пагинатор не создан, запросы на подсчёт страниц не выполнены — и мы не знаем, сколько всего страниц: десять или тысяча. Это становится известно только при создании пагинатора, в `PagerManagerInterface::createPager()`.

Напрашивается проверка по состоянию пагинатора на `KernelEvents::RESPONSE`, когда он уже создан. Но она ненадёжна. Если основное содержимое отдаётся из кеша (render cache, Dynamic Page Cache), контроллер не выполняется, пагинатор не создаётся, `::getPager()` возвращает `NULL` — и проверять нечего. Причём страница `?page=999` успевает попасть в render cache во время первого запроса, поэтому на повторном (например, у авторизованного пользователя) вернётся HTTP 200. Проверять нужно в момент создания пагинатора, пока ничего не ушло в кеш: исключение прервёт сборку страницы, и в кеш не попадёт ни пагинатор, ни содержимое.

Для этого декорируем сервис `pager.manager`: декоратор бросает исключение прямо в `::createPager()`.

::::: figure
::: figcaption
**Листинг 8** — Декоратор Pager Manager с проверкой диапазона — `src/Pager/RangeCheckedPagerManager.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Pager;

use Drupal\Core\Pager\Pager;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsDecorator(decorates: 'pager.manager')]
final readonly class RangeCheckedPagerManager implements PagerManagerInterface {

  public function __construct(
    #[AutowireDecorated]
    private PagerManagerInterface $inner,
    private RequestStack $requestStack,
  ) {}

  public function createPager($total, $limit, $element = 0): Pager {
    $pager = $this->inner->createPager($total, $limit, $element);

    // The error page is a subrequest that inherits the query, so only the main
    // request is checked, otherwise the 404 page would throw 404 itself.
    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL || $request !== $this->requestStack->getMainRequest() || PaginationPathPolicy::isExcluded($request->getPathInfo())) {
      return $pager;
    }

    // The pager clamps the current page, so compare the requested one.
    $page = $this->inner->findPage($element);
    if ($page > 0 && $page >= $pager->getTotalPages()) {
      throw new NotFoundHttpException();
    }

    return $pager;
  }

  public function getPager($element = 0): ?Pager {
    return $this->inner->getPager($element);
  }

  public function findPage(int $pager_id = 0): int {
    return $this->inner->findPage($pager_id);
  }

  /**
   * @param array<string, mixed> $query
   *
   * @return array<string, mixed>
   */
  public function getUpdatedParameters(array $query, $element, $index): array {
    return $this->inner->getUpdatedParameters($query, $element, $index);
  }

  public function getMaxPagerElementId(): int {
    return $this->inner->getMaxPagerElementId();
  }

  public function reservePagerElementId(int $element): void {
    $this->inner->reservePagerElementId($element);
  }

}
```
:::::

Вот как это работает:

- `::__construct()` — получаем оригинальный Pager Manager и `RequestStack`.

- `::createPager()` — сначала создаём пагинатор оригинальным сервисом, затем проверяем диапазон:

  - Выходим, если текущего запроса нет (`::getCurrentRequest()` возвращает `NULL` вне запроса, например в консоли), он не основной или путь исключён (`PaginationPathPolicy::isExcluded()` из раздела «Где улучшения не применяются»). Страница ошибки строится подзапросом, который наследует query исходного запроса. Без проверки на основной запрос другие списки с пагинатором на странице 404 (например, в сайдбаре) сами бросали бы исключение.

  - Сравниваем запрошенную страницу с общим количеством страниц. Берём именно запрошенную (`::findPage()` оригинального сервиса), а не `$pager->getCurrentPage()`: пагинатор приводит текущую страницу к последней существующей, и сравнение никогда бы не сработало. К моменту `::createPager()` входящий `PageNumberPathProcessor` уже перевёл `page` в 0-based, поэтому `::findPage()` возвращает внутренний номер; отдельно пересчитывать 1-based не нужно.

  - Первую страницу (0) не проверяем: у пустой выборки страниц ноль, но список без результатов не должен отвечать HTTP 404.

Остальные методы контракта просто делегируют вызов оригинальному сервису.

Ответ HTTP 404 при этом кешируется штатно: Drupal помечает его тегом `4xx-response` (с ним же работает Page Cache для анонимов). Ядро сбрасывает этот тег при сохранении любой сущности с canonical-ссылкой, поэтому как только появляются материалы, которые делают страницу валидной, закешированный 404 пропадает сам. Для списков, не основанных на сущностях, об инвалидации придётся позаботиться самостоятельно.

Регистрируем подписчик и декоратор как сервисы — и всё готово: невалидные значения параметра `page`, страницы за пределами допустимого диапазона и страницы без пагинации приведут к HTTP 404.

### Ограничения проверок

Страница с пагинатором определяется по тегу `pager:rendered`, который добавляют препроцессоры тем-хуков `pager` и `views_mini_pager` из листинга 3. Пагинатор, который выводится в обход этих тем-хуков, тега не получит, и валидная страница ответит ложным HTTP 404 — добавьте тег самостоятельно.

::: note [Мини-пагинатор Views]
Если вы используете мини-пагинатор Views (`views_mini_pager`), он уже строит ссылки через `<current>`, поэтому смещение для него работает. Листинг 3 регистрирует препроцессор и для `preprocess_views_mini_pager`. Если оставить только `preprocess_pager`, валидные страницы с `views_mini_pager` будут получать ложный HTTP 404.
:::

Ложный HTTP 404 возможен и с пагинатором, который создаётся внутри плейсхолдера: это `#lazy_builder` с `'#create_placeholder' => TRUE`, плейсхолдер BigPipe или автоплейсхолдер у элемента с плохой кешируемостью. Если плейсхолдер не создаётся, ядро выполняет `#lazy_builder` сразу — и проблемы нет.

Проверка выполняется до замены плейсхолдеров, а метаданные, объявленные на самом плейсхолдере, в ответ не поднимаются. Валидная страница получает ложный HTTP 404.

Решение — добавить тег в `#cache` родительского render-массива: `'#cache' => ['tags' => ['pager:rendered']]`. Результат зависит от [стратегии плейсхолдеров][placeholder-strategy]. Если плейсхолдер заменяется в основном потоке до отправки ответа (стандартная стратегия), исключение из `::createPager()` ещё может изменить ответ, и `?page=999` получит HTTP 404. Если замена происходит после того, как код ответа уже определён (потоковая отправка BigPipe для авторизованных пользователей, собственная стратегия с AJAX-подгрузкой), проверка диапазона для такого пагинатора не работает, и `?page=999` ответит HTTP 200 с пустым списком.

## Суффикс «— страница №X» в заголовках страниц

Если вы сделаете страницы с пагинацией каноническими (`rel="canonical"` с `?page`), вам наверняка захочется убедиться, что H1-заголовки не дублируют заголовок основной страницы. Самый простой способ решить эту задачу — добавить суффикс «— страница №X».

Опять же, хочу предупредить: на этот счёт я слышал много разных мнений, поэтому отнеситесь к этой правке с долей скепсиса. Лично я считаю: если мы помечаем `?page=X` как канонические страницы, чтобы поисковые системы трактовали их как самостоятельные, логично, что у них должны отличаться и заголовки H1. Но это лишь моё субъективное мнение.

Я решаю эту задачу так: декорирую [сервис][services] `title_resolver` и ввожу для маршрутов новый атрибут `_title_page_suffix` — индикатор того, на каких маршрутах нужен суффикс. Далеко не везде и не всегда он уместен, а применять его без разбора может быть вреднее, чем вовсе не добавлять.

::::: figure
::: figcaption
**Листинг 9** — Декоратор для резолвера заголовков — `src/Pager/PageSuffixTitleResolver.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Pager;

use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

#[AsDecorator(decorates: 'title_resolver')]
final readonly class PageSuffixTitleResolver implements TitleResolverInterface {

  public function __construct(
    #[AutowireDecorated]
    private TitleResolverInterface $inner,
    private PagerManagerInterface $pagerManager,
    private RendererInterface $renderer,
    private TranslationInterface $translation,
    private RequestStack $requestStack,
  ) {}

  /**
   * @return string|array<mixed>|\Stringable|null
   */
  public function getTitle(Request $request, Route $route): string|array|\Stringable|NULL {
    $title = $this->inner->getTitle($request, $route);

    if (!$route->getDefault('_title_page_suffix') || !$title) {
      return $title;
    }

    if ($request !== $this->requestStack->getCurrentRequest()) {
      return $title;
    }

    return $this->addSuffix($title);
  }

  /**
   * @return string|array<mixed>|\Stringable
   */
  public function addSuffix(mixed $title): string|array|\Stringable {
    if (!\is_string($title) && !\is_array($title) && !$title instanceof \Stringable) {
      return '';
    }

    $page_number = $this->pagerManager->findPage();
    if ($page_number < 1) {
      return $title;
    }

    if (Element::isRenderArray($title)) {
      \assert(\is_array($title));
      $title = $this->renderer->renderInIsolation($title);
    }

    if (!\is_string($title) && !$title instanceof \Stringable) {
      return $title;
    }

    return $this->translation->translate(
      string: '@title — page #@number',
      args: ['@title' => $title, '@number' => $page_number + 1],
    );
  }

}
```
:::::

В декораторе реализуем метод контракта — `\Drupal\Core\Controller\TitleResolverInterface::getTitle()` — и выносим общую логику суффикса в публичный `::addSuffix()`, чтобы её мог использовать препроцессор:

1. Получаем актуальный заголовок из декорируемого сервиса.
2. Если у маршрута нет значения по умолчанию `_title_page_suffix` или `$title` не проходит проверку на истинность (`NULL`, пустая строка) — отдаём оригинал как есть.
3. Если переданный запрос — не тот же объект, что текущий запрос из `RequestStack`, — отдаём оригинал как есть (подробности в примечании ниже).
4. Получаем текущий номер страницы через Pager Manager. Если номер меньше 1 — отдаём оригинал как есть. Нумерация здесь внутренняя, 0-based: 0 — первая страница, 1 — вторая и т. д., независимо от реализации 1-based смещения.
5. Проверяем, является ли `$title` [рендер-массивом][render-arrays]; если да — рендерим его и перезаписываем `$title`.
6. Если `$title` не является ни строкой (сюда всё ещё может попасть массив, не являющийся рендер-массивом), ни объектом `\Stringable` (после рендера рендер-массива это будет `Markup`; также сюда может попасть `TranslatableMarkup`, `PluralTranslatableMarkup` и т. д.) — отдаём оригинал как есть.
7. Если ни одно из условий не привело к возврату оригинала, возвращаем новый заголовок по шаблону `'@title — page #@number'`: передаём оригинальный заголовок и текущий номер страницы плюс один.

::: note [Почему сравниваем запросы]
`PagerManager::findPage()` читает `page` из текущего запроса и ничего не знает о `$request`, переданном в `::getTitle()`. А заголовки резолвятся не только для текущей страницы: хлебные крошки (`\Drupal\system\PathBasedBreadcrumbBuilder`) получают заголовки родительских маршрутов через синтетические подзапросы.

Возьмём статью `/blog/foo`, у которой комментарии открыты на второй странице (`?page=2`). У маршрута `/blog` (родитель в хлебных крошках) атрибут `_title_page_suffix` есть, а `::findPage()` вернёт `1` — номер страницы пагинатора комментариев. В итоге крошка превратится в «Публикации в блоге — страница №2», хотя к списку статей это не относится. Проверка на `$route->getDefault()` тут не спасает: она отвечает на вопрос «нужен ли этому маршруту суффикс», но не «чья это страница».

Такие подзапросы создаются вручную (`Request::create()`) и в `RequestStack` не попадают, поэтому достаточно сравнить объекты запросов: для самой текущей страницы в `::getTitle()` приходит тот же `$request`, что лежит в стеке. Суффикс добавляется, только когда заголовок резолвится для текущей страницы.
:::

Теперь используем новый атрибут на нужных маршрутах:

::::: figure
::: figcaption
**Листинг 10** — Пример использования атрибута `_title_page_suffix` на собственных маршрутах
:::
```yaml
example.foo:
  path: '/foo'
  defaults:
    _controller: 'Drupal\example\Controller\FooController'
    _title: 'Articles'
    _title_page_suffix: TRUE
  requirements:
    _permission: 'access content'
```

```php
#[Route(
  path: '/foo',
  name: 'example.foo',
  defaults: [
    '_title' => 'Articles',
    '_title_page_suffix' => TRUE,
  ],
  requirements: ['_permission' => 'access content'],
)]
```
:::::

Для сторонних маршрутов придётся подписаться на событие `RoutingEvents::ALTER` ([Route Subscriber][route-subscriber]):

::::: figure
::: figcaption
**Листинг 11** — Пример добавления атрибута `_title_page_suffix` на сторонние маршруты — `src/Pager/ArticlesTitlePageSuffixRouteSubscriber.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Pager;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

final class ArticlesTitlePageSuffixRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection): void {
    $collection
      ->get('view.featured_articles.page_1')
      ?->setDefault('_title_page_suffix', TRUE);
  }

}
```
:::::

Не забываем зарегистрировать его как сервис — и почти всё готово. Почти всё, потому что для маршрутов Views, контроллеров и некоторых сторонних модулей может потребоваться дополнительный препроцессор. Представление или контроллер может подставить свой заголовок через `#title`, из-за чего наш декоратор не сработает при рендере основного содержимого: при наличии `#title` вызов резолвера заголовка пропускается. Если `#title` нет, декоратор уже добавил суффикс, и препроцессор не нужен. В случае с `#title` добавляем суффикс препроцессором:

::::: figure
::: figcaption
**Листинг 12** — Препроцессор для добавления суффикса в заголовок страницы — `src/Hook/PreprocessPageTitleSuffix.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\example\Pager\PageSuffixTitleResolver;
use Symfony\Component\HttpFoundation\RequestStack;

#[Hook('preprocess_page_title')]
final readonly class PreprocessPageTitleSuffix {

  public function __construct(
    private PageSuffixTitleResolver $titleResolver,
    private RequestStack $requestStack,
    private RouteMatchInterface $routeMatch,
  ) {}

  /**
   * @param array{title?: mixed} $variables
   */
  public function __invoke(array &$variables): void {
    $request = $this->requestStack->getCurrentRequest();
    $route = $this->routeMatch->getRouteObject();
    if (!$request || !$route?->getDefault('_title_page_suffix')) {
      return;
    }

    $title = $variables['title'] ?? NULL;
    if (!$title || (!\is_string($title) && !\is_array($title) && !$title instanceof \Stringable)) {
      return;
    }

    $resolved_title = $this->titleResolver->getTitle($request, $route);
    $is_same_title = $title === $resolved_title;
    $is_same_string_title = !\is_array($title)
      && !\is_array($resolved_title)
      && (string) $title === (string) $resolved_title;

    if ($is_same_title || $is_same_string_title) {
      return;
    }

    $variables['title'] = $this->titleResolver->addSuffix($title);
  }

}
```
:::::

Препроцессор вызывается при выводе заголовка на любой странице, поэтому сначала он отсекает лишнее и выходит, если:

- запроса нет или у текущего маршрута нет атрибута `_title_page_suffix`;
- `title` пустой или не является ни строкой, ни массивом, ни `\Stringable`.

Запрос присутствует почти всегда — разве что его не будет при рендере через CLI; проверка скорее нужна для сужения типа в PHPStan, чтобы `NULL` реально не прошёл.

Дальше препроцессору нужно понять, откуда взялся заголовок. Обычно его даёт резолвер, и тогда суффикс уже добавлен нашим декоратором: `title` совпадает с результатом `::getTitle()`, и препроцессор ничего не меняет. Если `title` отличается, значит, заголовок задан через `#title` контроллера в обход резолвера, и суффикса в нём нет. Такой заголовок передаём в `PageSuffixTitleResolver::addSuffix()`: он сохранит кастомный текст и добавит к нему суффикс. На первой странице `::addSuffix()` возвращает заголовок без изменений.

Проверки две: `$is_same_title` ловит точное совпадение (включая одинаковые render-массивы), `$is_same_string_title` — случай, когда одно значение строка, а другое `Markup`/`Stringable` с тем же текстом.

Резолвер, как и токен `[url:absolute-with-page]`, берёт номер страницы через `::findPage()`, а не `::getPager()`: он читает его из запроса, поэтому не зависит от того, создан ли уже пагинатор. Подробнее об этом — в подразделе «Почему номер страницы берётся из запроса, а не из пагинатора».

## Токен с суффиксом страницы

Вне зависимости от того, сделали вы страницы с пагинацией каноническими или добавили суффикс к H1, не менее важно позаботиться и о мета-тегах — добавить аналогичный суффикс в `<meta name="description">` и `<title>`. Иначе легко получить предупреждения о дублях заголовков и описаний: поисковики не любят, когда у нескольких страниц полностью совпадают эти теги (речь именно о `<title>`, а не о H1).

Хоть это и пересекается с секцией выше, это совершенно другая категория проблем. Я не припомню, чтобы инструменты вебмастеров выдавали предупреждения о дублирующихся H1, но Яндекс.Вебмастер всегда ругается на дублирующиеся `<title>` и `<meta name="description">`. Поэтому эту правку имеет смысл рассматривать независимо от того, применили ли вы аналогичный подход для H1 или нет.

::: note
Суффикс уже будет присутствовать в значении `[current-page:title]`, если вы реализовали декоратор из предыдущей секции.
:::

Решение здесь то же, что и раньше: добавляем суффикс (если текущая страница — не первая) и оформляем его в виде токена. Правда, модуль `drupal/token` уже добавляет похожий токен `[current-page:page-number]` — он выводит текущий семантический номер страницы. Если вас устраивает такой токен — всё хорошо. Наш же позволит добавить произвольный текст с плейсхолдером `@page` и, в отличие от него, не будет выводить ничего на первой странице.

::::: figure
::: figcaption
**Листинг 13** — Реализация токена `[current-page:paged-text:?]` — `src/Hook/PagedTextToken.php`
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Token;

/**
 * Provides [current-page:paged-text:TEXT] token.
 *
 * The @page placeholder in TEXT is replaced with the page number. The token is
 * empty on the first page. TEXT is not translated here: it is an
 * editor-provided value, translate it where it is stored (e.g. Metatag
 * configuration).
 */
final readonly class PagedTextToken {

  public function __construct(
    private PagerManagerInterface $pagerManager,
    private Token $token,
    private TranslationInterface $translation,
  ) {}

  /**
   * @return array<string, mixed>
   */
  #[Hook('token_info')]
  public function info(): array {
    return [
      'tokens' => [
        'current-page' => [
          'paged-text' => [
            'name' => $this->translation->translate('Text for paged pages'),
            'description' => $this->translation->translate('Text shown only on pages after the first. Use @page placeholder to insert page number.'),
            'dynamic' => TRUE,
          ],
        ],
      ],
    ];
  }

  /**
   * @param array<string, string> $tokens
   * @param array<string, mixed> $data
   * @param array<string, mixed> $options
   *
   * @return array<string, string>
   */
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    if ($type !== 'current-page') {
      return [];
    }

    $paged_text_tokens = $this->token->findWithPrefix($tokens, 'paged-text');
    if (!$paged_text_tokens) {
      return [];
    }

    $bubbleable_metadata->addCacheContexts(['url.query_args.pagers:0']);
    $current_page = $this->pagerManager->findPage();
    if ($current_page < 1) {
      return [];
    }

    $replacements = [];
    foreach ($paged_text_tokens as $name => $original) {
      $replacements[$original] = \str_replace('@page', (string) ($current_page + 1), $name);
    }

    return $replacements;
  }

}
```
:::::

Токен объявляется динамическим, чтобы последняя часть была произвольной: плейсхолдер `@page` заменяется на семантический номер страницы. Реализация простая: определяем токен, берём номер страницы через `::findPage()` (по той же причине, что и в токене URL: на странице сущности пагинатор может быть ещё не создан, подробнее в подразделе «Почему номер страницы берётся из запроса, а не из пагинатора»), проверяем, что номер больше 0 (на первой суффикс не выводим), и в динамической части заменяем `@page` на номер страницы.

Пример использования: `[current-page:title][current-page:paged-text: — страница №@page]`. Обратите внимание: динамическая часть начинается с пробела — иначе при отсутствии суффикса останется лишний пробел. И помните: если вы уже реализовали суффикс для заголовка страницы, в этом примере получится дубль. Лучше заменить `[current-page:title]` на реальный заголовок текущей сущности: `[term:name][current-page:paged-text: — страница №@page]`.

### Альтернатива: плейсхолдер вместо токена

У токена есть ограничение. Metatag вычисляет его один раз, в главном запросе, и хранит готовые строки. Если запрошенная страница выходит за диапазон, исключение 404 бросается позже, а страница ошибки (подзапрос) берёт теги из того же хранилища. В итоге у такой страницы `<title>` и `description` остаются со «страница 99999». Обычно это не важно: страница не индексируется. Если важно, суффикс можно подставлять не токеном, а после сборки страницы.

Идея: в шаблоне метатега вместо токена пишется обычный текст-плейсхолдер, например `{paged-text: — страница №@page}`. Metatag его не трогает (это не `[токен]`) и оставляет в готовых тегах. Дальше плейсхолдер находится и заменяется в двух местах:

- в `hook_page_attachments_alter()` — для тегов в `html_head` (например, `description`);
- в `hook_preprocess_html()` — для `<title>`, который лежит в `$variables['head_title']`. Хук должен выполняться после Metatag, для этого достаточно `order: Order::Last` в атрибуте `#[Hook]` (см. листинг 14).

::::: figure
::: figcaption
**Листинг 14** — Подстановка плейсхолдера после сборки страницы
:::
```php
<?php

declare(strict_types=1);

namespace Drupal\example\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Pager\PagerManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class PagedTextPlaceholder {

  private const string PATTERN = '/\{paged-text:(.*?)\}/su';

  public function __construct(
    private PagerManagerInterface $pagerManager,
    private RequestStack $requestStack,
  ) {}

  /**
   * @param array{'#attached'?: array{html_head?: array<int, array{0: array{'#attributes'?: array<string, mixed>}, 1: string}>}} $attachments
   */
  #[Hook('page_attachments_alter')]
  public function attachments(array &$attachments): void {
    $attachments['#attached']['html_head'] = $this->replaceInHead($attachments['#attached']['html_head'] ?? []);
  }

  /**
   * @param array<int, array{0: array{'#attributes'?: array<string, mixed>}, 1: string}> $html_head
   *
   * @return array<int, array{0: array{'#attributes'?: array<string, mixed>}, 1: string}>
   */
  private function replaceInHead(array $html_head): array {
    foreach ($html_head as $key => [$element]) {
      $content = $element['#attributes']['content'] ?? NULL;
      if (!\is_string($content)) {
        continue;
      }

      $html_head[$key][0]['#attributes']['content'] = $this->replace($content);
    }

    return $html_head;
  }

  /**
   * @param array{head_title?: array{title?: mixed}} $variables
   */
  #[Hook('preprocess_html', order: Order::Last)]
  public function html(array &$variables): void {
    $title = $variables['head_title']['title'] ?? NULL;
    if (!\is_string($title)) {
      return;
    }

    $variables['head_title']['title'] = $this->replace($title);
  }

  private function replace(string $text): string {
    return (string) \preg_replace_callback(self::PATTERN, $this->suffix(...), $text);
  }

  /**
   * @param array<int, string> $matches
   */
  private function suffix(array $matches): string {
    if ($this->requestStack->getCurrentRequest() !== $this->requestStack->getMainRequest()) {
      return '';
    }

    $page = $this->pagerManager->getPager()?->getCurrentPage() ?? 0;
    if ($page < 1) {
      return '';
    }

    return \str_replace('@page', (string) ($page + 1), $matches[1]);
  }

}
```
:::::

Правила замены такие:

- на первой странице плейсхолдер вырезается целиком;
- на странице N ≥ 2 плейсхолдер заменяется текстом из фигурных скобок, в котором `@page` заменён на номер страницы N;
- в подзапросе страницы ошибки плейсхолдер вырезается всегда, поэтому в `<head>` 404 суффикса нет.

Это тот же приём, что подмена canonical в подразделе «Альтернатива: подменять canonical после сборки страницы», и работает он по той же причине: к моменту `hook_page_attachments_alter()` и `hook_preprocess_html()` пагинатор уже создан, поэтому номер страницы можно брать из `::getPager()`, а не из запроса. Исключение то же: пагинатор в `#lazy_builder` создаётся позже, и плейсхолдер на таких страницах останется без суффикса. Платить приходится удобством: у плейсхолдера нет браузера токенов и подсказок, опечатка молча попадает на сайт, а там, где теги Metatag отдаются не через `<head>` (например, в превью на формах), строка останется сырой. Поэтому в статье основной вариант — токен, а плейсхолдер — на случай, когда лишний суффикс на странице 404 недопустим.

## Ресурсы

- [Готовый модуль](examples/example) — основные варианты. Альтернативные хуки из листингов 5 и 14 в модуль не включены, чтобы не конфликтовать с основным вариантом; их код есть в статье.

[^route-normalizer]: С включённой опцией «Использовать только чистые и каноничные URL.» модуль `drupal/redirect` нормализует `/foo/` → `/foo` и `/FOO` → `/foo`, но эти редиректы выполняются позже наших подписчиков (приоритет 30 против 40 и 39), поэтому регистр мы приводим к нижнему сами. Закодированные символы (`/%66oo`) он не нормализует вовсе: при сравнении URL декодируются, и `/%66oo` считается равным `/foo`. Их мы тоже декодируем сами.
[^metatag]: Здесь и далее под Metatag имеется в виду модуль `drupal/metatag`.
[^php-int-max]: На 32-битных сборках [PHP_INT_MAX](https://www.php.net/manual/ru/reserved.constants.php#constant.php-int-max) равен `2 147 483 647` (10 цифр), на 64-битных — `9 223 372 036 854 775 807` (19 цифр). Десятизначное значение на 32-битной сборке может не поместиться в `int`, поэтому безопаснее сократить длину на один знак. Девять цифр — это максимум `999 999 999`, почти миллиард страниц. Как говорится: «<del>640 КБ</del> 1 млрд страниц хватит всем».

[inbound-outbound-processor]: ../../../../2018/05/30/drupal-8-inbound-outbound-processor/article.ru.md
[cache-metadata]: ../../../../2017/07/15/drupal-8-cache-metadata/article.ru.md
[events]: ../../../../2018/04/10/drupal-8-events/article.ru.md
[services]: ../../../../2017/06/21/drupal-8-services/article.ru.md
[theme-hooks]: ../../../../2017/06/26/drupal-8-hook-theme/article.ru.md
[token]: ../../../../2018/09/06/drupal-8-tokens/article.ru.md
[hooks]: ../../../../2018/06/28/drupal-8-hooks/article.ru.md
[main-content-renderer]: ../../../../2019/09/05/drupal-8-main-content-renderer/article.ru.md
[lazy-builder]: ../../../../2017/07/07/drupal-8-lazy-builder/article.ru.md
[placeholder-strategy]: ../../../../2020/05/15/drupal-8-9-placeholder-strategy/article.ru.md
[route-subscriber]: ../../../../2018/04/13/drupal-8-route-subscriber/article.ru.md
[render-arrays]: ../../../../2020/02/05/drupal-8-render-arrays/article.ru.md
[language-negotiation]: ../../../../2017/07/04/drupal-8-language-negotiation/article.ru.md
