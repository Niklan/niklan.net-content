:: youtube {vid=vOO-5QGhZgs}

Меня часто спрашивают, как у меня устроен процесс разработки. Также, общаясь с
теми кто мне пишет, часто вижу, что некоторые проблемы возникают из-за
неправильного процесса разработки. Из-за чего друпал становится ещё сложнее и "
запутаннее". Хотя, в 8-й версии есть все инструменты для облегчения жизни.

## In nutshell

Если вы уже более-менее в теме, то весь процесс моей разработки строится
следующим образом:

- Локальная разработка всего и вся. (S)FTP использую только для очень мелких
  правок, читай хотфиксов.
- Деплою через конфиги и git.
- Зависимости контролирую через [composer][drupal-8-composer].
- В качестве ядра использую [composer drupal project][two-ways-of-installing-drupal-8].

## Вода

Ничего особенного в моём процессе разработки и деплоя проекта совершенно нет. Я
не открою вам Америки, всё очень тривиально. Этот подход сам вырисовывается в
процессе работы над 8-кой.

Я в 99 из 100 случаев, работаю над проектом один, есть возможность что кто-то
внесет какие-то изменения в админке, но именно в кодовой базе, как правило я
единственный участник. Учитывайте это, но мой процесс спокойно сможет
отскалироваться до целой команды.

**Почему локальная разработка?** Я считаю, что заниматься разработкой с Drupal 8
на сервере лишено смысла:

1. Для разработки в Drupal 8 нужно отключать полностью кэширование, включать
   отладку Twig и прочие прелести. Это не такое же отключение как в 7-ке.
   Нагрузку такой сайт начинает создавать очень и очень приличную. Если вы
   раньше могли заниматься разработкой Drupal 7, на условном VPS $10 от DO и
   попутно держать там продакшен проекты, то теперь, такой сервачек спокойно
   может быть сложен парочкой сайтов на 8-ке в режиме разработки. Это потребует
   более мощного сервера.
2. Для удобного написания кода, без IDE уже не обойтись. А для этого нужно иметь
   всё ядро и все сторонние зависимости под рукой IDE, чтобы она могла
   анализировать кодовую базу и подсказывать нам. Гонять ядро через (S)FTP боль
   даже на Drupal 7, на 8-ке даже пробовать нет смысла. Если в 7-ке это легко
   разруливалось, скачиванием ядра с drupal.org и закидыванием всех популярных
   модулей к нему и скармливание IDE, то в 8-ке это уже не гарантированное
   решение. Зависимости композера, так или иначе будут появляться, а собирать
   ядро на все случаи жизни для кормления IDE неудобно. Из-за различных сервисов
   и прочих новшеств Drupal 8, ядро с солянкой может ввести в заблуждение. В
   общем, как минимум, придется держать копию проекта для IDE, и опять же, в чем
   тогда смысл удаленной разработки? Проще делать на локалке, иначе придется
   регулярно кодовую базу актуализировать. Гибче и быстрее.
3. Для 8-ки чаще приходится поднимать xdebug, для отладки сложных моментов кода.
   Поднимая xdebug на сервере, вы создаете серьезную нагрузку для всех проектов
   которые там хостятся, даже в режиме ожидания xdebug жрет очень много. Что уж
   говорить о моменте отладки. Да и проброс портов для xdebug — занятие не из
   приятных. Когда на локалке всё просто работает и всё. Постоянно включать и
   отключать его на сервере тоже, дело не из приятных. А если там несколько
   разработчиков? Придется оставить включенным, в итоге мешаться будет всем, при
   этом делать, зачастую, необоснованную нагрузку на весь сервер, и лишает
   возможности держать продакшен проекты рядом с dev\stage сайтами. А ещё на
   локалке можно бесплатно использовать blackfire для профилирования, что тоже
   приятно.
4. В случае отключения интернета и других аномалий, например, бана РКН, никаких
   проблем не будет. Вся разработка локальная. Меня уже это неоднократно
   спасало, пока провайдер вел тех. работы с интернетом, я спокойно мог работать
   над сайтом, это никак меня не ограничивало. Также, если у вас офисы и студия,
   то для юр. лиц цены на интернет намного более злые, а скорость очень низкая.
   Гонять туда-сюда кучу трафика для 8-ки, также может стать проблемой для
   кого-то. А ещё можно сюда закинуть любителей работать на нотубках, в
   поездках, кафешках и т.д. где скорость WiFi и вообще доступ к сети может быть
   проблемой, не говоря о безопасности таких подключений. Зато деплой через SSH
   в размере паре десятков, а может и сотен кб, при любом подключении пройдет
   моментально с минимальными затратами трафика.
