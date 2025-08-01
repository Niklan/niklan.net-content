Когда я пишу кастомные модули для проектов, там естественным образом оказывается
множество различных лейблов, описаний и т.д. Это поднимает вопрос, а на каком
языке писать эти самые метки?

По стандартам, весь код должен быть на английском языке, даже если ваш сайт
моноязычный. Если проект моноязычный, в принципе, очень легко частично убегать
от проблемы. Но она будет переодически о себе трубить, если её не игнорировать,
получается достаточнно странная ситуация.

Если забить на факт стандартов (что уже аргумент в пользу подхода), что метки,
описания и прочие пользовательские строки должны быть англоязычными, остаются
другие проблемы. А именно, не везде можно игнорировать `TranslatableMarkup`.
Например, в маршрутах по умолчанию заголовок автоматически проходит через
переводы, но это решается кастомным методом для заголовка, который вернет строку
на нужном языке в обход переводов. Но это требует дополнительных действий и
кода.

Есть места, где игнорирование в принципе невозможно. Например,
в `*.links.menu.yml` и подобных файлах, вообще не предусметрено что лейблом
может быть отличный от английского язык.

Да, можно писать на русском, но это
равносильно `new TranslatableMarkup('Привет Мир!')` — bad practice в довесок к
игнорированию стандарта.

Можно миксовать, там где нельзя подлезть, писать на английском, а где можно, на
русском. Но это полумера всеравно поднимет вопрос, а как же тогда переводить то
что написано на английском. Да и вообще, сильно ударяет по "целостности
проекта", и получается тяп-ляп.

В итоге, вместо того, чтобы воевать и уворачиваться от стандартов, я пришёл к
тому, что нужно просто им следовать и не париться. И тут возникает резонный
вопрос, если проект кастомный, как доставлять переводы? Ведь после деплоя
заходить и переводить — полнешний бред и пустая трата времени.

Как хорошо что и тут позаботились в ядре и добавили спец. настройки для модулей
и тем! Всего 2 строки в `*.info.yml` файле модуля позволят забыть про деплой
переводов.

## Как подключить и использовать

Для подключения поддержки переводов модулем (или темой), который не находится на
drupal.org и не имеет переводов на localize.drupal.org, можно добавить в его
объявление две строки:

- `'interface translation project'`: (обязательно) Содержит название проекта, к
  которому относятся переводы. В контексте кастомного модуля — это машинное имя
  модуля.
- `'interface translation server pattern'`: (обязательно) Путь до "сервера",
  откуда забирать переводы для импорта.

Как уже написал выше, в `'interface translation project'` просто указывается
машинное имя модуля где это описывается.

В случае с `'interface translation server pattern'`, нужно указать путь до **.po
** файла с переводами, которые необходимо импортировать для данного модуля.

Данный параметр имеет шаблоны подстановки:

- `%core`: Версия ядра указанная в `core` `*.info.yml` файла.
- `%project`: Машинное имя проекта.
- `%version`: Версия модуля из `version`. Для кастомов это имеет мало смысла,
  так как version задавать руками не принято, его добавляет drupal.org для своих
  проектов. Но если указан, можно задействовать.
- `%language`: Язык, для которого ищется перевод на импорт.

Эти плейсхолдеры опциональны. В пути можно указать как локальный путь, так и
удаленный, например:

- Локально с использованием
  StreamWrapper: `public://ru.po`, `translations://ru.po` и т.д.
- Локально по относительному
  пути: `modules/custom/%project/translations/%language.po`. В данном случае,
  путь должен быть относитель docroot проекта.
- На удалённом
  сервере `https://my-company.ru/translations/%core/%project/%language.po`.

В своих проектах я использую второй подход, все переводы я храню
в `translations/ru.po` файле модуля. В пути я использую подставноку `%project`,
чтобы не писать название второй раз.

Далее остаётся дело за малым, писать на английском в коде, и добавлять попутно
переводы в файлик. Может показаться что это слишком замороченно, но на деле даже
не напрягает, делается крайне шустро.

Для того чтобы переводы подхватились нужно обновить
локаль `drush locale:update`, которая и подхватит файл указанный в настройках, а
затем, сразу импортирует его на проект. После данных манипуляций необходимо
также сбросить кэш, чтобы переводы актуализировались.

Так, у меня в деплой добавилась просто одна строка перед сбросом кэша, и у меня
всегда актуальные переводы модулей, не только с drupal.org, но и кастомные.

```yaml {"header":"Пример подключения"}
name: Dummy
type: module
description: 'Some example'
package: Custom
core: 8.x

'interface translation project': dummy
'interface translation server pattern': modules/custom/%project/translations/%language.po
```

## Разбираемся с .po файлом

Как я и написал, придётся ручками добавлять переводы по мере нобходимости. Проще
всего не пытаться найти прогу для этого, а просто запомнить пару условий и
конструкций, а затем писать их через ваш же редактор кода, чтобы не тратить
время на доп. софт.

