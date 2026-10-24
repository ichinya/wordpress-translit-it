<?php
/** Run with wp eval-file inside an isolated WordPress installation. */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'TRANSLIT_IT_TESTS' ) ) {
    if ( 'cli' === PHP_SAPI ) { fwrite( STDERR, 'Run only in an isolated test installation with TRANSLIT_IT_TESTS=1.' . PHP_EOL ); }
    exit( 1 );
}

$GLOBALS['translit_test_checks'] = 0;
function translit_test_same( $expected, $actual, $label ) {
    ++$GLOBALS['translit_test_checks'];
    if ( $expected !== $actual ) {
        throw new RuntimeException( $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
    }
}
function translit_test_option( $name, $value ) {
    // Emulate pre-upgrade values, including unsafe data saved by the old plugin.
    $callback = array( 'Translit_It', array( 'rtl_standard' => 'sanitize_mode', 'rtl_translate' => 'sanitize_checkbox', 'ya_api_key' => 'sanitize_saved_key', 'folder_id' => 'sanitize_folder' )[ $name ] );
    remove_filter( 'sanitize_option_' . $name, $callback );
    update_option( $name, $value );
    add_filter( 'sanitize_option_' . $name, $callback );
}

Translit_It::register_settings();
$admin = get_user_by( 'login', 'translit_test_admin' );
if ( ! $admin ) {
    throw new RuntimeException( 'Create the isolated test administrator translit_test_admin first.' );
}
wp_set_current_user( $admin->ID );
$saved_options = array();
foreach ( array( 'rtl_standard', 'rtl_translate', 'ya_api_key', 'folder_id' ) as $option ) {
    $saved_options[ $option ] = get_option( $option, '__translit_test_missing__' );
}
register_shutdown_function( function () use ( $saved_options ) {
    foreach ( $saved_options as $option => $value ) {
        if ( '__translit_test_missing__' === $value ) {
            delete_option( $option );
        } else {
            translit_test_option( $option, $value );
        }
    }
} );

// Input validation, including malformed POST arrays and header injection.
foreach ( array( 'talk', 'iso', 'off', 'yandex', 'cloud_yandex' ) as $mode ) {
    translit_test_same( $mode, Translit_It::sanitize_mode( $mode ), 'Known mode' );
}
foreach ( array( array( 'talk' ), '<script>alert(1)</script>', null, 1 ) as $value ) {
    translit_test_same( 'off', Translit_It::sanitize_mode( $value ), 'Invalid mode' );
}
translit_test_same( false, Translit_It::sanitize_checkbox( array( 1 ) ), 'Array checkbox' );
translit_test_same( false, Translit_It::sanitize_checkbox( 'false' ), 'False checkbox string' );
translit_test_same( true, Translit_It::sanitize_checkbox( '1' ), 'Enabled checkbox' );
foreach ( array( array( 'key' ), "key\r\nInjected: value", '<script>evil</script>', str_repeat( 'a', 2049 ) ) as $key ) {
    translit_test_same( '', Translit_It::sanitize_key( $key ), 'Invalid key' );
}
translit_test_same( 'test-api.key_123:ok', Translit_It::sanitize_key( ' test-api.key_123:ok ' ), 'Valid opaque key' );
translit_test_same( '', Translit_It::sanitize_folder( array( 'folder' ) ), 'Array folder' );
translit_test_same( '', Translit_It::sanitize_folder( '<script>evil</script>' ), 'HTML folder' );

// The new UI does not need to receive a saved secret to preserve it.
update_option( 'ya_api_key', 'saved-fixture-secret' );
update_option( 'ya_api_key', array( 'value' => '' ) );
translit_test_same( 'saved-fixture-secret', get_option( 'ya_api_key' ), 'Blank replacement preserves saved key' );
update_option( 'ya_api_key', array( 'value' => 'replacement-fixture-secret' ) );
translit_test_same( 'replacement-fixture-secret', get_option( 'ya_api_key' ), 'Structured replacement saves new key' );
update_option( 'ya_api_key', array( 'value' => '<script>invalid</script>' ) );
translit_test_same( 'replacement-fixture-secret', get_option( 'ya_api_key' ), 'Invalid replacement preserves saved key' );
ob_start();
Translit_It::render_settings();
$secret_html = ob_get_clean();
translit_test_same( false, strpos( $secret_html, 'replacement-fixture-secret' ) !== false, 'Saved secret is absent from settings HTML' );
translit_test_same( true, strpos( $secret_html, 'name="ya_api_key[clear]"' ) !== false, 'Saved key has an explicit deletion control' );
update_option( 'ya_api_key', array( 'value' => '', 'clear' => '1' ) );
translit_test_same( '', get_option( 'ya_api_key' ), 'Explicit deletion removes saved key' );

// Frozen compatibility fixtures retain the original 1.8.0 transliteration tables.
$original = json_decode( file_get_contents( __DIR__ . '/fixtures/transliteration.json' ), true );
foreach ( array( 'talk', 'iso' ) as $mode ) {
    $old_map = $original[ $mode ];
    $new_map = 'talk' === $mode ? translit_it_get_talk_array() : translit_it_get_iso_array();
    translit_test_same( $old_map, $new_map, $mode . ' original mapping unchanged' );
    $text = 'ЁЖИК Щука № 7 — «ЧАЙ» и подъезд';
    translit_test_same( strtr( mb_strtolower( $text, 'UTF-8' ), $old_map ), translit_it_convert( $text, $mode ), $mode . ' uppercase handling' );
}

// Real WordPress publishing behavior and slug uniqueness.
update_option( 'rtl_standard', 'talk' );
translit_test_same( 'privet-mir', sanitize_title( 'Привет мир' ), 'Conversational slug' );
update_option( 'rtl_standard', 'iso' );
translit_test_same( 'yozhik', sanitize_title( 'Ёжик' ), 'ISO slug' );
update_option( 'rtl_standard', 'talk' );
$one = wp_insert_post( array( 'post_title' => 'Привет мир', 'post_status' => 'publish' ), true );
$two = wp_insert_post( array( 'post_title' => 'Привет мир', 'post_status' => 'publish' ), true );
translit_test_same( false, is_wp_error( $one ), 'Create first post' );
translit_test_same( 'privet-mir', get_post_field( 'post_name', $one ), 'New post slug' );
translit_test_same( 'privet-mir-2', get_post_field( 'post_name', $two ), 'Duplicate slug handled by core' );
wp_update_post( array( 'ID' => $one, 'post_title' => 'Новое название' ) );
translit_test_same( 'privet-mir', get_post_field( 'post_name', $one ), 'Existing URL retained after title edit' );
$manual = wp_insert_post( array( 'post_title' => 'Ручной адрес', 'post_name' => 'my-custom-url', 'post_status' => 'publish' ), true );
translit_test_same( 'my-custom-url', get_post_field( 'post_name', $manual ), 'Manual Latin slug retained' );
$page = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'Новая страница', 'post_status' => 'publish' ), true );
translit_test_same( 'novaya-stranica', get_post_field( 'post_name', $page ), 'Page slug' );
$term = wp_insert_term( 'Новая рубрика', 'category' );
translit_test_same( false, is_wp_error( $term ), 'Category creation' );
translit_test_same( 'novaya-rubrika', get_term( $term['term_id'] )->slug, 'Category slug' );

