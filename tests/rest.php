<?php
/** Exercise the REST endpoints used by the block editor. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'TRANSLIT_IT_TESTS' ) ) {
    exit;
}

function translit_test_rest( $method, $route, $params = array() ) {
    $request = new WP_REST_Request( $method, $route );
    foreach ( $params as $key => $value ) {
        $request->set_param( $key, $value );
    }
    return rest_do_request( $request );
}

$rest_posts = array();
$rest_terms = array();
$saved_structure = get_option( 'permalink_structure' );
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'rtl_standard', 'iso' );
rest_get_server();

try {
    // A Gutenberg draft needs a generated permalink before it is published.
    $response = translit_test_rest( 'POST', '/wp/v2/posts', array( 'title' => 'Привет мир редактора', 'status' => 'draft', 'context' => 'edit' ) );
    $data = $response->get_data();
    if ( isset( $data['id'] ) ) { $rest_posts[] = $data['id']; }
    translit_test_same( 201, $response->get_status(), 'REST create editor draft' );
    translit_test_same( 'privet-mir-redaktora', $data['generated_slug'], 'Editor generated permalink in ISO mode' );
    $draft = $data['id'];

    $response = translit_test_rest( 'POST', '/wp/v2/posts/' . $draft, array( 'status' => 'publish', 'context' => 'edit' ) );
    $data = $response->get_data();
    translit_test_same( 200, $response->get_status(), 'REST publish draft' );
    translit_test_same( 'privet-mir-redaktora', $data['slug'], 'Published slug matches editor preview' );

    $response = translit_test_rest( 'POST', '/wp/v2/posts/' . $draft, array( 'title' => 'Другой заголовок', 'context' => 'edit' ) );
    translit_test_same( 200, $response->get_status(), 'REST edit published title' );
    translit_test_same( 'privet-mir-redaktora', $response->get_data()['slug'], 'Title edit preserves published URL' );

    $response = translit_test_rest( 'POST', '/wp/v2/posts/' . $draft . '/autosaves', array( 'title' => 'Автосохранение', 'content' => 'Новое содержимое' ) );
    translit_test_same( true, in_array( $response->get_status(), array( 200, 201 ), true ), 'Editor autosave succeeds' );
    translit_test_same( $draft, wp_get_post_parent_id( $response->get_data()['id'] ), 'Editor autosave belongs to the original post' );
    translit_test_same( 'privet-mir-redaktora', get_post_field( 'post_name', $draft ), 'Autosave preserves published URL' );

    $response = translit_test_rest( 'POST', '/wp/v2/posts', array( 'title' => 'Ручной адрес', 'slug' => 'my-manual-slug', 'status' => 'publish', 'context' => 'edit' ) );
    $data = $response->get_data();
    if ( isset( $data['id'] ) ) { $rest_posts[] = $data['id']; }
    translit_test_same( 201, $response->get_status(), 'REST create with explicit slug' );
    translit_test_same( 'my-manual-slug', $data['slug'], 'Editor respects explicit Latin slug' );

    $response = translit_test_rest( 'POST', '/wp/v2/pages', array( 'title' => 'Ёжик на странице', 'status' => 'publish', 'context' => 'edit' ) );
    $data = $response->get_data();
    if ( isset( $data['id'] ) ) { $rest_posts[] = $data['id']; }
    translit_test_same( 201, $response->get_status(), 'REST create page' );
    translit_test_same( 'yozhik-na-stranice', $data['slug'], 'REST page ISO transliteration' );

    $response = translit_test_rest( 'POST', '/wp/v2/categories', array( 'name' => 'Рубрика редактора' ) );
    $data = $response->get_data();
    if ( isset( $data['id'] ) ) { $rest_terms[] = array( $data['id'], 'category' ); }
    translit_test_same( 201, $response->get_status(), 'REST create category' );
    translit_test_same( 'rubrika-redaktora', $data['slug'], 'REST category transliteration' );

    // Translation in the editor uses the same mocked, bounded HTTP transport.
    update_option( 'rtl_standard', 'cloud_yandex' );
    update_option( 'ya_api_key', 'editor-fixture-key' );
    update_option( 'rtl_translate', true );
    $fixture = array( 'response' => array( 'code' => 200 ), 'body' => '{"translations":[{"text":"Cloud editor title"}]}' );
    $response = translit_test_rest( 'POST', '/wp/v2/posts', array( 'title' => 'Заголовок облачного редактора', 'status' => 'draft', 'context' => 'edit' ) );
    $data = $response->get_data();
    if ( isset( $data['id'] ) ) { $rest_posts[] = $data['id']; }
    translit_test_same( 201, $response->get_status(), 'Cloud editor draft' );
    translit_test_same( 'cloud-editor-title', $data['generated_slug'], 'Cloud editor preview' );
    $cloud_draft = $data['id'];
    $before_publish = count( $requests );
    $response = translit_test_rest( 'POST', '/wp/v2/posts/' . $cloud_draft, array( 'status' => 'publish', 'context' => 'edit' ) );
    translit_test_same( 200, $response->get_status(), 'Cloud editor publish' );
    translit_test_same( 'cloud-editor-title', $response->get_data()['slug'], 'Cloud editor publication agrees with preview' );
    translit_test_same( $before_publish, count( $requests ), 'Repeated editor title reuses request-local translation' );

    $fixture = new WP_Error( 'http_request_failed', 'Fixture timeout' );
    $response = translit_test_rest( 'POST', '/wp/v2/posts', array( 'title' => 'Редактор без сети', 'status' => 'publish', 'context' => 'edit' ) );
    $data = $response->get_data();
    if ( isset( $data['id'] ) ) { $rest_posts[] = $data['id']; }
    translit_test_same( 201, $response->get_status(), 'Editor publishes during translation outage' );
    translit_test_same( 'redaktor-bez-seti', $data['slug'], 'Editor translation outage fallback' );

    // Settings are not exposed by the REST settings endpoint.
    $response = translit_test_rest( 'GET', '/wp/v2/settings' );
    translit_test_same( 200, $response->get_status(), 'Administrator can read core REST settings' );
    translit_test_same( false, array_key_exists( 'ya_api_key', $response->get_data() ), 'API key stays outside REST settings' );
} finally {
    foreach ( $rest_posts as $id ) { wp_delete_post( $id, true ); }
    foreach ( $rest_terms as $term ) { wp_delete_term( $term[0], $term[1] ); }
    update_option( 'permalink_structure', $saved_structure );
}
