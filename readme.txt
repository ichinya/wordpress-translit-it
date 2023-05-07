=== translit-it ===
Contributors: Ichi-nya
Donate link: https://sobe.ru/na/translit_it
Tags: l10n, translations, transliteration, slugs, russian, rustolat, rustoeng, rus2eng
Requires at least: 6.0
Tested up to: 6.2
Stable tag: trunk

Is used to put the slugs from Russian into English.

== Description ==

Переводит русские slugs (postname) на английский с помощью переводчика или транслитом.

Похож на плагины [Cyr-To-Lat](http://wordpress.org/extend/plugins/cyr2lat/) и [rus-to-lat](http://wordpress.org/extend/plugins/rustolat/).

В отличие от оригинального плагина rus-to-lat, этот плагин может не только транслитерировать слаги постов и тегов, но переводит их с помощью переводчика.

== Installation ==

1. Загрузите папку плагина в `/wp-content/plugins/`.
1. Активируйте плагин в Wordpress.
1. В параметрах (Параметры -> Транслитерируй это!) выбрать способ транситерации и других настроек.


== Frequently Asked Questions ==

= Когда появится Google Translate =

Гугл сделал платным сервис перевода через API Google

= Ошибки при работе =

Плагин не работает если активирован rus-to-lat.

== Screenshots ==

screenshot-1.png

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

= 1.4b =
* Добавлена поддержка Wordpress 5
* Перестал работать перевод Яндекс Экспериментальный

= 1.5b =
* Исправлены ошибки

= 1.6 =
* Переписан код
* Добавлен Yandex Cloud

= 1.7 =
* Исправлен метод для совместимости c PHP8

== Upgrade Notice ==

= 1.7 =
* Исправлен метод для совместимости c PHP8

== ToDo ==
Планы на следующие версии:

1. Вынести файл настроек отдельно.
1. Изменить меню опций.
1. Добавить перевод файлов
<?php code(); // goes in backticks ?>
