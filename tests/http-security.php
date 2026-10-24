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
function selected_mode_test( $body ) {
    preg_match_all( '/<input\b[^>]*>/i', $body, $inputs );
    foreach ( $inputs[0] as $input ) {
        if ( strpos( $input, 'name="rtl_standard"' ) !== false && preg_match( '/\schecked(?:=|\s|>)/i', $input ) && preg_match( '/value="([^"]*)"/', $input, $match ) ) {
            return $match[1];
        }
    }
    return '';
}
function empty_key_test( $body ) {
    return (bool) preg_match( '/<input\b(?=[^>]*id="ya_api_key")(?=[^>]*value="")[^>]*>/i', $body );
}
$page = '/wp-admin/options-general.php?page=translit-it/index.php';
try {
    login_test( 'translit_test_admin' );
    list( $status, $html ) = request_test( $page );
    check_test( 200 === $status && strpos( $html, 'name="rtl_standard"' ) !== false, 'Administrator can open settings' );
    $nonce = nonce_test( $html );
    check_test( '' !== $nonce, 'Administrator receives a real nonce' );
    $fields = array( 'option_page' => 'translit_it_settings', 'action' => 'update', '_wp_http_referer' => $page, 'rtl_standard' => 'iso', 'rtl_translate' => '1', 'ya_api_key' => '\"><script>alert(1)</script>', 'folder_id' => '\"><img src=x onerror=alert(2)>' );

    list( $status ) = request_test( '/wp-admin/options.php', $fields );
    check_test( 403 === $status, 'Settings POST without nonce is rejected' );
    list( , $html ) = request_test( $page );
    check_test( 'talk' === selected_mode_test( $html ), 'CSRF did not change stored mode' );
    $fields['_wpnonce'] = 'invalid-fixture-nonce';
    list( $status ) = request_test( '/wp-admin/options.php', $fields );
    check_test( 403 === $status, 'Invalid nonce is rejected' );

    list( , $html ) = request_test( $page, array( 'rtl_standard' => 'iso', 'ya_api_key' => 'legacy-attack' ) );
    check_test( 'talk' === selected_mode_test( $html ), 'Old vulnerable settings URL no longer saves POST data' );

    $fields['_wpnonce'] = $nonce;
    list( $status, $saved_html ) = request_test( '/wp-admin/options.php', $fields );
    check_test( 200 === $status, 'Valid administrator save completes' );
    check_test( 1 === substr_count( $saved_html, 'setting-error-settings_updated' ), 'Successful save shows exactly one notice' );
    list( , $html ) = request_test( $page );
    check_test( 'iso' === selected_mode_test( $html ), 'Valid save persists selected mode' );
    check_test( strpos( $html, '<script>alert(1)</script>' ) === false && strpos( $html, '<img src=x' ) === false, 'Saved payloads do not execute as HTML' );
    check_test( empty_key_test( $html ), 'API key field never reveals the stored value' );

    $fields = array( 'option_page' => 'translit_it_settings', 'action' => 'update', '_wpnonce' => nonce_test( $html ), 'rtl_standard' => 'cloud_yandex', 'rtl_translate' => '1', 'ya_api_key' => array( 'value' => 'fixture-hidden-saved-key' ), 'folder_id' => '' );
    list( $status ) = request_test( '/wp-admin/options.php', $fields );
    check_test( 200 === $status, 'Structured API key save succeeds' );
    list( , $html ) = request_test( $page );
    check_test( 'cloud_yandex' === selected_mode_test( $html ), 'Yandex mode is saved' );
    check_test( strpos( $html, 'data-key-configured="1"' ) !== false, 'Saved key status is shown' );
    check_test( strpos( $html, 'fixture-hidden-saved-key' ) === false && empty_key_test( $html ), 'Saved secret never appears in response HTML' );
    check_test( strpos( $html, '/translit-it/assets/admin.css' ) !== false && strpos( $html, '/translit-it/assets/admin.js' ) !== false, 'Settings screen loads its packaged assets' );
    list( , $dashboard ) = request_test( '/wp-admin/index.php' );
    check_test( strpos( $dashboard, '/translit-it/assets/admin.' ) === false, 'Settings assets are not loaded on other admin pages' );

    $fields['_wpnonce'] = nonce_test( $html );
    $fields['rtl_standard'] = 'talk';
    $fields['ya_api_key'] = array( 'value' => '' );
    list( $status ) = request_test( '/wp-admin/options.php', $fields );
    list( , $html ) = request_test( $page );
    check_test( 200 === $status && strpos( $html, 'data-key-configured="1"' ) !== false, 'Switching to local mode preserves the saved secret' );

    $fields['_wpnonce'] = nonce_test( $html );
    $fields['ya_api_key'] = array( 'value' => '<script>bad-key</script>' );
    request_test( '/wp-admin/options.php', $fields );
    list( , $html ) = request_test( $page );
    check_test( strpos( $html, 'data-key-configured="1"' ) !== false && strpos( $html, '<script>bad-key</script>' ) === false, 'Invalid replacement keeps the old secret and escapes errors' );

    $fields['_wpnonce'] = nonce_test( $html );
    $fields['ya_api_key'] = array( 'clear' => '1', 'value' => '' );
    request_test( '/wp-admin/options.php', $fields );
    list( , $html ) = request_test( $page );
    check_test( strpos( $html, 'data-key-configured="0"' ) !== false && strpos( $html, 'name="ya_api_key[clear]"' ) === false, 'Explicit key deletion succeeds' );
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
    check_test( 'talk' === selected_mode_test( $html ), 'Subscriber request did not change stored mode' );
    echo 'PASS: ' . $checks . ' authenticated HTTP security checks' . PHP_EOL;
} finally {
    unlink( $cookie );
}
