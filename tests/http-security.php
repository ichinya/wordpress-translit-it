<?php
/** Exercise core's real settings handler against a loopback-only test server. */
if ( 'cli' !== PHP_SAPI || '1' !== getenv( 'TRANSLIT_IT_TESTS' ) ) {
    if ( 'cli' === PHP_SAPI ) { fwrite( STDERR, 'Run only in an isolated test installation with TRANSLIT_IT_TESTS=1.' . PHP_EOL ); }
    exit( 1 );
}
$base = 'http://127.0.0.1:18947';
$cookie = tempnam( sys_get_temp_dir(), 'translit-cookies-' );
$checks = 0;
function request_test( $path, $fields = null ) {
    global $base, $cookie;
    $curl = curl_init( $base . $path );
    curl_setopt_array( $curl, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
    ) );
    if ( null !== $fields ) {
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query( $fields ) );
    }
    $body = curl_exec( $curl );
    if ( false === $body ) {
        throw new RuntimeException( curl_error( $curl ) );
    }
    $status = curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );
    return array( $status, $body );
}
function check_test( $condition, $label ) {
    global $checks;
    ++$checks;
    if ( ! $condition ) {
        throw new RuntimeException( $label );
    }
}
function login_test( $user ) {
    global $cookie;
    file_put_contents( $cookie, '' );
    request_test( '/wp-login.php' );
    return request_test( '/wp-login.php', array( 'log' => $user, 'pwd' => 'fixture-only-isolated-admin-20261024', 'wp-submit' => 'Log In', 'testcookie' => '1', 'redirect_to' => '/wp-admin/' ) );
}
function nonce_test( $body ) {
    preg_match( '/name="_wpnonce" value="([^"]+)"/', $body, $match );
    return $match[1] ?? '';
}
$page = '/wp-admin/options-general.php?page=translit-it/index.php';
try {
    login_test( 'translit_test_admin' );
    list( $status, $html ) = request_test( $page );
    check_test( 200 === $status && strpos( $html, 'name="rtl_standard"' ) !== false, 'Administrator can open settings' );
    $nonce = nonce_test( $html );
    check_test( '' !== $nonce, 'Administrator receives a real nonce' );
    $fields = array( 'option_page' => 'translit_it_settings', 'action' => 'update', 'rtl_standard' => 'iso', 'rtl_translate' => '1', 'ya_api_key' => '\"><script>alert(1)</script>', 'folder_id' => '\"><img src=x onerror=alert(2)>' );

    list( $status ) = request_test( '/wp-admin/options.php', $fields );
    check_test( 403 === $status, 'Settings POST without nonce is rejected' );
    list( , $html ) = request_test( $page );
    check_test( strpos( $html, 'value="talk"  selected=' ) !== false, 'CSRF did not change stored mode' );
    $fields['_wpnonce'] = 'invalid-fixture-nonce';
    list( $status ) = request_test( '/wp-admin/options.php', $fields );
    check_test( 403 === $status, 'Invalid nonce is rejected' );

    list( , $html ) = request_test( $page, array( 'rtl_standard' => 'iso', 'ya_api_key' => 'legacy-attack' ) );
    check_test( strpos( $html, 'value="talk"  selected=' ) !== false, 'Old vulnerable settings URL no longer saves POST data' );

    $fields['_wpnonce'] = $nonce;
    list( $status ) = request_test( '/wp-admin/options.php', $fields );
    check_test( 200 === $status, 'Valid administrator save completes' );
    list( , $html ) = request_test( $page );
    check_test( strpos( $html, 'value="iso"  selected=' ) !== false, 'Valid save persists selected mode' );
    check_test( strpos( $html, '<script>alert(1)</script>' ) === false && strpos( $html, '<img src=x' ) === false, 'Saved payloads do not execute as HTML' );
    check_test( strpos( $html, 'name="ya_api_key" type="password" class="regular-text" autocomplete="new-password" value=""' ) !== false, 'Invalid key is rejected during real settings save' );
    $admin_nonce = nonce_test( $html );
    $fields = array( 'option_page' => 'translit_it_settings', 'action' => 'update', '_wpnonce' => $admin_nonce, 'rtl_standard' => 'talk', 'ya_api_key' => '', 'folder_id' => '' );
    list( $status ) = request_test( '/wp-admin/options.php', $fields );
    check_test( 200 === $status, 'Saving with unchecked checkbox succeeds' );
    list( , $html ) = request_test( $page );
    check_test( ! preg_match( '/name="rtl_translate"[^>]*checked=/', $html ), 'Unchecked fallback is saved as disabled' );

    login_test( 'translit_test_subscriber' );
    list( $status ) = request_test( $page );
    check_test( 403 === $status, 'Subscriber cannot read API-key settings' );
    $fields['rtl_standard'] = 'cloud_yandex';
    list( $status ) = request_test( '/wp-admin/options.php', $fields );
    check_test( 403 === $status, 'Subscriber cannot save settings with an administrator nonce' );
    login_test( 'translit_test_admin' );
    list( , $html ) = request_test( $page );
    check_test( strpos( $html, 'value="talk"  selected=' ) !== false, 'Subscriber request did not change stored mode' );
    echo 'PASS: ' . $checks . ' authenticated HTTP security checks' . PHP_EOL;
} finally {
    unlink( $cookie );
}
