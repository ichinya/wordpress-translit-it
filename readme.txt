=== translit it! ===
Contributors: Ichi-nya
Donate link: https://sobe.ru/na/translit_it
Tags: transliteration, slugs, russian, translation, permalinks
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transliterate Russian slugs locally, or translate them into English with Yandex Translate.

== Description ==

translit it! creates Latin slugs for Russian post titles, pages and taxonomy terms.

Choose between the original conversational and ISO-style transliteration tables, or an optional Yandex translation service. The original transliteration tables and option names are retained for compatibility with existing installations.

Local transliteration does not require an API key, does not send data to any external service and works without a network connection.

The plugin does not run a bulk rewrite of existing permalinks. WordPress still handles slug cleanup and uniqueness. Explicitly changing or regenerating a slug can change its URL, as usual.

Settings are available under Settings > Транслитерируй это!.

= External services =

External translation is optional and disabled by default. It is used only when an administrator selects a Yandex translation mode and supplies an API key. When WordPress generates a slug in a save context and the source contains Cyrillic, that source text is sent to the selected Yandex service. Depending on the WordPress operation, this may include unpublished post titles or taxonomy names. Already Latin text does not trigger a translation request.

* Yandex Cloud Translate: requests are sent to https://translate.api.cloud.yandex.net/translate/v2/translate with the source text, Russian/English language codes and the configured API key. Use a service-account API key with the ai.translate.user role; its folder is selected by Yandex automatically. A paid Yandex account or usage charges may be required. Documentation: https://yandex.cloud/en/docs/translate/ . Terms: https://yandex.com/legal/cloud_terms/ . Privacy policy: https://yandex.com/legal/confidential/ .
* Legacy Yandex Translate API 1.5: retained for existing settings and keys. Requests are sent to https://translate.yandex.net/api/v1.5/tr.json/translate with the source text, language pair and configured API key. Availability of this old service is not guaranteed; use local transliteration or Yandex Cloud for a new setup. Terms: https://yandex.com/legal/translate_api/ . Privacy policy: https://yandex.com/legal/confidential/ .

Requests have a five-second timeout and do not follow redirects. Enable fallback transliteration to generate a Latin slug when translation fails. Without fallback, WordPress receives the original text. Translation responses are reused only within the current PHP request; the plugin does not persist a translation cache or log API keys.

== Installation ==

1. Upload the translit-it folder to /wp-content/plugins/ or install the plugin from WordPress.org when available.
2. Activate translit it!.
3. Open Settings > Транслитерируй это! and choose a conversion mode.
4. For local transliteration, no external account is needed. For Yandex Cloud, configure your service-account API key.

== Frequently Asked Questions ==

= Will upgrading change my existing URLs? =

There is no bulk conversion or database migration of existing slugs. The plugin converts text only when WordPress asks it to generate a slug for saving. Back up your site and check your normal publishing workflow before upgrading.

= Can I use this together with another slug transliteration plugin? =

Use only one slug conversion plugin at a time to avoid conflicting filters.

= Is the ISO mode a full implementation of ISO 9? =

It retains the historical ASCII table from this plugin for URL compatibility; it is not a complete implementation of the ISO 9 standard.

= Why does the legacy folder identifier remain visible? =

Its stored value is preserved during upgrades. Yandex Cloud API keys use their service account's folder, so the legacy identifier is not sent in API-key requests.

== Changelog ==

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

= 1.8.0 =
Security update addressing CSRF and stored XSS in settings. Existing option names and transliteration tables are retained. Check your selected translation mode and fallback setting after updating.
