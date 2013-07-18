=== translit-it ===
Contributors: Ichi-nya
Donate link: http://goo.gl/GieeJ
Tags: l10n, translations, transliteration, slugs, russian, rustolat, rustoeng, rus2eng
Requires at least: 2.7
Tested up to: 4.5.2
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Используется для перевода ЧПУ с русского на английский язык.

== Description ==

Переводит русские slugs (postname) на английский с помощью переводчика или транслитом.

Похож на плагины [Cyr-To-Lat](http://wordpress.org/extend/plugins/cyr2lat/) и [rus-to-lat](http://wordpress.org/extend/plugins/rustolat/).

В отличие от оригинального плагина rus-to-lat, этот плагин может не только транслитерировать слаги постов и тегов, но переводит их с помощью переводчика (если тот недоступен - тогда работает транслитерация).

ВНИМАНИЕ! Плагин не работает если активирован rus-to-lat.


== Installation ==

1. Загрузите папку плагина в `/wp-content/plugins/`.
2. Активируйте плагин в Wordpress.
3. В параметрах [Параметры -> Транслитерируй это!] выбрать способ транситерации и других настроек.


== Frequently Asked Questions ==

= Когда появится Google Translate =

Это неизвестно. Гугл закрыл внешний перевод.

== Screenshots ==

translit-it1.jpg

== Changelog ==

= 0.1 =
* Создание плагина

= 0.2 =
* Небольшие изменения

= 0.3 =
Стабильная версия.

= 0.5 =
Обновление API Яндекса
Исправлена ошибка с пропуском не транслитерируемых букв

== Upgrade Notice ==

= 0.5 =
Обновление API Яндекса
Исправлена ошибка с пропуском не транслитерируемых букв
  
== ToDo ==
Планы на следующие версии:

1. Вынести файл настроек отдельно.
1. Подключить Google Translate.
1. Изменить меню опций.
1. Добавить перевод файлов
<?php code(); // goes in backticks ?>