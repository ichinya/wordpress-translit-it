<?php
/** Regression cases for bounded and non-recursive external requests. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'TRANSLIT_IT_TESTS' ) ) { exit( 1 ); }

update_option( 'rtl_standard', 'cloud_yandex' );
update_option( 'rtl_translate', true );
update_option( 'ya_api_key', 'outage-regression-key' );
$fixture = new WP_Error( 'http_request_failed', 'Fixture timeout' );
$before = count( $requests );
translit_test_same( 'pervaya oshibka', Translit_It::filter_title( 'Первая ошибка' ), 'First outage uses fallback' );
translit_test_same( 'vtoraya oshibka', Translit_It::filter_title( 'Вторая ошибка' ), 'Further titles use fallback after outage' );
translit_test_same( $before + 1, count( $requests ), 'Only one failed HTTP request for different titles' );
update_option( 'rtl_translate', false );
translit_test_same( 'Без резерва при отказе', Translit_It::filter_title( 'Без резерва при отказе' ), 'Outage still honors disabled fallback' );
translit_test_same( $before + 1, count( $requests ), 'Disabling fallback does not retry failed translator' );

// Changing credentials must allow a fresh attempt in the same PHP process.
update_option( 'rtl_translate', true );
update_option( 'ya_api_key', 'replacement-regression-key' );
$fixture = array( 'response' => array( 'code' => 200 ), 'body' => '{"translations":[{"text":"Recovered translation"}]}' );
translit_test_same( 'recovered-translation', sanitize_title( 'Сохранённый успешный перевод' ), 'New key recovers after old key failure' );
translit_test_same( $before + 2, count( $requests ), 'New key makes a fresh request' );
$fixture = new WP_Error( 'http_request_failed', 'Fixture outage after success' );
sanitize_title( 'Другой текст после успеха' );
$after_failure = count( $requests );
translit_test_same( 'recovered-translation', sanitize_title( 'Сохранённый успешный перевод' ), 'Successful response remains cached after later failure' );
translit_test_same( $after_failure, count( $requests ), 'Successful cached title needs no retry' );

// Failure is scoped to both the mode and key, rather than all translation.
update_option( 'rtl_standard', 'yandex' );
$fixture = array( 'response' => array( 'code' => 200 ), 'body' => '{"code":200,"text":["Legacy remains available"]}' );
translit_test_same( 'legacy-remains-available', sanitize_title( 'Другая служба перевода' ), 'Cloud failure does not disable the legacy mode' );
translit_test_same( $after_failure + 1, count( $requests ), 'Different mode may contact its own service' );

update_option( 'rtl_standard', 'cloud_yandex' );
update_option( 'ya_api_key', 'length-regression-key' );
$fixture = array( 'response' => array( 'code' => 200 ), 'body' => '{"translations":[{"text":"Valid length"}]}' );
$before = count( $requests );
$long_title = str_repeat( 'я', 10001 );
translit_test_same( str_repeat( 'ya', 10001 ), Translit_It::filter_title( $long_title ), 'Oversize title falls back locally' );
translit_test_same( $before, count( $requests ), 'Oversize title never contacts the API' );
update_option( 'rtl_translate', false );
translit_test_same( $long_title, Translit_It::filter_title( $long_title ), 'Oversize title honors disabled fallback' );
update_option( 'rtl_translate', true );
translit_test_same( 'valid-length', sanitize_title( str_repeat( 'я', 10000 ) ), 'Exact Unicode character limit is accepted' );
translit_test_same( $before + 1, count( $requests ), 'Rejected input does not disable the service' );

// A third-party HTTP filter may sanitize another title while logging a request.
update_option( 'ya_api_key', 'reentry-regression-key' );
$nested_calls = 0;
$nested_slug = '';
$reentry_hook = function ( $pre ) use ( &$nested_calls, &$nested_slug ) {
    ++$nested_calls;
    if ( 1 === $nested_calls ) { $nested_slug = sanitize_title( 'Вложенный заголовок' ); }
    return $pre;
};
add_filter( 'pre_http_request', $reentry_hook, 9 );
$before = count( $requests );
try {
    translit_test_same( 'valid-length', sanitize_title( 'Внешний заголовок' ), 'Outer translation succeeds during HTTP-hook reentry' );
    translit_test_same( 'vlojennyy-zagolovok', $nested_slug, 'Nested title uses local fallback' );
    translit_test_same( 1, $nested_calls, 'No recursive HTTP request' );
    translit_test_same( $before + 1, count( $requests ), 'Only outer title contacts service' );
} finally {
    remove_filter( 'pre_http_request', $reentry_hook, 9 );
}
translit_test_same( 'valid-length', sanitize_title( 'Следующий обычный заголовок' ), 'Guard resets after HTTP hook returns' );
translit_test_same( $before + 2, count( $requests ), 'Later valid title can still contact service' );
translit_test_same( 'valid-length', sanitize_title( 'Вложенный заголовок' ), 'Nested fallback was not cached as a failed translation' );
translit_test_same( $before + 3, count( $requests ), 'Previously nested title may translate normally later' );

// An exception from another HTTP plugin must not leave translation locked.
$throwing_hook = function () { throw new RuntimeException( 'Fixture HTTP hook exception' ); };
add_filter( 'pre_http_request', $throwing_hook, 9 );
$caught = false;
try {
    sanitize_title( 'Заголовок с исключением' );
} catch ( RuntimeException $error ) {
    $caught = 'Fixture HTTP hook exception' === $error->getMessage();
} finally {
    remove_filter( 'pre_http_request', $throwing_hook, 9 );
}
translit_test_same( true, $caught, 'Unrelated HTTP-hook exception is preserved' );
$before = count( $requests );
translit_test_same( 'valid-length', sanitize_title( 'Заголовок после исключения' ), 'Guard resets even when an HTTP hook throws' );
translit_test_same( $before + 1, count( $requests ), 'HTTP remains available after an exception' );