Файл
.po [стандартизован](https://www.gnu.org/software/gettext/manual/html_node/PO-Files.html),
я лишь напишу шпаргалку:

- В каждом `.po` файле первый перевод идет для мета-информации, он полностью
  игнорируется. Если использовать софт, он туда добавляет информацию об авторе,
  проекте и т.д. Для Русского языка нужно указать формулу склонений, иначе не
  будет работать третья форма (5 яблок).

::: note
Ранее тут была рекомендация использовать в качестве «заголовка» пустые `msgid`
и `msgstr`. В случае с русским языком и тремя склонениями, также необходимо
указывать формулу, которая представлена ниже. Если переводы вашего модуля будут
загружены последними, то будет всего два склонения!
:::

```text
msgid ""
msgstr ""
"Plural-Forms: nplurals=3; plural=((((n%10)==1)&&((n%100)!=11))?(0):(((((n%10)>=2)&&((n%10)<=4))&&(((n%100)<10)||((n%100)>=20)))?(1):2));\n"
```

- `msgid` — для оригинала, `msgstr` — для перевода. Должны идти друг за другом
  именно в таком порядке.
- `#` для комментариев.
- Для переводов с `context`, перед `msgid` указывается контекст при
  помощи `msgctxt`. Например `msgctxt "Drupal commerce"`.
- Для множественных форм используется следующая конструкция:

```text
# Оригинал в единственном числе.
msgid "1 minute"
# Оригинал во множественном числе.
msgid_plural "@count minutes"
# Перевод единственного числа.
msgstr[0] "1 минута"
# Первая форма множественного перевода.
msgstr[1] "@count минуты"
# Вторая форма множественного перевода.
msgstr[2] "@count минут"
```

- Для многострочных значений, первая строка должна быть `""`, затем, каждая
  строка должна содержать необходимый текст.

```text
msgid ""
"First paragraph.\n"
"Second paragraph."
msgstr ""
"Первый абзац.\n"
"Второй абзац.\n"
```

## Автоматическая генерация .po файла

Если вы хотите автоматизировать генерацию оригинальных строк, то можно
воспользовать проектом [drupal/potx](https://github.com/kgaut/drupal-potx). Он
сырой, но рабочий.

Вам необходимо загрузить модуль при
помощи [композера][drupal-8-composer] `composer require kgaut/potx`, а затем включить
его `drusn en potx`. После чего, у вас появится новая команда для генерации .po
файлов. Там есть способ в обход установки модуля, для этого читайте README.md
проекта.

Пример экспорта всех строк из указанных модулей. Строки будут экспортированы в
корень проекта в файл `general.pot`.

```bash
drush potx single --include=modules/contrib/potx --modules=foo,bar --api=8
```

Аналогичная команда, но с `multiple` вместо `single` сделает по .pot файлу, для
каждого файла где найдётся строка на перевод. В прямом смысле, если в
файле `modules/custom/dummy/src/Plugin/EntityReferenceSelection/ProductVariationWithSkuSelection.php`
будут найдены строки, то он создаст
файл `modules-custom-dummy-src-Plugin-EntityReferenceSelection.pot` (и опять в
корне сайта).

```bash
drush potx multiple --include=modules/contrib/potx --modules=foo,bar --api=8
```

Такие команды сгенерируют примерно следующий результат:

```text
# $Id$
#
# LANGUAGE translation of Drupal (general)
# Copyright YEAR NAME <EMAIL@ADDRESS>
# Generated from files:
#  modules/custom/dummy/dummy.info.yml: n/a
#  modules/custom/dummy/src/Plugin/EntityReferenceSelection/ProductVariationWithSkuSelection.php: n/a
#
#, fuzzy
msgid ""
msgstr ""
"Project-Id-Version: PROJECT VERSION\n"
"POT-Creation-Date: 2019-09-02 14:22+0000\n"
"PO-Revision-Date: YYYY-mm-DD HH:MM+ZZZZ\n"
"Last-Translator: NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <EMAIL@ADDRESS>\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=utf-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\n"

#: modules/custom/dummy/dummy.info.yml:0
msgid "Dummy"
msgstr ""

#: modules/custom/dummy/dummy.info.yml:0
msgid "EntityReferenceSelection example"
msgstr ""

#: modules/custom/dummy/dummy.info.yml:0
msgid "Custom"
msgstr ""

#: modules/custom/dummy/src/Plugin/EntityReferenceSelection/ProductVariationWithSkuSelection.php:8
msgid "Order Item selection with SKU"
msgstr ""
```

Удобен он тем, что часть работы берет на себя модуль. Он также указывает где он
нашёл данную строку, и когда она пропадёт, он её удалит (хоть и глючит для yml
файлов определение строки, но работает вроде корректно).

Но данный способ мне не прижился, так как экспорт в 1 здоровенный файл, даже без
возможности указать куда выгружать — для меня перекрывает абсолютно все плюсы. Я
хочу чтобы переводы были только там, где они используются, а не в одном
спагетти-файле. Плюс, как видно из сгенерированного примера, а также явно
указано на странице проекта — он сырой.

Также у него не очень приятное поведение если поменялась оригинальная строка. Он
заменяет как строку, так и перевод. С одной стороны это логично, с другой, если
вы добавили запятую, он откатит перевод целиком, но в этом нет необходимости. В
общем, спорный помощник, но может вам подойдёт.

[drupal-8-composer]: ../../../../2016/09/03/drupal-8-composer/article.ru.md
