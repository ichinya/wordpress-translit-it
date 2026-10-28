=== translit it! ===
Contributors: Ichi-nya
Donate link: https://sobe.ru/na/translit_it
Tags: transliteration, slugs, russian, translation, permalinks
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.4
Stable tag: 1.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transliterate Russian slugs locally, or translate them into English with Yandex Translate.

== Description ==

translit it! creates Latin slugs for Russian post titles, pages and taxonomy terms.

Choose between the original conversational and ISO-style transliteration tables, or an optional Yandex translation service. The original transliteration tables and option names are retained for compatibility with existing installations.

Local transliteration does not require an API key, does not send data to any external service and works without a network connection.

The plugin does not run a bulk rewrite of existing permalinks. WordPress still handles slug cleanup and uniqueness. Explicitly changing or regenerating a slug can change its URL, as usual.

Settings are available under Settings > Транслитерируй это!.

The settings screen includes conversion-mode cards, a local slug example and a separate Yandex connection section. Saved API keys are never sent back to the page: leave the replacement field blank to keep your key, or explicitly select its deletion. Legacy options remain available in a collapsible section. The form also works without JavaScript.

= Minimum requirements =

WordPress 7.1 or later and PHP 8.4 or later are required. PHP 8.5 is also tested.

= External services =

External translation is optional and disabled by default. It is used only when an administrator selects a Yandex translation mode and supplies an API key. When WordPress generates a slug in a save context and the source contains Cyrillic, that source text is sent to the selected Yandex service. Depending on the WordPress operation, this may include unpublished post titles or taxonomy names. Already Latin text does not trigger a translation request.

* Yandex Cloud Translate: requests are sent to https://translate.api.cloud.yandex.net/translate/v2/translate with the source text, Russian/English language codes and the configured API key. Use a service-account API key with the ai.translate.user role; its folder is selected by Yandex automatically. A paid Yandex account or usage charges may be required. Documentation: https://yandex.cloud/en/docs/translate/ . Terms: https://yandex.com/legal/cloud_terms/ . Privacy policy: https://yandex.com/legal/confidential/ .
* Legacy Yandex Translate API 1.5: retained for existing settings and keys. Requests are sent to https://translate.yandex.net/api/v1.5/tr.json/translate with the source text, language pair and configured API key. Availability of this old service is not guaranteed; use local transliteration or Yandex Cloud for a new setup. Terms: https://yandex.com/legal/translate_api/ . Privacy policy: https://yandex.com/legal/confidential/ .

Requests have a five-second timeout and do not follow redirects. Enable fallback transliteration to generate a Latin slug when translation fails. Without fallback, WordPress receives the original text. Translation responses are reused only within the current PHP request; the plugin does not persist a translation cache or log API keys.

After the first failed response from a translation mode and API key, further uncached translations using that configuration are skipped until the next PHP request. Previously successful responses remain available. This avoids repeated timeout delays during bulk operations. Nested uncached translations from HTTP hooks and titles longer than 10,000 Unicode characters also use the configured fallback without an external call.

== Installation ==

1. Install translit it! from the WordPress.org plugin directory, or upload the translit-it folder to /wp-content/plugins/.
2. Activate translit it!.
3. Open Settings > Транслитерируй это! and choose a conversion mode.
4. For local transliteration, no external account is needed. For Yandex Cloud, configure your service-account API key.

== Frequently Asked Questions ==

= How do I update from version 1.6? =

First make sure your site runs WordPress 7.1 or later and PHP 8.4 or later, and back up your files and database. Open Plugins > Installed Plugins and use Update now for translit it!. You do not need to uninstall the old version.

The selected conversion mode, fallback checkbox, API key and legacy folder identifier are retained. Open Settings > Транслитерируй это! after updating to check them. Leave the API-key replacement field blank to keep the saved key.

Direct updates from 1.6 to 1.9.0 were checked through the WordPress admin on WordPress 7.1.3 / PHP 8.4 with MySQL 8.4 and, separately, SQLite. The checks covered Yandex Cloud with fallback enabled and legacy Yandex API 1.5 with fallback disabled, including saving the new form without re-entering the key. Stored options and existing post, page and category slugs were preserved. These checks used fictional keys and did not verify live Yandex translation.

= Why is the API-key field empty after updating? =