5. Самый главный пункт — нет проблем с переносом изменений. Деплой всех
   изменений, уже проверенных и готовых к показу, занимает не более 1 минуты.
   Серьезно.
6. Опять же, в случае необходимости развернуть с ничего, полноценную копию с
   прода, дело 5, ну максимум 10 минут. С докерами это нереально просто и
   быстро, а главное, безболезненно.

Для локальной разработки, на данный момент, я
пользуюсь [Docker4Drupal][docker4drupal-ubuntu]. Опять же, это тоже можно
закинуть как ещё один пунктик. Так как под каждый проект можно будет делать своё
окружение, но это можно сделать и на сервере, если заморочиться.

**Почему и для чего использую git?** Git я стал использовать уже давно, ещё с
7-ки. Это удобно и безопасно, так ещё и спасательный круг. Ох, как мне пару раз
он спасал жизнь, когда заказчик выдавал доступы "своим" разработчикам и они
поверх Drupal заливали что-то своё и всё ломая, а `git reset --HARD` возвращал
всё назад. Также легко заметить, кто-то влезал в кодовую базу кроме меня или
нет. И если это был кто-то левый, можно предъявить, типа, это кто-то из ваших
лазил и сделать это, то и сё. И ведь такое тоже пригодилось!

Но с 8-кой гит заиграл новыми красками. Из-за конфигов, стало возможным деплоить
не только код, но и то что "накликано" в админке. И это просто нереально круто!
Поработали, запушили, спулили на продакшене\деве\стейдже — все изменения
внесены. Это занимает ~1 минуту. И в случае проблем, можно всегда откатиться на
предыдущую версию конфигураций, которые были рабочие.

Также, если у проекта намечается крупный редизайн, можно вести его в отдельной
ветке, пока он не будет готов, при этом внося изменения в действующий проект. А
по завершению новой темы, смерджить и всё будет как надо. Уж за плюсы гита
рассказывать думаю совершенно лишено смысла. Но вот в контексте Drupal, я им
деплою конфигурации, кастомные модули, темы, composer.lock\json и возможно ещё
чего по мелочи.

В качестве репозитория последние месяца 2-3 использую GitLab. Перешел на них с
Bitbucket, понравилось, в принципе и не дергаюсь. А так, разницы, на самом деле,
для своего процесса я не заметил.

Деплой я произвожу используя SSH ключи. Для того чтобы постоянно не вводить
пароли, что неимоверно ускоряет процесс деплоя и внедрения.

А сам процесс деплоя состоит из следующих шагов:

1. _(локалка)_  На локалке, экспортирую актуальные конфиги `drush cex -y`.
2. _(локалка)_ Добавляю все изменения и пишу
   коммит `git add -A`, `git commit -m "Some changes was made."`.
3. _(локалка)_ Отправляю изменения в репо `git push`.
4. _(сервер)_ Захожу на сервер по SSH и в папку проекта. Забираю все изменения с
   репозитория `git fetch && git pull` (можно просто `git pull`)/
5. _(сервер)_ Если вижу что изменился composer.lock (ставил новые
   зависимости\обновлял существующие). Тогда пишу `composer install`.
6. _(сервер)_ Если были изменения в конфигурациях, то `drush cim -y`. Обратите
   внимание что влив конфигов должен быть **после** пункта 5.
7. _(сервер)_ В некоторых случаях, например при больших изменениях в теме,
   сбрасываю кэш `drush cr`. Как правило это не требуется, изменения применяются
   сразу.

Может вам покажется что это сложно и замороченно. Но просто гляньте видео где я
это всё делаю попутно болтая (отвлекаясь), и все равно это смело укладывается в
1 минуту, а то и меньше. В идеале ещё можно вебхуки и чтобы сервак сам делал всё
что начинается с пункта 4, но пока особой надобности у меня в этом нет.

**Как использую composer?** Composer у меня отвечает за все возможные
зависимости, ядро друпала, модули, темы, сторонние зависимости, включая JS
библиотеки. А также патчинг модулей. У меня есть про
него [отдельный материал][drupal-8-composer], так что особо тут разжевывать и нечего.

Но добавлю, что все зависимости я устанавливаю на локалке. В репозитории у меня
всегда имеется composer.json и composer.lock (!). Я их деплою через git. Но на
продакшене и прочих версиях сайта, я выполняю только `composer install` (он
устанавливает зависимости из .lock файла). Это не жрет так много памяти
как `require`, даже на самом говеном хостинге все установится, да и сам процесс
установки будет кратно быстрее, так как он устанавливает из уже распарсенных
версий и не запрашивает все репозитории. Также, это защищает от того, что версии
на продакшене и локалке могут разойтись. Ставятся те, что поставили в момент
установки на локалке. Собственно и обновляю я также, сначала на локалке, деплою
изменения через git, вливаю их через `composer install`. И это ещё один плюсик в
копилку локалки, ловили memory exceeded в момент запроса на сервачке? А вот
с `composer install` не будет.