// All HTTP is intercepted; no API credentials or unpublished titles leave the test.
$requests = array();
$fixture  = array( 'response' => array( 'code' => 200 ), 'body' => '{"translations":[{"text":"Hello world"}]}' );
add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$requests, &$fixture ) {
    $requests[] = array( 'url' => $url, 'args' => $args );
    return $fixture;
}, 10, 3 );
update_option( 'rtl_standard', 'cloud_yandex' );
update_option( 'ya_api_key', 'fixture-not-a-real-key' );
update_option( 'folder_id', 'old-folder-retained' );
update_option( 'rtl_translate', true );
translit_test_same( 'hello-world', sanitize_title( 'Тест облака' ), 'Cloud success' );
$request = $requests[0];
translit_test_same( 'https://translate.api.cloud.yandex.net/translate/v2/translate', $request['url'], 'Fixed trusted cloud endpoint' );
translit_test_same( 5, $request['args']['timeout'], 'Bounded timeout' );
translit_test_same( 0, $request['args']['redirection'], 'No credential redirects' );
translit_test_same( true, $request['args']['sslverify'], 'TLS verification retained' );
translit_test_same( 65536, $request['args']['limit_response_size'], 'Bounded response' );
$body = json_decode( $request['args']['body'], true );
translit_test_same( array( 'Тест облака' ), $body['texts'], 'Correct cloud text' );
translit_test_same( false, isset( $body['folderId'] ) || isset( $body['folderID'] ), 'Service account omits folder' );
translit_test_same( 'old-folder-retained', get_option( 'folder_id' ), 'Legacy folder not deleted' );
$count = count( $requests );
sanitize_title( 'Тест облака' );
translit_test_same( $count, count( $requests ), 'Request-local cache' );
Translit_It::filter_title( 'Поиск', 'Поиск', 'query' );
sanitize_title( 'already-latin-slug' );
translit_test_same( $count, count( $requests ), 'No translation for lookup or Latin text' );