This is intentional: the saved key is not returned to the browser. The "Ключ сохранён" status means a valid key is already stored. Leave the replacement field blank to keep it, enter a new key to replace it, or select "Удалить сохранённый ключ" to remove it explicitly.

= Will upgrading change my existing URLs? =

There is no bulk conversion or database migration of existing slugs. The plugin converts text only when WordPress asks it to generate a slug for saving. Back up your site and check your normal publishing workflow before upgrading.

= Can I use this together with another slug transliteration plugin? =

Use only one slug conversion plugin at a time to avoid conflicting filters.

= Is the ISO mode a full implementation of ISO 9? =

It retains the historical ASCII table from this plugin for URL compatibility; it is not a complete implementation of the ISO 9 standard.

= Why does the legacy folder identifier remain visible? =

Its stored value is preserved during upgrades. Yandex Cloud API keys use their service account's folder, so the legacy identifier is not sent in API-key requests.

== Screenshots ==

1. Local transliteration settings with conversion-mode cards and a sample address. No API key or external account is required.
2. Optional Yandex Cloud connection with saved-key status, a blank replacement field and fallback transliteration.
3. Legacy Yandex Translate API 1.5 settings and the preserved folder identifier in the compatibility section.

== Changelog ==

= 1.9.0 =
* Redesign the settings screen with accessible mode cards, conditional Yandex settings, a local permalink example and a responsive layout.
* Stop exposing saved API keys in HTML; support blank-field preservation, validated replacement and explicit deletion.
* Keep legacy settings in a collapsible section and load screen assets only on the plugin settings page.
* Include the settings template and runtime CSS/JavaScript in installation packages.

= 1.8.3 =
* Avoid repeated translation requests after a service failure within the same PHP request, while retaining successful cached responses.
* Prevent recursive translation calls triggered by third-party HTTP hooks.
* Check the 10,000-character input limit before sending a translation request.
* Add regressions for outages, HTTP-hook reentry, input length and API-key changes; keep the existing slug tables and fallback setting.

= 1.8.2 =
* Set the minimum supported versions to WordPress 7.1 and PHP 8.4.
* Align the plugin header, readme and automated compatibility matrix with these requirements; keep PHP 8.5 coverage.

= 1.8.1 =
* Confirm compatibility with WordPress 7.1.3, including block-editor REST draft previews, publishing, autosaves, pages, categories and explicit slugs.
* Add reproducible integration and authenticated settings-security tests, with automated WordPress/PHP compatibility checks.
* Keep API keys outside the REST settings endpoint and verify fallback publishing when the translation service is unavailable.
* Preserve the existing transliteration tables, settings and publishing behavior.

= 1.8.0 =
* Security: replace direct settings writes with the WordPress Settings API, capability checks and nonce protection; validate inputs and escape output. Addresses the CSRF/stored XSS issue reported as CVE-2025-39416.
* Preserve the plugin entry file, original option names and transliteration tables.
* Use the WordPress HTTP API with timeouts, redirect protection and validation of translation responses.
* Correct Yandex Cloud service-account request parameters and the legacy API status check.
* Avoid external translation in query contexts and for already Latin slugs.
* Make fallback behavior consistent when credentials are missing or translation fails.
* Prefix plugin symbols and remove the hard dependency on mbstring.
* Update metadata and document optional external services and privacy implications.

= 1.7 =
* Historical changelog: PHP 8 compatibility adjustment. The old package still declared version 1.6.

= 1.6 =
* Reworked the code and added Yandex Cloud.

= 1.5b =
* Bug fixes.

= 1.4b =
* Added WordPress 5 support.

== Upgrade Notice ==

= 1.9.0 =
New settings page. Direct updates from 1.6 retain existing options. A blank API-key field keeps the saved key; use the explicit deletion checkbox to remove it. WordPress 7.1+ and PHP 8.4+ are required.

= 1.8.3 =
Reduces repeated waits when translation is unavailable and prevents nested translation calls. WordPress 7.1+ and PHP 8.4+ remain required.

= 1.8.2 =
This release requires WordPress 7.1 or later and PHP 8.4 or later. PHP 8.5 is also supported.

= 1.8.1 =
WordPress 7.1 compatibility verification with automated editor and settings-security regression coverage. Existing settings and slug conversion behavior are retained.

= 1.8.0 =
Security update addressing CSRF and stored XSS in settings. Existing option names and transliteration tables are retained. Check your selected translation mode and fallback setting after updating.