**Почему composer drupal project?** В качестве установки ядра я не использую
стандартный способ. Все за и против я собрал в отдельном материале
про [два варианта установки ядра Drupal 8][two-ways-of-installing-drupal-8].

Я все веду через composer, что он позволяет вести, конечно ;)

## Жизненный цикл у проекта

Тут тоже особо расписывать нечего. Но в контексте вышесказанного, это происходит
следующим образом:

1. Разворачиваю локальный сервер на Docker4Drupal.
2. Устанавливаю ядро через composer drupal project. И сразу устанавливаю Drupal.
3. Создаю репозиторий для проекта на gitlab, инициализирую репозиторий, и сразу
   же сливаю с конфигами после установки.
4. На первых этапах проекта вся работа ведется на локалке, до тех пор, пока
   клиент не попросит показать, или не будет что-то уже более-менее готов для
   просмотра, например какой-нибудь калькулятор или ещё чего. Как правило,
   первую выгрузку делаю в момент начала верстки. Тут уже, как правило, основная
   часть функционала готова, и видно визуально как ведется работа над проектом.
5. Практически до самой выгрузки сайта, в большинстве случаев я делаю коммиты в
   репозиторий в конце рабочего дня (над проектом), одним большим коммитом с
   комментарием "WIP" (Work In Progress). Так как на начальных этапах слишком
   много изменений, и документировать всё, как по мне — пустая трата времени,
   лишенная смысла. Но если разработчиков несколько, то я бы уже комментировал.
6. Под конец я уже начинаю коммитить одним большим коммитом, но коммит содержит
   сводку изменений. Если же разработчиков по такому подходу будет работать
   более 1, то лучше коммит дробить на мелкие по задачкам. Так будет проще
   мерджить и решать конфликты в случае чего.
7. Мелкие изменения, всегда делаю отдельным коммитом с осмысленным комментарием.
8. Обновление ядра и модулей, аналогично, всегда отдельным коммитом. Правки с
   обновлениями стараюсь не мешать. На начальных этапах иногда такое получается
   из-за установки множества зависимостей, но именно обновления ядра и контриба
   после релиза или перед ним, всегда отделяю.
9. БД, до тех пор, пока клиент не захочет начать работать над сайтом,
   периодически выгружаю с локалки затерая версию на деве\стейдже. Так, у
   клиента есть возможность поиграться с сайтом, сломать его, в общем оторваться
   по полной, не опасаясь что будут последствия.
10. После выгрузки проекта и запуска его, работать продолжаю на локалке, деплой
    через git + composer + конфиги. Никаких перезаливов БД, естественно, нет.
    Как и проблем ?
11. Никакие изменения (кроме очень важных хотфиксов), на продакшене не делаю.
    Никаких новых модулей и т.д. Переодически сливаю оттуда только конфиги.
    Например от какого-нибудь robotstxt модуля, где его настройки правятся SEO
    спецами, и мне надо чтобы конфиги были актуальны и не перетерали их
    изменения моими старыми. Для этого, я сливаю их кадый день перед началом
    работы проекта. Если же изменений небыло, то просто сразу начинаю работу на
    локалке. Это видно при вводе `drush cex -y`, который напишет что изменилось
    что-то в конфигах, или они до сих пор актуальны (с тем что были задеплоины
    гитом в прошлый раз).
12. Если над проектом работа прекращается или крайне редкая, я либо дропаю
    контейнеры с проектом и оставляю только кодовую базу, либо вообще удаляю всё
    с локалки. А когда потребуется разворачиваю за пару минут, так как в
    репозитории находятся также файлы настроек docker для локалки. И все что мне
    нужно, создать папку под
    проекта, `git clone REPO .`, `docker-compose up -d`, `composer install`.
    Залить БД с продакшена, и всё готово к работе.

В принципе вот и всё. Даже не знаю, как о таком писать и что тут рассказывать.
Поэтому, можете посмотреть видео, там я это показал наглядно. Ничего
необычного тут не должно быть, всё очень даже стандартно, как по мне. Да я даже
блог так веду, на продакшене только контент, всё изменения на локалке.

[drupal-8-composer]: ../../../../2016/09/03/drupal-8-composer/article.ru.md
[two-ways-of-installing-drupal-8]: ../../../../2018/06/29/two-ways-of-installing-drupal-8/article.ru.md
[docker4drupal-ubuntu]: ../../../../2018/04/15/docker4drupal-ubuntu/article.ru.md
