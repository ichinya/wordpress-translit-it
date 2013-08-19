=== translit-it ===
Contributors: Ichi-nya
Donate link: http://goo.gl/GieeJ
Tags: l10n, translations, transliteration, slugs, russian, rustolat, rustoeng, rus2eng
Requires at least: 3.3
Tested up to: 3.6
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Is used to put the slugs from Russian into English.

== Description ==

Переводит русские slugs (postname) на английский с помощью переводчика или транслитом.

Похож на плагины [Cyr-To-Lat](http://wordpress.org/extend/plugins/cyr2lat/) и [rus-to-lat](http://wordpress.org/extend/plugins/rustolat/).

В отличие от оригинального плагина rus-to-lat, этот плагин может не только транслитерировать слаги постов и тегов, но переводит их с помощью переводчика.

== Installation ==

1. Загрузите папку плагина в `/wp-content/plugins/`.
1. Активируйте плагин в Wordpress.
1. В параметрах [Параметры -> Транслитерируй это!] выбрать способ транситерации и других настроек.

== Frequently asked questions ==

= Когда появится Google Translate =
Гугл сделал платным сервис перевода через API Google

= Ошибки при работе =
Плагин не работает если активирован rus-to-lat.

== Screenshots ==

1. 

== Changelog ==

= 0.1 =
* Создание плагина

= 0.2 =
* Небольшие изменения

= 0.3 =
* Стабильная версия.

= 0.5 =
* Обновление API Яндекса
* Исправлена ошибка с пропуском не транслитерируемых букв

= 1.0 =
* Оптимизация запросов к API Яндекса
* Исправление ошибок

= 1.1 =
* Добавление нового способа обращение к Яндекс.Переводчику
* Исправление ошибок

== Upgrade notice ==

* Добавление нового способа обращение к Яндекс.Переводчику
* Исправление ошибок

== ToDo ==
Планы на следующие версии:

1. Вынести файл настроек отдельно.
1. Подключить Google Translate.
1. Изменить меню опций.
1. Добавить перевод файлов
<?php code(); // goes in backticks ?>