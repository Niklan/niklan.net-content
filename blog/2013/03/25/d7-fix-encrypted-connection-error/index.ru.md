---
id: d7-fix-encrypted-connection-error
language: ru
title: 'Drupal 7: Не устанавливаются темы и модули.'
created: '2013-03-25T11:10:03'
updated: '2023-10-16T18:21:20'
needs_manual_review: true
description: 'Фиксим права на папку чтобы модули и теми начали устанавливаться на Ubuntu.'
promo: 'image/1.png'
tags:
  - Drupal
  - 'Drupal 7'
---

После того как перешел на Ubuntu у меня на всех сайтах всплыла следующая ошибка:

> "WARNING: You are not using an encrypted connection, so your password will be sent in plain text. Learn more." "To continue, provide your server connection details"

Далее предлагается указать настройки FTP. Но это полный бред, даже не пробуйте это сделать.

В общем эта ошибка не позволяет устанавливать темы и модули в автоматическом режиме. А решение простое до безобразия, у веб-сервера просто нету доступа на запись в папку sites.

~~~sh {"header":"Решение для локального сервера"}
sudo chown www-data:www-data -R domains/drupal.dev/sites
~~~

Не забудьте вместо моего адреса до папки sites указать свой.

И все, теперь заработает. Не знаю как на Windows, есть ли там вообще такая ошибка, если есть то, скорее всего, проблема в папке temp.
