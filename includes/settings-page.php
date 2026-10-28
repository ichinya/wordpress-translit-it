<?php
/** Settings screen, rendered from Translit_It::render_settings().
 * @package Translit_It
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Keep the Russian sample as input data, including on English-language sites.
$translit_it_example = 'Ёжик у озера';
$translit_it_choices = array(
	'talk' => array(
		'title' => __( 'Transliteration', 'translit-it' ),
		'tag' => __( 'Local', 'translit-it' ),
		'description' => __( 'Replaces Russian letters with Latin characters. Works without an API key or external services.', 'translit-it' ),
		'preview' => '/' . sanitize_title_with_dashes( translit_it_convert( $translit_it_example ) ) . '/',
		'note' => __( 'Processed on your site. No text is sent to an external service.', 'translit-it' ),
	),
	'iso' => array(
		'title' => __( 'ISO-style transliteration', 'translit-it' ),
		'tag' => __( 'Local', 'translit-it' ),
		'description' => __( 'The historical ISO-style table: ё → yo, ж → zh. Choose this if you prefer these spellings.', 'translit-it' ),
		'preview' => '/' . sanitize_title_with_dashes( translit_it_convert( $translit_it_example, 'iso' ) ) . '/',
		'note' => __( 'Processed on your site. No text is sent to an external service.', 'translit-it' ),
	),
	'cloud_yandex' => array(
		'title' => __( 'Translate with Yandex', 'translit-it' ),
		'tag' => 'API',
		'description' => __( 'Translates the title into English. Requires a Yandex Cloud API key.', 'translit-it' ),
		'preview' => __( 'The translation is generated when the post is saved', 'translit-it' ),
		'note' => __( 'The title is sent to Yandex. This preview does not make paid requests.', 'translit-it' ),
	),
	'off' => array(
		'title' => __( 'No conversion', 'translit-it' ),
		'tag' => __( 'Off', 'translit-it' ),
		'description' => __( 'Lets WordPress generate addresses using its standard behavior.', 'translit-it' ),
		'preview' => '/' . rawurldecode( sanitize_title_with_dashes( $translit_it_example ) ) . '/',
		'note' => __( 'The plugin does not change the generated address.', 'translit-it' ),
	),
);
$translit_it_legacy = array(
	'title' => 'Yandex Translate API 1.5',
	'preview' => __( 'The translation is generated when the post is saved', 'translit-it' ),
	'note' => __( 'Uses the legacy Yandex service. Its availability is not guaranteed.', 'translit-it' ),
);
$translit_it_preview = $translit_it_choices[ $mode ] ?? $translit_it_legacy;
?>
<div class="wrap translit-it-settings" data-key-configured="<?php echo $has_key ? '1' : '0'; ?>">
	<header class="translit-it-header">
		<p class="translit-it-brand">translit it! <span><?php echo esc_html( self::VERSION ); ?></span></p>
		<h1><?php esc_html_e( 'Slug transliteration', 'translit-it' ); ?></h1>
		<p><?php esc_html_e( 'Readable addresses for new posts, pages and categories.', 'translit-it' ); ?></p>
	</header>
	<form action="options.php" method="post" class="translit-it-form">
		<?php settings_fields( 'translit_it_settings' ); ?>
		<div class="translit-it-layout">
			<div class="translit-it-main">
				<section class="translit-it-panel">
					<fieldset class="translit-it-modes">
						<legend><?php esc_html_e( 'How to generate addresses', 'translit-it' ); ?></legend>
						<p class="translit-it-lead"><?php esc_html_e( 'Choose how to convert titles into addresses.', 'translit-it' ); ?></p>
						<div class="translit-it-mode-grid">
							<?php foreach ( $translit_it_choices as $translit_it_value => $translit_it_choice ) : ?>
								<label class="translit-it-mode<?php echo $mode === $translit_it_value ? ' is-selected' : ''; ?>">
									<input type="radio" name="rtl_standard" value="<?php echo esc_attr( $translit_it_value ); ?>" <?php checked( $mode, $translit_it_value ); ?> data-preview="<?php echo esc_attr( $translit_it_choice['preview'] ); ?>" data-preview-note="<?php echo esc_attr( $translit_it_choice['note'] ); ?>" data-mode-title="<?php echo esc_attr( $translit_it_choice['title'] ); ?>">
									<span class="translit-it-mode-body">
										<span class="translit-it-mode-heading"><strong><?php echo esc_html( $translit_it_choice['title'] ); ?></strong><span class="translit-it-tag"><?php echo esc_html( $translit_it_choice['tag'] ); ?></span></span>
										<span class="translit-it-mode-description"><?php echo esc_html( $translit_it_choice['description'] ); ?></span>
									</span>
								</label>
							<?php endforeach; ?>
						</div>
						<details class="translit-it-advanced" <?php echo 'yandex' === $mode ? 'open' : ''; ?>>
							<summary><?php esc_html_e( 'Compatibility with legacy settings', 'translit-it' ); ?></summary>
							<label class="translit-it-legacy-choice"><input type="radio" name="rtl_standard" value="yandex" <?php checked( $mode, 'yandex' ); ?> data-preview="<?php echo esc_attr( $translit_it_legacy['preview'] ); ?>" data-preview-note="<?php echo esc_attr( $translit_it_legacy['note'] ); ?>" data-mode-title="<?php echo esc_attr( $translit_it_legacy['title'] ); ?>"> <strong><?php echo esc_html( $translit_it_legacy['title'] ); ?></strong></label>
							<p class="description"><?php esc_html_e( 'For an existing legacy connection. Choose Yandex Cloud for a new connection.', 'translit-it' ); ?></p>
							<label for="folder_id" class="translit-it-field-label"><?php esc_html_e( 'Legacy folder identifier', 'translit-it' ); ?></label>
							<input id="folder_id" name="folder_id" type="text" class="regular-text" value="<?php echo esc_attr( is_string( $folder ) ? $folder : '' ); ?>" aria-describedby="translit-it-folder-help">
							<p class="description" id="translit-it-folder-help"><?php esc_html_e( 'Kept for compatibility. A Yandex Cloud service-account API key does not require this identifier.', 'translit-it' ); ?></p>
						</details>
					</fieldset>
				</section>

				<section class="translit-it-panel translit-it-cloud" data-cloud-panel aria-labelledby="translit-it-cloud-heading">
					<div class="translit-it-section-heading"><h2 id="translit-it-cloud-heading"><?php esc_html_e( 'Yandex connection', 'translit-it' ); ?></h2><span class="translit-it-key-status<?php echo $has_key ? ' has-key' : ''; ?>"><?php echo $has_key ? esc_html__( 'Key saved', 'translit-it' ) : esc_html__( 'No key configured', 'translit-it' ); ?></span></div>
					<p class="translit-it-lead" data-cloud-help><?php esc_html_e( 'Yandex Cloud requires a service-account API key with the ai.translate.user role.', 'translit-it' ); ?> <a href="https://yandex.cloud/ru/docs/iam/operations/authentication/manage-api-keys" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'How to get an API key', 'translit-it' ); ?><span class="screen-reader-text"><?php esc_html_e( ' (opens in a new tab)', 'translit-it' ); ?></span></a></p>
					<p class="translit-it-lead" data-legacy-help><?php esc_html_e( 'For legacy API 1.5, use its original registration key. A Yandex Cloud key cannot be used in this mode.', 'translit-it' ); ?></p>
					<label for="ya_api_key" class="translit-it-field-label"><?php echo $has_key ? esc_html__( 'Replace API key', 'translit-it' ) : esc_html__( 'API key', 'translit-it' ); ?></label>
					<div class="translit-it-key-field">
						<input id="ya_api_key" name="ya_api_key[value]" type="password" class="regular-text" autocomplete="new-password" value="" spellcheck="false" aria-describedby="translit-it-key-help" placeholder="<?php echo $has_key ? esc_attr__( 'Leave blank to keep the saved key', 'translit-it' ) : esc_attr__( 'Paste your API key', 'translit-it' ); ?>">
						<button type="button" class="button" data-key-toggle data-show-label="<?php esc_attr_e( 'Show', 'translit-it' ); ?>" data-hide-label="<?php esc_attr_e( 'Hide', 'translit-it' ); ?>" aria-controls="ya_api_key" aria-pressed="false" hidden><?php esc_html_e( 'Show', 'translit-it' ); ?></button>
					</div>
					<p class="description" id="translit-it-key-help"><?php esc_html_e( 'The saved key is never shown again. To replace it, enter a new key and save the settings.', 'translit-it' ); ?></p>
					<?php if ( $has_key ) : ?>
						<label class="translit-it-clear-key"><input type="checkbox" name="ya_api_key[clear]" value="1" data-clear-key> <?php esc_html_e( 'Delete the saved key', 'translit-it' ); ?></label>
					<?php endif; ?>
					<div class="translit-it-fallback">
						<label><input type="checkbox" name="rtl_translate" value="1" <?php checked( $fallback ); ?>> <strong><?php esc_html_e( 'Use transliteration as a fallback', 'translit-it' ); ?></strong></label>
						<p class="description"><?php esc_html_e( 'If Yandex is unavailable, the address is generated locally. Non-Latin characters in the response are also converted.', 'translit-it' ); ?></p>
					</div>
					<p class="translit-it-privacy"><?php esc_html_e( 'Translation sends titles to Yandex, including draft titles. Service charges may apply.', 'translit-it' ); ?></p>
				</section>
				<div class="translit-it-save-bar">
					<?php submit_button( __( 'Save settings', 'translit-it' ), 'primary', 'submit', false ); ?>
					<span data-save-status data-dirty-label="<?php esc_attr_e( 'You have unsaved changes', 'translit-it' ); ?>" role="status"><?php esc_html_e( 'Changes take effect after saving.', 'translit-it' ); ?></span>
				</div>
			</div>

			<aside class="translit-it-sidebar" aria-label="<?php esc_attr_e( 'Example and guidance', 'translit-it' ); ?>">
				<section class="translit-it-panel translit-it-preview">
					<h2><?php esc_html_e( 'Address example', 'translit-it' ); ?></h2>
					<p class="translit-it-preview-label"><?php esc_html_e( 'Post title', 'translit-it' ); ?></p>
					<p class="translit-it-example-title"><?php echo esc_html( $translit_it_example ); ?></p>
					<p class="translit-it-preview-label" data-preview-mode><?php echo esc_html( $translit_it_preview['title'] ); ?></p>
					<p class="translit-it-preview-result" data-preview-result aria-live="polite"><?php echo esc_html( $translit_it_preview['preview'] ); ?></p>
					<p class="description" data-preview-note-output><?php echo esc_html( $translit_it_preview['note'] ); ?></p>
				</section>
				<section class="translit-it-panel translit-it-note">
					<h2><?php esc_html_e( 'What will change', 'translit-it' ); ?></h2>
					<p><?php esc_html_e( 'This setting applies when generating new addresses. Existing links are not automatically rewritten.', 'translit-it' ); ?></p>
					<p><?php esc_html_e( 'Use one slug conversion plugin at a time to avoid conflicts.', 'translit-it' ); ?></p>
				</section>
			</aside>
		</div>
	</form>
</div>
