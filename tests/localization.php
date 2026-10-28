<?php
/** Exercise bundled catalogs and real WordPress locale switching. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'TRANSLIT_IT_TESTS' ) ) { exit( 1 ); }

require_once ABSPATH . WPINC . '/pomo/po.php';
require_once ABSPATH . WPINC . '/pomo/mo.php';
$pot = new PO();
$po = new PO();
$mo = new MO();
$languages = WP_PLUGIN_DIR . '/translit-it/languages/';
translit_test_same( '/installed-language-pack/', Translit_It::bundled_translation_path( '/installed-language-pack/', 'translit-it', 'ru_RU' ), 'Installed language packs take priority' );
translit_test_same( false, Translit_It::bundled_translation_path( false, 'other-plugin', 'ru_RU' ), 'Other text domains are unchanged' );
translit_test_same( false, Translit_It::bundled_translation_path( false, 'translit-it', 'en_US' ), 'English uses its source messages' );
translit_test_same( true, $pot->import_from_file( $languages . 'translit-it.pot' ), 'Translation template is packaged' );
translit_test_same( true, $po->import_from_file( $languages . 'translit-it-ru_RU.po' ), 'Russian translation source is packaged' );
translit_test_same( true, $mo->import_from_file( $languages . 'translit-it-ru_RU.mo' ), 'Compiled Russian catalog is packaged' );
$missing = array();
foreach ( $pot->entries as $entry ) {
    $key = $entry->key();
    if ( ! isset( $po->entries[ $key ] ) || empty( $po->entries[ $key ]->translations[0] ) || $mo->translate( $entry->singular ) !== $po->entries[ $key ]->translations[0] ) {
        $missing[] = $entry->singular;
    }
}
translit_test_same( array(), $missing, 'Every source string has a matching compiled Russian translation' );

$locale_options = array();
foreach ( array( 'rtl_standard', 'rtl_translate', 'ya_api_key', 'folder_id' ) as $option ) {
    $locale_options[ $option ] = get_option( $option );
}
foreach ( array(
    'ru_RU' => array( 'Транслитерация адресов', 'Сохранить настройки', 'Показать', 'Скрыть', 'Есть несохранённые изменения', 'Транслитерируй это!' ),
    'en_US' => array( 'Slug transliteration', 'Save settings', 'Show', 'Hide', 'You have unsaved changes', 'Translit it!' ),
) as $locale => $expected ) {
    $switched = switch_to_locale( $locale );
    translit_test_same( $locale, get_locale(), $locale . ' locale is available' );
    try {
        ob_start();
        Translit_It::render_settings();
        $locale_html = ob_get_clean();
        translit_test_same( true, str_contains( $locale_html, '<h1>' . $expected[0] . '</h1>' ), $locale . ' settings heading' );
        translit_test_same( true, str_contains( $locale_html, 'value="' . $expected[1] . '"' ), $locale . ' submit button' );
        translit_test_same( true, str_contains( $locale_html, 'data-show-label="' . $expected[2] . '"' ) && str_contains( $locale_html, 'data-hide-label="' . $expected[3] . '"' ), $locale . ' JavaScript toggle labels' );
        translit_test_same( true, str_contains( $locale_html, 'data-dirty-label="' . $expected[4] . '"' ), $locale . ' JavaScript save status' );
        translit_test_same( $expected[5], __( 'Translit it!', 'translit-it' ), $locale . ' menu label' );
        translit_test_same( true, str_contains( $locale_html, 'Ёжик у озера' ) && str_contains( $locale_html, '/ejik-u-ozera/' ) && str_contains( $locale_html, '/yozhik-u-ozera/' ), $locale . ' Russian sample and slug tables stay unchanged' );
    } finally {
        if ( $switched ) { restore_previous_locale(); }
    }
}
foreach ( $locale_options as $option => $expected_value ) {
    translit_test_same( $expected_value, get_option( $option ), 'Locale switching preserves ' . $option );
}
