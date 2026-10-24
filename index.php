<?php
/**
 * Plugin Name: translit it!
 * Plugin URI: https://wordpress.org/plugins/translit-it/
 * Description: Transliterates Russian slugs or translates them into English with Yandex Translate.
 * Version: 1.8.3
 * Requires at least: 7.1
 * Requires PHP: 8.4
 * Author: Ichi-nya
 * Author URI: https://profiles.wordpress.org/ichi-nya/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: translit-it
 *
 * @package Translit_It
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/transliteration.php';

/** Slug conversion and the settings page. */
final class Translit_It {
	/** Reuse responses within one request, without storing titles or API keys. */
	private static array $translations = array();

	/** Avoid repeated waits after a translator fails during this PHP request. */
	private static array $unavailable_translators = array();

	/** HTTP hooks can generate slugs while a translation is still in progress. */
	private static bool $translating = false;

	/** Register hooks. */
	public static function boot() {
		add_filter( 'sanitize_title', array( __CLASS__, 'filter_title' ), 0, 3 );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/** Register the original option names so existing settings survive upgrades. */
	public static function register_settings() {
		register_setting( 'translit_it_settings', 'rtl_standard', array( 'type' => 'string', 'sanitize_callback' => array( __CLASS__, 'sanitize_mode' ), 'default' => 'off' ) );
		register_setting( 'translit_it_settings', 'rtl_translate', array( 'type' => 'boolean', 'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ), 'default' => false ) );
		register_setting( 'translit_it_settings', 'ya_api_key', array( 'type' => 'string', 'sanitize_callback' => array( __CLASS__, 'sanitize_key' ), 'default' => '' ) );
		register_setting( 'translit_it_settings', 'folder_id', array( 'type' => 'string', 'sanitize_callback' => array( __CLASS__, 'sanitize_folder' ), 'default' => '' ) );
	}

	/** Validate the conversion mode against the supported values. */
	public static function sanitize_mode( $value ) {
		return is_string( $value ) && in_array( $value, array( 'off', 'talk', 'iso', 'yandex', 'cloud_yandex' ), true ) ? $value : 'off';
	}

	/** Normalize a checkbox, rejecting arrays and other unexpected input. */
	public static function sanitize_checkbox( $value ) {
		return in_array( $value, array( true, 1, '1' ), true );
	}

	/** Validate an opaque API key before storing it or using it in a header. */
	public static function sanitize_key( $value ) {
		if ( ! is_string( $value ) || strlen( $value ) > 2048 ) {
			return '';
		}
		$value = trim( $value );
		return preg_match( '/\A[A-Za-z0-9_.:\-]*\z/', $value ) ? $value : '';
	}

	/** Keep the legacy folder setting without accepting arbitrary HTML. */
	public static function sanitize_folder( $value ) {
		return is_string( $value ) && preg_match( '/\A[A-Za-z0-9_-]{0,50}\z/', $value ) ? $value : '';
	}

	/** Convert a title only when WordPress is generating a slug for saving. */
	public static function filter_title( $title, $raw_title = '', $context = 'save' ) {
		if ( 'save' !== $context || ! is_string( $title ) || '' === $title ) {
			return $title;
		}
		$mode = self::sanitize_mode( get_option( 'rtl_standard', 'off' ) );
		if ( 'off' === $mode ) {
			return $title;
		}
		if ( 'talk' === $mode || 'iso' === $mode ) {
			return translit_it_convert( $title, $mode );
		}

		// Already Latin slugs need no external translation, including manual slugs.
		if ( ! preg_match( '/[\x{0400}-\x{04FF}]/u', $title ) ) {
			return $title;
		}
		$fallback = self::sanitize_checkbox( get_option( 'rtl_translate', false ) );
		$fallback_title = $fallback ? translit_it_convert( $title ) : $title;
		$key      = self::sanitize_key( get_option( 'ya_api_key', '' ) );
		// Bound Unicode text before sending it; the Cloud API permits 10,000 characters.
		if ( '' === $key || 1 !== preg_match( '/\A.{1,10000}\z/us', $title ) ) {
			return $fallback_title;
		}
		$translator_key = hash( 'sha256', $mode . "\0" . $key );
		$cache_key = hash( 'sha256', $mode . "\0" . $key . "\0" . $title );
		if ( ! array_key_exists( $cache_key, self::$translations ) ) {
			if ( self::$translating || isset( self::$unavailable_translators[ $translator_key ] ) ) {
				return $fallback_title;
			}
			self::$translating = true;
			try {
				self::$translations[ $cache_key ] = self::translate( $title, $mode, $key );
			} finally {
				self::$translating = false;
			}
			if ( null === self::$translations[ $cache_key ] ) {
				self::$unavailable_translators[ $translator_key ] = true;
			}
		}
		$translated = self::$translations[ $cache_key ];
		if ( null === $translated ) {
			return $fallback_title;
		}
		return $fallback ? translit_it_convert( $translated ) : $translated;
	}

	/** Perform a bounded request; never follow redirects with credentials. */
	private static function translate( $title, $mode, $key ) {
		$args = array(
			'timeout'             => 5,
			'redirection'         => 0,
			'limit_response_size' => 65536,
		);
		if ( 'cloud_yandex' === $mode ) {
			// API keys belong to service accounts; folderId must be omitted.
			$args['headers'] = array( 'Content-Type' => 'application/json', 'Authorization' => 'Api-Key ' . $key );
			$args['body']    = wp_json_encode( array( 'sourceLanguageCode' => 'ru', 'targetLanguageCode' => 'en', 'format' => 'PLAIN_TEXT', 'texts' => array( $title ) ) );
			$response        = wp_remote_post( 'https://translate.api.cloud.yandex.net/translate/v2/translate', $args );
		} else {
			// Keep the old API mode for existing installations and their API keys.
			$args['body'] = array( 'key' => $key, 'lang' => 'ru-en', 'text' => $title );
			$response     = wp_remote_post( 'https://translate.yandex.net/api/v1.5/tr.json/translate', $args );
		}
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return null;
		}
		if ( 'cloud_yandex' === $mode ) {
			$text = $body['translations'][0]['text'] ?? null;
		} else {
			if ( 200 !== ( $body['code'] ?? null ) ) {
				return null;
			}
			$text = $body['text'][0] ?? null;
		}
		return is_string( $text ) && '' !== trim( $text ) ? $text : null;
	}

	/** Preserve the old settings URL and require administrative capabilities. */
	public static function add_menu() {
		add_options_page( __( 'Транслитерируй это!', 'translit-it' ), __( 'Транслитерируй это!', 'translit-it' ), 'manage_options', 'translit-it/index.php', array( __CLASS__, 'render_settings' ) );
	}

	/** Render escaped settings; options.php handles authorization and CSRF. */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'У вас нет доступа к настройкам плагина.', 'translit-it' ), '', array( 'response' => 403 ) );
		}
		$mode   = self::sanitize_mode( get_option( 'rtl_standard', 'off' ) );
		$key    = get_option( 'ya_api_key', '' );
		$folder = get_option( 'folder_id', '' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Транслитерируй это!', 'translit-it' ); ?></h1>
			<?php settings_errors(); ?>
			<p><?php esc_html_e( 'Выберите способ создания адресов новых записей, страниц и рубрик. Существующие адреса автоматически не изменяются.', 'translit-it' ); ?></p>
			<form action="options.php" method="post">
				<?php settings_fields( 'translit_it_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="rtl_standard"><?php esc_html_e( 'Способ преобразования', 'translit-it' ); ?></label></th>
						<td><select id="rtl_standard" name="rtl_standard">
							<option value="off" <?php selected( $mode, 'off' ); ?>><?php esc_html_e( 'Отключено', 'translit-it' ); ?></option>
							<option value="talk" <?php selected( $mode, 'talk' ); ?>><?php esc_html_e( 'Разговорная транслитерация', 'translit-it' ); ?></option>
							<option value="iso" <?php selected( $mode, 'iso' ); ?>><?php esc_html_e( 'Транслитерация ISO (прежняя таблица)', 'translit-it' ); ?></option>
							<option value="cloud_yandex" <?php selected( $mode, 'cloud_yandex' ); ?>><?php esc_html_e( 'Yandex Cloud Translate', 'translit-it' ); ?></option>
							<option value="yandex" <?php selected( $mode, 'yandex' ); ?>><?php esc_html_e( 'Yandex Translate API 1.5 (устаревший)', 'translit-it' ); ?></option>
						</select></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Резервная транслитерация', 'translit-it' ); ?></th>
						<td><label><input type="checkbox" name="rtl_translate" value="1" <?php checked( self::sanitize_checkbox( get_option( 'rtl_translate', false ) ) ); ?>> <?php esc_html_e( 'Транслитерировать результат перевода; при ошибке переводчика использовать транслитерацию исходного текста.', 'translit-it' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="ya_api_key"><?php esc_html_e( 'API-ключ Яндекса', 'translit-it' ); ?></label></th>
						<td><input id="ya_api_key" name="ya_api_key" type="password" class="regular-text" autocomplete="new-password" value="<?php echo esc_attr( is_string( $key ) ? $key : '' ); ?>">
						<p class="description"><?php esc_html_e( 'Для Yandex Cloud используйте API-ключ сервисного аккаунта с правом ai.translate.user. Локальная транслитерация работает без ключа и без отправки текста во внешние сервисы.', 'translit-it' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="folder_id"><?php esc_html_e( 'Прежний идентификатор каталога', 'translit-it' ); ?></label></th>
						<td><input id="folder_id" name="folder_id" type="text" class="regular-text" value="<?php echo esc_attr( is_string( $folder ) ? $folder : '' ); ?>">
						<p class="description"><?php esc_html_e( 'Сохранён для совместимости настроек. При использовании API-ключа сервисного аккаунта каталог определяется Яндексом автоматически.', 'translit-it' ); ?></p></td>
					</tr>
				</table>
				<p><?php esc_html_e( 'При выборе перевода заголовок с кириллицей отправляется в Яндекс для создания адреса. При выборе транслитерации текст обрабатывается только на вашем сайте.', 'translit-it' ); ?></p>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

Translit_It::boot();