$cases = array(
    new WP_Error( 'http_request_failed', 'Fixture timeout' ),
    array( 'response' => array( 'code' => 401 ), 'body' => '{}' ),
    array( 'response' => array( 'code' => 302 ), 'body' => '{}' ),
    array( 'response' => array( 'code' => 200 ), 'body' => 'not JSON' ),
    array( 'response' => array( 'code' => 200 ), 'body' => '{"translations":[{"text":[]}]}' ),
    array( 'response' => array( 'code' => 200 ), 'body' => '{"translations":"invalid"}' ),
    array( 'response' => array( 'code' => 200 ), 'body' => '{"translations":[{"text":" "}]}' ),
);
foreach ( $cases as $i => $case ) {
    update_option( 'ya_api_key', 'invalid-response-fixture-key-' . $i );
    $fixture = $case;
    $title   = 'Ошибка перевода ' . $i;
    translit_test_same( translit_it_convert( $title ), Translit_It::filter_title( $title ), 'Fallback after malformed/error response ' . $i );
}
update_option( 'rtl_translate', false );
$fixture = new WP_Error( 'http_request_failed', 'Timeout' );
translit_test_same( 'Без резерва', Translit_It::filter_title( 'Без резерва' ), 'Fallback off preserves source' );
update_option( 'ya_api_key', '' );
$count = count( $requests );
translit_test_same( 'Без ключа', Translit_It::filter_title( 'Без ключа' ), 'No key without fallback' );
update_option( 'rtl_translate', true );
translit_test_same( 'bez klyucha', Translit_It::filter_title( 'Без ключа' ), 'No key with fallback' );
translit_test_same( $count, count( $requests ), 'Missing key causes no request' );

update_option( 'rtl_standard', 'yandex' );
update_option( 'ya_api_key', 'legacy-fixture-key' );
$fixture = array( 'response' => array( 'code' => 200 ), 'body' => '{"code":200,"text":["Legacy response"]}' );
translit_test_same( 'legacy-response', sanitize_title( 'Старый переводчик' ), 'Legacy API success' );
$fixture = array( 'response' => array( 'code' => 200 ), 'body' => '{"code":401,"text":["Must not be used"]}' );
translit_test_same( 'nevernyy kod', Translit_It::filter_title( 'Неверный код' ), 'Legacy code checked correctly' );

// Old malicious data must remain inert when the settings page is displayed.
translit_test_option( 'ya_api_key', '\"><script>alert(1)</script>' );
translit_test_option( 'folder_id', '\"><img src=x onerror=alert(2)>' );
ob_start();
Translit_It::render_settings();
$html = ob_get_clean();
translit_test_same( false, strpos( $html, '<script>alert(1)</script>' ) !== false, 'Stored key XSS escaped' );
translit_test_same( false, strpos( $html, '<img src=x' ) !== false, 'Stored folder XSS escaped' );
translit_test_same( false, strpos( $html, 'alert(1)' ) !== false, 'Legacy malicious key is never returned in HTML' );
translit_test_same( true, strpos( $html, 'name="_wpnonce"' ) !== false, 'Nonce in form' );
translit_test_same( true, strpos( $html, 'action="options.php"' ) !== false, 'Core settings handler' );
$count = count( $requests );
Translit_It::filter_title( 'Небезопасный ключ' );
translit_test_same( $count, count( $requests ), 'Legacy unsafe key never sent' );

// Leave the isolated site ready for authenticated HTTP security regression tests.
update_option( 'rtl_standard', 'talk' );
update_option( 'rtl_translate', true );
update_option( 'ya_api_key', '' );
update_option( 'folder_id', '' );
wp_delete_post( $one, true );
wp_delete_post( $two, true );
wp_delete_post( $manual, true );
wp_delete_post( $page, true );
wp_delete_term( $term['term_id'], 'category' );
require __DIR__ . '/rest.php';
require __DIR__ . '/translation-failures.php';
echo 'PASS: ' . $GLOBALS['translit_test_checks'] . ' WordPress integration checks on ' . get_bloginfo( 'version' ) . ' / PHP ' . PHP_VERSION . PHP_EOL;
