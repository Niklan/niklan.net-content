::: note [Материал полностью переписан в мае 2018]
Данная статья полностью переписана на момент 8.5.x версии (май 2018). За этот
срок очень многое изменилось, как в подходе к разработке, так и к стандартному
файлу composer.json из ядра, также, я набрался опыта, появились интересные
решения и всё, наконец, устаканилось. ? _Данная версия будет содержать меньше
примеров, так как то, что требовалось делать раньше, уже внедрено на уровне ядра
и это утратило смысл._
:::

:: youtube {vid=-bYVRSyTlt4}

Начиная с Drupal 8.1 в ядро был внедрён Composer, для управления зависимостями
проекта. С этого момента подход к разработке, деплою и прочему начал меняться.

Работа с Composer теперь неотъемлемая часть процесса при разработке и поддержке
сайта на 8-ой версии. Вы никуда от него не убежите, и нравится он вам или нет,
вам придется его использовать. Так что, лучше сразу подружитесь с ним и
полностью начните сопровождать проект с его помощью. На самом деле, Composer —
круто! По началу напугает, будут непонимания, но со временем, вы будете только
рады. Особенно после стандартного подхода деплоя и управления 8-кой, это просто
рай.

Для всех новых проектов на 8-ке, а также старых, если у вас есть желание и
возможности, <mark>я настоятельно рекомендую
использовать</mark> [Drupal Project](https://github.com/drupal-composer/drupal-project).
Поверьте, проверьте, это просто и круто. Все беды с композером на данный момент
лишь потому, что его внедрили после релиза 8-ки, и по уму, надо вообще запретить
качать ядро друпала в виде архивов, либо оставлять их как ознакомительный
вариант с исходным кодом (сейчас это делать, очевидно, очень проблематично, но
вангану что Drupal 9, скорее всего, когда выйдет, будет поставляться именно так,
без архивов). Надо делать как Symfony, попробуйте найдите архивы у них на сайте
для загрузки, то-то же. Данный проект, немного меняет структуру проекта, но
решает просто тонну различных проблем стандартного подхода от drupal.org. Друпал
будет там тот же, всё будет абсолютно идентично, работа с проектом изменится
совсем чуточку, из-за немного измененной структуры, но плюсы, которые открывает
данный подход, просто перекрывают всё. Работать, деплоить, обновлять и
сопровождать проект будет просто сказка, после адовой солянки из стандартной
сборки. _Эта штука, вообще тема для отдельной статьи, и если интересно, могу про
неё детальнее рассказать, если есть трудности с пониманием что это._

Возвращаясь к композеру. Если вы не знакомы с ним, то это пакетный менеджер.
Если вы имели опыт или знаете как работают пакетные менеджеры в Linux apt, yum и
прочие, или, например, NPM или Yarn, то это примерно тоже самое, только для PHP.
Он позволяет вам запрашивать и устанавливать различные "пакеты" (зависимости),
удалять их, и выполнять различные действия в процессе выполнения.

В Drupal, композер позволяет вам качать и обновлять, как модули и темы, так и
ядро. И считайте что это уже обязательно. Уже множество модулей ставятся
исключительно композером, да, вы можете скачать и даже, возможно включится, но
работать не будет из-за недоступных зависимостей в виде пакетов из композера,
которые руками вам никак не закинуть. И раз вам придется ставить часть через
композер, проще всё ставить через него, будет всё стандартно внутри проекта, а
не солянка, что эти модули так, другие сяк, и сиди гадай как всё обновить
корректно.

Композер работает на основе двух файлов: **composer.json** и **composer.lock**.
Первый содержит всю информацию, какие требуются пакеты, что, куда и как ставить
и прочие настройки, скрипты и т.д. Он подразумевает то, что в нём можно править
руками и вмешиваться в его структуру. Composer.lock содержит уже актуальную
информацию о текущем проекте. Данный файл автоматически генерируется даже если
его нет, править его не нужно. О том, для чего он, будет чуточку позже.

## Стандартный composer.json

Чтобы немного понять что там и к чему, давайте рассмотрим стандартный
composer.json поставляемый с Drupal.

```json {"header":"composer.json"}
{
  "name": "drupal/drupal",
  "description": "Drupal is an open source content management platform powering millions of websites and applications.",
  "type": "project",
  "license": "GPL-2.0-or-later",
  "require": {
    "composer/installers": "^1.0.24",
    "wikimedia/composer-merge-plugin": "^1.4"
  },
  "replace": {
    "drupal/core": "^8.6"
  },
  "minimum-stability": "dev",
  "prefer-stable": true,
  "config": {
    "preferred-install": "dist",
    "autoloader-suffix": "Drupal8"
  },
  "extra": {
    "_readme": [
      "By default Drupal loads the autoloader from ./vendor/autoload.php.",
      "To change the autoloader you can edit ./autoload.php.",
      "This file specifies the packages.drupal.org repository.",
      "You can read more about this composer repository at:",
      "https://www.drupal.org/node/2718229"
    ],
    "merge-plugin": {
      "include": [
        "core/composer.json"
      ],
      "recurse": true,
      "replace": false,
      "merge-extra": false
    },
    "installer-paths": {
      "core": [
        "type:drupal-core"
      ],
      "modules/contrib/{$name}": [
        "type:drupal-module"
      ],
      "profiles/contrib/{$name}": [
        "type:drupal-profile"
      ],
      "themes/contrib/{$name}": [
        "type:drupal-theme"
      ],
      "drush/contrib/{$name}": [
        "type:drupal-drush"
      ],
      "modules/custom/{$name}": [
        "type:drupal-custom-module"
      ],
      "themes/custom/{$name}": [
        "type:drupal-custom-theme"
      ],
      "libraries/{$name}": [
        "type:drupal-library"
      ]
    }
  },
  "autoload": {
    "psr-4": {
      "Drupal/Core/Composer/": "core/lib/Drupal/Core/Composer"
    }
  },
  "scripts": {
    "pre-autoload-dump": "Drupal/Core/Composer/Composer::preAutoloadDump",
    "post-autoload-dump": "Drupal/Core/Composer/Composer::ensureHtaccess",
    "post-package-install": "Drupal/Core/Composer/Composer::vendorTestCodeCleanup",
    "post-package-update": "Drupal/Core/Composer/Composer::vendorTestCodeCleanup",
    "drupal-phpunit-upgrade-check": "Drupal/Core/Composer/Composer::upgradePHPUnit",
    "drupal-phpunit-upgrade": "@composer update phpunit/phpunit --with-dependencies --no-progress",
    "phpcs": "phpcs --standard=core/phpcs.xml.dist --runtime-set installed_paths $($COMPOSER_BINARY config vendor-dir)/drupal/coder/coder_sniffer --",
    "phpcbf": "phpcbf --standard=core/phpcs.xml.dist --runtime-set installed_paths $($COMPOSER_BINARY config vendor-dir)/drupal/coder/coder_sniffer --"
  },
  "repositories": [
    {
      "type": "composer",
      "url": "https://packages.drupal.org/8"
    }
  ]
}
```

_Я также добавил туда `"libraries/{$name}": ["type:drupal-library"]`, так как не
ясно, почему в ядре до сих пор этого нет, зато это есть в drupal project._

- `name`: Название проекта/пакета.
- `description`: Описание.
- `type`: Тип определяет что это за пакет и файл вообще. Типы можно указывать
  какие хотите. Для Drupal проектов есть свои
  типы `drupal-core`, `drupal-module`, `drupal-profile`, `drupal-theme`, `drupal-drush`, `drupal-custom-module`
  и `drupal-custom-theme`. А ещё есть `drupal-library` для библиотек. По сути,
  их тип влияет в итоге на то, куда данный пакет будет установлен. Сам композер
  по умолчанию имеет несколько поддерживаемых типов:
  - `library`: (по умолчанию) Подразумевает что пакет является библиотекой и
    установится в `vendor` папку.
  - `project`: Определяет что это корневой файл всего проекта. Как раз, корень
    сайта - это проект, а не какой-то конкретный пакет.
  - `metapackage`: Пустой пакет, который содержит только composer.json с
    зависимостями и ничего более.
  - `composer-plugin`: Пакет является плагином для композера.
- `license`: Тут указывается лицензия для пакета или проекта.
- `require`: Это вложенный "массив" из зависимостей. Тут указываются все
  необходимые для проекта зависимости в виде массива, где ключ название пакета,
  а значение - его верисия. В стандартном файле ядра две зависимости, но это
  далеко не все.
- `replace`: Список пакетов которые заменяются текущим. В данном случае, это
  значит что запросив `drupal/core`, он по сути ничего не сделает, так как
  текущий пакет его заменяет.
- `extra`: Раздел для дополнительных конфигураций работы композера.
  - `merge-plugin`: Это раздел настроек для
    зависимости `wikimedia/composer-merge-plugin`, которая добавляет поддержку
    этого раздела и пользуется им. В нем указется какие дополнительные файлы
    composer.json внутри проекта нужно подгружать когда вызывается композер. Это
    позволяет вам указывать какие-то специфичные зависимости, собирать свой
    composer.json в кастомных модулях и теме, но так как они не качаются
    композером, то их зависимости не будут обнаружены, данный плагин позволяет
    их находить.
  - `installed-paths`: Этот раздел позволяет задать, что и куда будет
    установлено. Вот здесь какраз используется `type`. У всех модулей на
    drupal.org по умолчанию тип `drupal-module`, на основе данного соответствия
    он понимает, что модуль нужнно положить в папку `modules/contrib/{$name}`где
    переменная `$name` заменится на название пакета. По умолчанию все они
    ставятся в `vendor`.
- `scripts`: Данный раздел позволяет подключать различные скрипты. У композера
  есть хуки (события) на которые можно подключать свои скритпы. В данном массиве
  это и происходит, в качестве ключа указывается собатыие на которое
  реагировать, а в значении либо команда, либо путь до объекта который будет
  вызываться в этот момент. Если нужно указать несколько, то значение становится
  массивом значений.
- `repositories`: В данном массиве объявляются репозитории в которых будут
  искаться пакеты и качаться. По умолчанию подключен packagegist, у друпала есть
  свой репозиторий для всех проектов с drupal.org, он там и подключается. Таким
  образом это позволяет качать модули и темы с drupal.org через композер. Вы
  можете добавлять свои собственные или другие сторонние репозитории, а также
  закрытые для компании и пользоваться.

Как правило, данный файл руками правится крайне редко, все зависимости
прописываются туда автоматически при установке, и удаляются при удалении.

## Установка, обновление и удаление пакетов

Три самые популярные команды композера, запомните их - считайте, вы освоили
композер.

**Установка** производится в следующем формате: `composer require PACKAGENAME`.
Можно указывать сразу несколько нужных пакетов на установку разделяя их
пробелом. Пакеты имеют формат `vendor/name`. Так намного проще разруливать
ситуации когда есть одноименные пакеты. Для всех проектов с drupal.org формат
будет `drupal/PROJECT_NAME`. Таким образом, для установки, например,
модуля [Paragraphs](https://www.drupal.org/project/paragraphs) нужно
прописать `composer require drupal/paragraphs`.

**Обновление** происходит аналогично, только можно опустить название
пакета `composer update` обновит все зависимости прописанные в composer.json.
Хитрость тут в том, что обновит он только те что прописаны, например, если
модуль прописывает свои зависимости для композера, то они не обновятся данной
командой, пока не будет вызвана с ключом `--with-dependencies`. Чтобы обновить
вообще все пакеты, включая их зависимости, нужно вызывать
так `composer update --with-dependencies`. Таким образом, нужно вызывать команду
когда выходят обновления минорных версий ядра, так как зависимые пакеты могут
сильно измениться за это время. Вы также можете указать конкретный пакет(ы) для
обновления: `composer update drupal/paragraphs`.

**Удаление** вызывается командой `composer remove PACKAGENAME`. **Не забудьте
удалить модуль друпала в админке, прежде чем удалять его пакет.** Не забывайте
что композер пакетный менеджер, а не драш и его аналоги, ничего об особенностях
друпала он не знает и он лишь качает\удаляет вам файлики, плюс небольшие
операции.

## Установка определенных версий

По умолчанию, запрос пакета на установку скачает самую последнюю актуальную (
отдав предпочтение стабильной) версию. Может получиться так, что вам потребуется
другая ветка модуля, или же, дев версия.

**Установка конкретной версии** имеет
вид `composer require PACKAGENAME:VERSION`. Подобным нужно пользоваться только
когда это действительно необходимо.

[Вариантов указания](https://getcomposer.org/doc/articles/versions.md#stability-constraints)
версий очень много, мы рассмотрим те что применимы в реалиях Drupal. Все они
будут работать, но используются всего парочка.

Композер сильно опирается, на так называемые, семантические
версии: `major.minor.patch`. У друпал проектов же версии имеют
формат `major.minor`.

Примеры указания конкретных версий:

- `1.2` — `=1.2.0-stable`, `1.0-dev`, `1.0-alpha3`, `1.0-rc5`: Такой вариант
  также позволяет сделать version lock. Таким образом, пакет будет строго данной
  версии всегда, так как это строгое указание. Он никогда не обновится до других
  версий пока это не будет явно запрошено указанием новой версии.
- `^1.2` — `>=1.2.0 < 2.0.0`: Позволяет скачать версию определенной мажорной
  версии и обновлять её только в пределах данной версии. Самый распространенный
  способ и используется по умолчанию при запросе пакета без указания специфичных
  требований к версии. Это защищает от смены мажорных версий, которые почти
  всегда приведут к поломке.
- `~1.2` — `>=1.2 < 2.0.0`: Похоже на пример выше, но как можете заметить, это
  практически идентичное обозначение как и `^1.2`, но оно не допускает
  обновлений `patch`. У друпал модулей и тем таких нет, поэтому какой из
  вариантов использовать — нет никакой разницы. Но я использую всегда `^1.2`,
  так как композер при запросе пакета без версии запрашивает аналогично и всё
  получается в едином стиле.

Думаю основная суть ясна. Но бывает и такое, что может потребоваться экзотика.
Например, есть модуль, но вам нужна по каким-то причинам конкретная версия в
определенный момент. Так как все пакеты drupal.org, да и не только, полностью в
VCS, то мы можем указать определенный коммит на момент которого нам нужен
модуль.

Допустим, вы хотите поставить модуль Pararaphs
на [вот этот](https://cgit.drupalcode.org/paragraphs/commit/?h=8.x-1.x&id=be6a91c8aaa1e174d1145c3af9d49944efade49a)
момент. Так как это коммит, то вы можете сослаться на его hash. У данного
коммита хэш `  be6a91c8aaa1e174d1145c3af9d49944efade49a`. Вы можете указать
либо: `composer require drupal/paragraphs:1.x-dev#be6a91c8aaa1e174d1145c3af9d49944efade49a`,
либо просто `composer require drupal/paragraphs:1.x-dev#be6a91c` (первые 7
символов хэша).

Но это не очень хорошее решение и, вероятно, никогда не потребуются, так как у
композера есть лучше решения. Это больше нужно для разработки и теста каких-то
конкретных версий модуля, а не для продакшена.

## Автоматическое применение патчей

Бывает что в модуле есть какая-то ошибка, которая пофикшена только в dev, но вы
хотите остаться на стабильной версии, или вообще пофикшена, но нету решения
нигде как в патче. Надо патчить, но рукам же не хочется, верно?

Есть такой [пакет](https://github.com/cweagans/composer-patches), который будет
делать это за вас каждый раз как что-то обновляется. Это невероятно удобно!

Для того чтобы оно работало, первым делом **нужно установить** его на
проект: `composer require cweagans/composer-patches`.

После чего, можно описывать патчи в composer.json. Описываются они в
разделе `extra`, в нём необходимо будет создать дополнительное
вложение `patches`, а уже в нем, формат следующий: ключ - название пакета, а его
значение, массив где указываются все патчи, ключем которого является короткое
описание патча, чтобы не забыть, а значение - путь до патча.

На примере стандартного composer json это выглядело бы следующим образом:

```json {"highlighted_lines":"9,46:51"}
{
  "name": "drupal/drupal",
  "description": "Drupal is an open source content management platform powering millions of websites and applications.",
  "type": "project",
  "license": "GPL-2.0-or-later",
  "require": {
    "composer/installers": "^1.0.24",
    "wikimedia/composer-merge-plugin": "^1.4",
    "cweagans/composer-patches": "^1.6"
  },
  "replace": {
    "drupal/core": "^8.6"
  },
  "minimum-stability": "dev",
  "prefer-stable": true,
  "config": {
    "preferred-install": "dist",
    "autoloader-suffix": "Drupal8"
  },
  "extra": {
    "_readme": [
      "By default Drupal loads the autoloader from ./vendor/autoload.php.",
      "To change the autoloader you can edit ./autoload.php.",
      "This file specifies the packages.drupal.org repository.",
      "You can read more about this composer repository at:",
      "https://www.drupal.org/node/2718229"
    ],
    "merge-plugin": {
      "include": [
        "core/composer.json"
      ],
      "recurse": true,
      "replace": false,
      "merge-extra": false
    },
    "installer-paths": {
      "core": [
        "type:drupal-core"
      ],
      "modules/contrib/{$name}": [
        "type:drupal-module"
      ],
      "profiles/contrib/{$name}": [
        "type:drupal-profile"
      ],
      "themes/contrib/{$name}": [
        "type:drupal-theme"
      ],
      "drush/contrib/{$name}": [
        "type:drupal-drush"
      ],
      "modules/custom/{$name}": [
        "type:drupal-custom-module"
      ],
      "themes/custom/{$name}": [
        "type:drupal-custom-theme"
      ],
      "libraries/{$name}": [
        "type:drupal-library"
      ]
    },
    "patches": {
      "drupal/core": {
        "Add startup configuration for PHP server": "https://www.drupal.org/files/issues/add_a_startup-1543858-30.patch",
        "Patch from local file": "patches/fix.patch"
      }
    }
  },
  "autoload": {
    "psr-4": {
      "Drupal/Core/Composer/": "core/lib/Drupal/Core/Composer"
    }
  },
  "scripts": {
    "pre-autoload-dump": "Drupal/Core/Composer/Composer::preAutoloadDump",
    "post-autoload-dump": "Drupal/Core/Composer/Composer::ensureHtaccess",
    "post-package-install": "Drupal/Core/Composer/Composer::vendorTestCodeCleanup",
    "post-package-update": "Drupal/Core/Composer/Composer::vendorTestCodeCleanup",
    "drupal-phpunit-upgrade-check": "Drupal/Core/Composer/Composer::upgradePHPUnit",
    "drupal-phpunit-upgrade": "@composer update phpunit/phpunit --with-dependencies --no-progress",
    "phpcs": "phpcs --standard=core/phpcs.xml.dist --runtime-set installed_paths $($COMPOSER_BINARY config vendor-dir)/drupal/coder/coder_sniffer --",
    "phpcbf": "phpcbf --standard=core/phpcs.xml.dist --runtime-set installed_paths $($COMPOSER_BINARY config vendor-dir)/drupal/coder/coder_sniffer --"
  },
  "repositories": [
    {
      "type": "composer",
      "url": "https://packages.drupal.org/8"
    }
  ]
}
```

Запросив данный пакет, он добавился в `require`, а руками мы добавили раздел с
патчами, где один патч скачается с drupal.org и применится, а второй, будет взят
с диска.

**Когда патч станет неактуальным** он будет писать что не удалось применить
патч. Это не значит что вашу проблему пофиксили, но стоит зайти туда, откуда
взяли патч, и посмотреть, действительно ли решена проблема, или требуется новый
патч.

## Установка JavaScript библиотек

Данный способ хоть никак и не урегулирован на данный момент на drupal.org, но
это очень удобный, простой и быстрый способ качать зависимости в виде JS
библиотек и прочих, что отсутствуют в стандартных репозиториях.

Во-первых, это держит ваш репозиторий проекта в чистом виде, данные зависимости
не имеет смысл добавлять в репозиторий так как они сами скачаются при запросе.
Во-вторых, это достаточно описать один раз, в дальнейшем простой копипаст и
корректировка версий. Многие модули сами предлагают такой способ установки
зависимых JS библиотек.

JS библиотек нет в репозиториях композера, так как он для PHP, то данная
возможность достигается путем добавления новых репозиториев в **composer.json**
файл, которые прямо ведут на библиотеку, а затем её запрос.

Самая важная часть в объявлении репозитория. Есть как минимум 2 варианта
провернуть это.

**Первый вариант** подразумевает установку напрямую из VCS репозитория:

```json {"header":"Пример установки Swiper из GitHub репозитория."}
{
  "type": "package",
  "package": {
    "name": "nolimits4web/swiper",
    "type": "drupal-library",
    "version": "4.0.7",
    "source": {
      "url": "https://github.com/nolimits4web/Swiper",
      "type": "git",
      "reference": "v4.0.7",
      "no-api": true
    }
  }
}
```

- `type`: указываем что это не настоящий репозиторий, а пакет.
- `package`: описание пакета
  - `name`: Название пакета. Старайтесь сохранять стандартное
    именование `vendor/package`. Это достаточно просто, по сути, из того же git
    это `username/repo-name`. Я также эту строку привожу всегда к нижнему
    регистру.
  - `type`: Так как это в 99 из 100 просто JS библиотека, а в Drupal модули
    ставятся в `libraries` папку, то указываем что это `drupal-library`.
    Благодаря соответствиям данный пакет сам установится в `libraries`.
  - `version`: Версия которая объявлена в разделе. Она учитывается при запросе
    пакета композером, но ни на что больше не влияет.
  - `source`: Описание источника пакета. Для git это следующие значения:
    - `url`: Путь до репозитория. Можно указывать без окончания `.git`, композер
      сам добавит эту приставку.
    - `type`: `git` если `git`, иной если другой.
    - `reference`: То, что он вытащит из репозитория. Указывается либо tag из
      VCS, либо hash конкретного коммита (или 7 первых его символов).
    - `no-api`: (опционально) Данная пометка только для репозиториев на GitHub.
      Это позволяет отключить использование GitHub API. По умолчанию, для таких
      пакетов композер будет пытаться получить все данные при помощи API, что
      должно ускорить работу. По факту же, это только тормозит и имеет смысл
      только на очень крупных проектах.

**Второй вариант** лучше во многих смыслах. Во-первых, он проще объявляется.
Во-вторых, он быстрее отрабатывает из-за его сути.

```json {"header":"Пример объявляения вторым способом"}
{
  "type": "package",
  "package": {
    "name": "dimsemenov/photoswipe",
    "type": "drupal-library",
    "version": "4.1.2",
    "dist": {
      "url": "https://github.com/dimsemenov/PhotoSwipe/archive/v4.1.2.zip",
      "type": "zip"
    }
  }
}
```

Данный пример делает всё тоже самое что и первый. Отличие лишь в том, что
вместо `source` тут указывается `dist`. Внутри него есть `url`, содержащий
ссылку на архив, и `type`, указывающий на тип архива.

Ссылки на эти архивы, всегда лежат в releases на гитхабе и их очень просто
скопировать. Данный вариант проще, так как он просто качает и распаковывает куда
надо, первый же вариант клонирует репозиторий и переключает на эту версию, что
естественно сказывается на скорости.

О всех других тонкостях можно почитать
в [офф. доке](https://getcomposer.org/doc/05-repositories.md).

Для того чтобы установит эти пакеты, далее достаточно просто вызвать штатную
установку по `name` указанному
вами: `composer require nolimits4web/swiper dimsemenov/photoswipe` и оба они
скачаются и поместатся в `libraries`.

Вот пример такого файла, опять же, на основе нашего стандартного composer.json
из ядра и двух примерах выше.

```json {"highlighted_lines":"10,11,75:100"}
{
  "name": "drupal/drupal",
  "description": "Drupal is an open source content management platform powering millions of websites and applications.",
  "type": "project",
  "license": "GPL-2.0-or-later",
  "require": {
    "composer/installers": "^1.0.24",
    "wikimedia/composer-merge-plugin": "^1.4",
    "cweagans/composer-patches": "^1.6",
    "dimsemenov/photoswipe": "^4.1",
    "nolimits4web/swiper": "^4.0"
  },
  "replace": {
    "drupal/core": "^8.6"
  },
  "minimum-stability": "dev",
  "prefer-stable": true,
  "config": {
    "preferred-install": "dist",
    "autoloader-suffix": "Drupal8"
  },
  "extra": {
    "_readme": [
      "By default Drupal loads the autoloader from ./vendor/autoload.php.",
      "To change the autoloader you can edit ./autoload.php.",
      "This file specifies the packages.drupal.org repository.",
      "You can read more about this composer repository at:",
      "https://www.drupal.org/node/2718229"
    ],
    "merge-plugin": {
      "include": [
        "core/composer.json"
      ],
      "recurse": true,
      "replace": false,
      "merge-extra": false
    },
    "installer-paths": {
      "core": [
        "type:drupal-core"
      ],
      "modules/contrib/{$name}": [
        "type:drupal-module"
      ],
      "profiles/contrib/{$name}": [
        "type:drupal-profile"
      ],
      "themes/contrib/{$name}": [
        "type:drupal-theme"
      ],
      "drush/contrib/{$name}": [
        "type:drupal-drush"
      ],
      "modules/custom/{$name}": [
        "type:drupal-custom-module"
      ],
      "themes/custom/{$name}": [
        "type:drupal-custom-theme"
      ],
      "libraries/{$name}": [
        "type:drupal-library"
      ]
    },
    "patches": {
      "drupal/core": {
        "Add startup configuration for PHP server": "https://www.drupal.org/files/issues/add_a_startup-1543858-30.patch",
        "Patch from local file": "/patches/fix.patch"
      }
    }
  },
  "autoload": {
    "psr-4": {
      "Drupal/Core/Composer/": "core/lib/Drupal/Core/Composer"
    }
  },
  "scripts": {
    "pre-autoload-dump": "Drupal/Core/Composer/Composer::preAutoloadDump",
    "post-autoload-dump": "Drupal/Core/Composer/Composer::ensureHtaccess",
    "post-package-install": "Drupal/Core/Composer/Composer::vendorTestCodeCleanup",
    "post-package-update": "Drupal/Core/Composer/Composer::vendorTestCodeCleanup",
    "drupal-phpunit-upgrade-check": "Drupal/Core/Composer/Composer::upgradePHPUnit",
    "drupal-phpunit-upgrade": "@composer update phpunit/phpunit --with-dependencies --no-progress",
    "phpcs": "phpcs --standard=core/phpcs.xml.dist --runtime-set installed_paths $($COMPOSER_BINARY config vendor-dir)/drupal/coder/coder_sniffer --",
    "phpcbf": "phpcbf --standard=core/phpcs.xml.dist --runtime-set installed_paths $($COMPOSER_BINARY config vendor-dir)/drupal/coder/coder_sniffer --"
  },
  "repositories": [
    {
      "type": "composer",
      "url": "https://packages.drupal.org/8"
    },
    {
      "type": "package",
      "package": {
        "name": "nolimits4web/swiper",
        "type": "drupal-library",
        "version": "4.0.7",
        "source": {
          "url": "https://github.com/nolimits4web/Swiper",
          "type": "git",
          "reference": "v4.0.7",
          "no-api": true
        }
      }
    },
    {
      "type": "package",
      "package": {
        "name": "dimsemenov/photoswipe",
        "type": "drupal-library",
        "version": "4.1.2",
        "dist": {
          "url": "https://github.com/dimsemenov/PhotoSwipe/archive/v4.1.2.zip",
          "type": "zip"
        }
      }
    }
  ]
}
```

### Дополнительный способ

Также есть вот такой проект — [Asset Packagist](https://asset-packagist.org/).
Данный проект — репозиторий для Composer, в котором собираются пакеты из NPM и
Bower репозиториев, где с 99% вероятностью можно всегда найти JS зависимость для
модулей. Я не пробовал его на живых проектах, но для старта это может стать
намного лучше, помимо его простоты, он также содержит актуальные версии, а не
фиксированные как в первых двух вариантах. Такой огромный репо также скажется на
скорости работы композера, если при подключении это не стало критично — почему
бы и нет?

Подключается он следующим образом. В `repositories` добавляется:

```json
{
  "type": "composer",
  "url": "https://asset-packagist.org"
}
```

Затем догружается зависимость, чтобы `installer-paths` работал
корректно: `composer require oomphinc/composer-installers-extender`

Затем, в `installer-paths` нужно поправить место куда будут устанавливаться
данные пакеты:

```json {"highlighted_lines":"35,44,67:70"}
{
  "name": "drupal/drupal",
  "description": "Drupal is an open source content management platform powering millions of websites and applications.",
  "type": "project",
  "license": "GPL-2.0-or-later",
  "require": {
    "composer/installers": "^1.0.24",
    "wikimedia/composer-merge-plugin": "^1.4"
  },
  "replace": {
    "drupal/core": "^8.6"
  },
  "minimum-stability": "dev",
  "prefer-stable": true,
  "config": {
    "preferred-install": "dist",
    "autoloader-suffix": "Drupal8"
  },
  "extra": {
    "_readme": [
      "By default Drupal loads the autoloader from ./vendor/autoload.php.",
      "To change the autoloader you can edit ./autoload.php.",
      "This file specifies the packages.drupal.org repository.",
      "You can read more about this composer repository at:",
      "https://www.drupal.org/node/2718229"
    ],
    "merge-plugin": {
      "include": [
        "core/composer.json"
      ],
      "recurse": true,
      "replace": false,
      "merge-extra": false
    },
    "installer-types": [
      "bower-asset",
      "npm-asset"
    ],
    "installer-paths": {
      "core": [
        "type:drupal-core"
      ],
      "modules/contrib/{$name}": [
        "type:drupal-module"
      ],
      "profiles/contrib/{$name}": [
        "type:drupal-profile"
      ],
      "themes/contrib/{$name}": [
        "type:drupal-theme"
      ],
      "drush/contrib/{$name}": [
        "type:drupal-drush"
      ],
      "modules/custom/{$name}": [
        "type:drupal-custom-module"
      ],
      "themes/custom/{$name}": [
        "type:drupal-custom-theme"
      ],
      "libraries/{$name}": [
        "type:drupal-library",
        "type:bower-asset",
        "type:npm-asset"
      ]
    }
  },
  "autoload": {
    "psr-4": {
      "Drupal/Core/Composer/": "core/lib/Drupal/Core/Composer"
    }
  },
  "scripts": {
    "pre-autoload-dump": "Drupal/Core/Composer/Composer::preAutoloadDump",
    "post-autoload-dump": "Drupal/Core/Composer/Composer::ensureHtaccess",
    "post-package-install": "Drupal/Core/Composer/Composer::vendorTestCodeCleanup",
    "post-package-update": "Drupal/Core/Composer/Composer::vendorTestCodeCleanup",
    "drupal-phpunit-upgrade-check": "Drupal/Core/Composer/Composer::upgradePHPUnit",
    "drupal-phpunit-upgrade": "@composer update phpunit/phpunit --with-dependencies --no-progress",
    "phpcs": "phpcs --standard=core/phpcs.xml.dist --runtime-set installed_paths $($COMPOSER_BINARY config vendor-dir)/drupal/coder/coder_sniffer --",
    "phpcbf": "phpcbf --standard=core/phpcs.xml.dist --runtime-set installed_paths $($COMPOSER_BINARY config vendor-dir)/drupal/coder/coder_sniffer --"
  },
  "repositories": [
    {
      "type": "composer",
      "url": "https://packages.drupal.org/8"
    },
    {
      "type": "composer",
      "url": "https://asset-packagist.org"
    }
  ]
}
```

После чего можно устанавливать пакеты. Например, swiper из примера выше можно
взять из npm репозитория: `composer require npm-asset/swiper`, а фотосвайп из
bower `composer require bower-asset/photoswipe`.

## composer.json в кастом модуле и теме

По хорошему, сейчас каждый модуль или тема должны содержать composer.json, и эту
тенденцию можно наблюдать у всех популярных модулей. Даже если вам не нужны
никакие зависимости, лучше чтобы он был.

Хотя бы такой минимум:

```json
{
  "name": "drupal/example",
  "description": "This is an example composer.json for example module.",
  "type": "drupal-custom-module",
  "license": "GPL-2.0+"
}
```

_Не забудьте правильно подобрать type._

Если модуль публикуется на drupal.org как полноценный проект, то туда можно
добавить побольше информации.

```json
{
  "name": "drupal/PROJECTNAME",
  "type": "drupal-module",
  "description": "My module.",
  "keywords": [
    "Drupal"
  ],
  "license": "GPL-2.0+",
  "homepage": "https://www.drupal.org/project/PROJECTNAME",
  "authors": [
    {
      "name": "Your name here",
      "homepage": "https://www.drupal.org/u/your_name_here",
      "role": "Maintainer"
    },
    {
      "name": "Contributors",
      "homepage": "https://www.drupal.org/node/NID/committers",
      "role": "Contributors"
    }
  ],
  "support": {
    "issues": "https://www.drupal.org/project/issues/PROJECTNAME",
    "source": "http://cgit.drupalcode.org/test"
  }
}
```

Самый быстрый способ получить такой файл: `drush generate composer`.

Если вы объявите такой файл и зависимости в кастомном модуле и теме, то ваши
зависимости не будут загружены. Для этого нужно не забыть добавить путь до
composer.json файла в разделе `"merge-plugin"`. Проще всего, добавить следующего
вида: `"modules/custom/*/composer.json"`.

## Экономия памяти ?

Такая гибкость достигается путем не хилой прожорливости по памяти. Использовать
композер на хостинге или слабом VPS может закончиться остановкой операции из-за
нехватки памяти. В идеале composer нужно `memory_limit 1024M`. Где-то это
невозможно, где-то это очень проблемно.

И решение у композера есть, его нужно просто использовать правильно! Те две
команды что мы рассмотрели ранее `require` и `update` при своём вызове **всегда
** запускают полный парсинг всех репозиториев и данных о каждом проекте. Потому
столько памяти и жрется. Эти две команды должны быть в обороте только на локалке
и дев сервере где лимиты не должны быть проблемой.

После того как вы вызвали одну из этих команд, генерируется файлик *
*composer.lock** — он то и спасает от этой проблемы. В него записываются все
пакеты которые установлены и прямые пути на их загрузку той версии, что была
установлена в момент `require` или `update`. Данный файл **нужно хранить в
репозитории**.

Когда вы обновили или установили что нужно, отгрузили на продакшен, достаточно
написать `composer install` и всё установится, удалиться и обновиться за
считанные секунды, так как он не будет заниматься парсингом и кучей левых
запросов.

Возьмите себе за правило, `require` и `update` только на дев и
локалке, `install` на продакшене и больше ничего. Это решит не только проблемы с
памятью и скоростью, но и кучу других вытекающих (например выйдет новая версия
пока релизилось и там что-то изменилось и посыпались ошибки).

Причем вам даже не обязательно иметь полноценный сайт для того чтобы
доустановить пакет или обновить их все. Вы можете просто склонировать себе
репозиторий проекта на локалку, установить\обновить\удалить всё что необходимо,
закоммитить изменения в `composer.json` и `composer.lock`, запушить, а на
продакшене fetch, pull, `composer install` и вуаля, всё быстро и просто.

git — лучший друг для composer.

## Выполнение команд после установки и\или обновления

Данную операцию я делаю вообще на каждом проекте. Все дело в том, что я активно
использую модуль `robotstxt`, который позволяет управлять файлом `robots.txt` в
админке и иметь разыне версии под разные языки и т.д. Если вы с ним работали, то
в курсе, что для его работы надо удалять файл robots.txt в корне сайта. После
обновлений он всегда возвращается на место и это раздражает. Благодаря
композеру, это фиксится очень просто.

Для этого в `scripts` разделе нам нужно добавить команду на
событие `post-update-cmd`. Как правило сервера на линуксах, поэтому команда
будет `rm -f robots.txt`.

```json {"highlighted_lines":"60:62"}
{
  "name": "drupal/drupal",
  "description": "Drupal is an open source content management platform powering millions of websites and applications.",
  "type": "project",
  "license": "GPL-2.0-or-later",
  "require": {
    "composer/installers": "^1.0.24",
    "wikimedia/composer-merge-plugin": "^1.4"
  },
  "replace": {
    "drupal/core": "^8.6"
  },
  "minimum-stability": "dev",
  "prefer-stable": true,
  "config": {
    "preferred-install": "dist",
    "autoloader-suffix": "Drupal8"
  },
  "extra": {
    "_readme": [
      "By default Drupal loads the autoloader from ./vendor/autoload.php.",
      "To change the autoloader you can edit ./autoload.php.",
      "This file specifies the packages.drupal.org repository.",
      "You can read more about this composer repository at:",
      "https://www.drupal.org/node/2718229"
    ],
    "merge-plugin": {
      "include": [
        "core/composer.json"
      ],
      "recurse": true,
      "replace": false,
      "merge-extra": false
    },
    "installer-paths": {
      "core": [
        "type:drupal-core"
      ],
      "modules/contrib/{$name}": [
        "type:drupal-module"
      ],
      "profiles/contrib/{$name}": [
        "type:drupal-profile"
      ],
      "themes/contrib/{$name}": [
        "type:drupal-theme"
      ],
      "drush/contrib/{$name}": [
        "type:drupal-drush"
      ],
      "modules/custom/{$name}": [
        "type:drupal-custom-module"
      ],
      "themes/custom/{$name}": [
        "type:drupal-custom-theme"
      ],
      "libraries/{$name}": [
        "type:drupal-library"
      ]
    }
  },
  "autoload": {
    "psr-4": {
      "Drupal/Core/Composer/": "core/lib/Drupal/Core/Composer"
    }
  },
  "scripts": {
    "pre-autoload-dump": "Drupal/Core/Composer/Composer::preAutoloadDump",
    "post-autoload-dump": "Drupal/Core/Composer/Composer::ensureHtaccess",
    "post-package-install": "Drupal/Core/Composer/Composer::vendorTestCodeCleanup",
    "post-package-update": "Drupal/Core/Composer/Composer::vendorTestCodeCleanup",
    "drupal-phpunit-upgrade-check": "Drupal/Core/Composer/Composer::upgradePHPUnit",
    "drupal-phpunit-upgrade": "@composer update phpunit/phpunit --with-dependencies --no-progress",
    "phpcs": "phpcs --standard=core/phpcs.xml.dist --runtime-set installed_paths $($COMPOSER_BINARY config vendor-dir)/drupal/coder/coder_sniffer --",
    "phpcbf": "phpcbf --standard=core/phpcs.xml.dist --runtime-set installed_paths $($COMPOSER_BINARY config vendor-dir)/drupal/coder/coder_sniffer --",
    "post-update-cmd": [
      "rm -f robots.txt"
    ]
  },
  "repositories": [
    {
      "type": "composer",
      "url": "https://packages.drupal.org/8"
    }
  ]
}
```

После этого, при каждом вызове update или require он будет вызывать данную
команду в конце всех операций. Таким образом файл robots.txt будет удаляться
постоянно как только будет появляться и проблема исчезнет.
