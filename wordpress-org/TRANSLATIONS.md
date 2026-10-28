The plugin uses English gettext source messages and the `translit-it` text domain. The Russian catalog is bundled in `languages/translit-it-ru_RU.po` and `languages/translit-it-ru_RU.mo`, so Russian administrators do not need to wait for a WordPress.org language pack.

Rebuild the template and binary catalog with WP-CLI:

```powershell
wp i18n make-pot . languages/translit-it.pot --domain=translit-it --exclude=tests,tools,wordpress-org --skip-js
wp i18n make-mo languages
```

Keep the source PO file synchronized with the template before rebuilding. The compatibility workflow installs the Russian WordPress locale and tests both Russian and English settings screens from the actual release ZIP.

To contribute the Russian runtime translation, import `languages/translit-it-ru_RU.po` into the Russian Development and Stable translation sets, once the new strings have been imported by WordPress.org:

https://translate.wordpress.org/projects/wp-plugins/translit-it/

The readme has separate translation sets. Their PO file is `wordpress-org/translit-it-readme-ru.po`.

Import requires signing in with a WordPress.org account. Suggested translations need review by the Russian translation editors unless the contributor has approval rights for this plugin. Uploading a catalog to SVN does not mark it as approved in GlotPress or remove the WordPress.org translation notice by itself.

The Russian sample title in the settings screen is input data rather than a translated UI message; it deliberately stays Russian in every locale. JavaScript receives localized labels from escaped HTML data attributes and makes no translation-service calls.
