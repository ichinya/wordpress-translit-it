<?php
/**
 * Plugin Name: translit it!
 * Plugin URI: https://wordpress.org/plugins/translit-it/
 * Description: Transliterates Russian slugs or translates them into English with Yandex Translate.
 * Version: 1.9.0
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
	/** Asset version for the settings screen. */
	private const VERSION = '1.9.0';

	/** Hook returned by WordPress for this plugin's settings screen. */
	private static string $settings_page = '';
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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_settings_assets' ) );
	}

	/** Register the original option names so existing settings survive upgrades. */
	public static function register_settings() {
		register_setting( 'translit_it_settings', 'rtl_standard', array( 'type' => 'string', 'sanitize_callback' => array( __CLASS__, 'sanitize_mode' ), 'default' => 'off' ) );
		register_setting( 'translit_it_settings', 'rtl_translate', array( 'type' => 'boolean', 'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ), 'default' => false ) );
		register_setting( 'translit_it_settings', 'ya_api_key', array( 'type' => 'string', 'sanitize_callback' => array( __CLASS__, 'sanitize_saved_key' ), 'default' => '' ) );
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

	/** A blank UI field keeps a saved key; clearing it requires an explicit choice. */
	public static function sanitize_saved_key( $value ) {
		// Preserve ordinary Options API writes, including explicit empty strings.
		if ( ! is_array( $value ) ) {
			return self::sanitize_key( $value );
		}
		if ( self::sanitize_checkbox( $value['clear'] ?? false ) ) {
			return '';
		}
		$current = self::sanitize_key( get_option( 'ya_api_key', '' ) );
		$entered = $value['value'] ?? '';
		if ( is_string( $entered ) && '' === trim( $entered ) ) {
			return $current;
		}
		$validated = self::sanitize_key( $entered );
		if ( '' === $validated ) {
			add_settings_error( 'translit_it_settings', 'invalid_api_key', __( 'Не удалось сохранить API-ключ. Проверьте скопированное значение. Прежний ключ сохранён.', 'translit-it' ) );
			return $current;
		}
		return $validated;
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
		self::$settings_page = (string) add_options_page( __( 'Транслитерируй это!', 'translit-it' ), __( 'Транслитерируй это!', 'translit-it' ), 'manage_options', 'translit-it/index.php', array( __CLASS__, 'render_settings' ) );
	}

	/** Load the screen's CSS and JavaScript only on this settings page. */
	public static function enqueue_settings_assets( $hook_suffix ) {
		if ( '' === self::$settings_page || self::$settings_page !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style( 'translit-it-settings', plugins_url( 'assets/admin.css', __FILE__ ), array(), self::VERSION );
		wp_enqueue_script( 'translit-it-settings', plugins_url( 'assets/admin.js', __FILE__ ), array(), self::VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	/** Render escaped settings; options.php handles authorization and CSRF. */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'У вас нет доступа к настройкам плагина.', 'translit-it' ), '', array( 'response' => 403 ) );
		}
		$mode   = self::sanitize_mode( get_option( 'rtl_standard', 'off' ) );
		$has_key = '' !== self::sanitize_key( get_option( 'ya_api_key', '' ) );
		$folder = get_option( 'folder_id', '' );
		$fallback = self::sanitize_checkbox( get_option( 'rtl_translate', false ) );
		require __DIR__ . '/includes/settings-page.php';
	}
}

Translit_It::boot();
