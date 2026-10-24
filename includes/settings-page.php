<?php
/** Settings screen, rendered from Translit_It::render_settings().
 * @package Translit_It
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$translit_it_example = __( 'Ёжик у озера', 'translit-it' );
$translit_it_choices = array(
	'talk' => array(
		'title' => __( 'Транслитерация', 'translit-it' ),
		'tag' => __( 'Локально', 'translit-it' ),
		'description' => __( 'Заменяет русские буквы латинскими. Работает без ключа и подключения к сервисам.', 'translit-it' ),
		'preview' => '/' . sanitize_title_with_dashes( translit_it_convert( $translit_it_example ) ) . '/',
		'note' => __( 'Обработка на вашем сайте. Текст никуда не отправляется.', 'translit-it' ),
	),
	'iso' => array(
		'title' => __( 'Транслитерация ISO', 'translit-it' ),
		'tag' => __( 'Локально', 'translit-it' ),
		'description' => __( 'Прежняя таблица ISO: ё → yo, ж → zh. Подойдёт, если вы привыкли к такому написанию.', 'translit-it' ),
		'preview' => '/' . sanitize_title_with_dashes( translit_it_convert( $translit_it_example, 'iso' ) ) . '/',
		'note' => __( 'Обработка на вашем сайте. Текст никуда не отправляется.', 'translit-it' ),
	),
	'cloud_yandex' => array(
		'title' => __( 'Перевод через Яндекс', 'translit-it' ),
		'tag' => __( 'API', 'translit-it' ),
		'description' => __( 'Переводит название на английский. Понадобится API-ключ Yandex Cloud.', 'translit-it' ),
		'preview' => __( 'Перевод появится при сохранении записи', 'translit-it' ),
		'note' => __( 'Название отправляется в Яндекс. Предпросмотр не выполняет платных запросов.', 'translit-it' ),
	),
	'off' => array(
		'title' => __( 'Без преобразования', 'translit-it' ),
		'tag' => __( 'Выключено', 'translit-it' ),
		'description' => __( 'Оставляет создание адресов стандартным средствам WordPress.', 'translit-it' ),
		'preview' => '/' . rawurldecode( sanitize_title_with_dashes( $translit_it_example ) ) . '/',
		'note' => __( 'Плагин не участвует в создании адреса.', 'translit-it' ),
	),
);
$translit_it_legacy = array(
	'title' => __( 'Yandex Translate API 1.5', 'translit-it' ),
	'preview' => __( 'Перевод появится при сохранении записи', 'translit-it' ),
	'note' => __( 'Используется устаревший сервис Яндекса. Его доступность не гарантируется.', 'translit-it' ),
);
$translit_it_preview = $translit_it_choices[ $mode ] ?? $translit_it_legacy;
?>
<div class="wrap translit-it-settings" data-key-configured="<?php echo $has_key ? '1' : '0'; ?>">
	<header class="translit-it-header">
		<p class="translit-it-brand">translit it! <span><?php echo esc_html( self::VERSION ); ?></span></p>
		<h1><?php esc_html_e( 'Транслитерация адресов', 'translit-it' ); ?></h1>
		<p><?php esc_html_e( 'Понятные адреса для новых записей, страниц и рубрик.', 'translit-it' ); ?></p>
	</header>
	<form action="options.php" method="post" class="translit-it-form">
		<?php settings_fields( 'translit_it_settings' ); ?>
		<div class="translit-it-layout">
			<div class="translit-it-main">
				<section class="translit-it-panel">
					<fieldset class="translit-it-modes">
						<legend><?php esc_html_e( 'Как создавать адреса', 'translit-it' ); ?></legend>
						<p class="translit-it-lead"><?php esc_html_e( 'Выберите подходящий способ преобразования названия.', 'translit-it' ); ?></p>
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
							<summary><?php esc_html_e( 'Совместимость со старыми настройками', 'translit-it' ); ?></summary>
							<label class="translit-it-legacy-choice"><input type="radio" name="rtl_standard" value="yandex" <?php checked( $mode, 'yandex' ); ?> data-preview="<?php echo esc_attr( $translit_it_legacy['preview'] ); ?>" data-preview-note="<?php echo esc_attr( $translit_it_legacy['note'] ); ?>" data-mode-title="<?php echo esc_attr( $translit_it_legacy['title'] ); ?>"> <strong><?php echo esc_html( $translit_it_legacy['title'] ); ?></strong></label>
							<p class="description"><?php esc_html_e( 'Для уже настроенного старого подключения. Для нового подключения выберите Yandex Cloud.', 'translit-it' ); ?></p>
							<label for="folder_id" class="translit-it-field-label"><?php esc_html_e( 'Прежний идентификатор каталога', 'translit-it' ); ?></label>
							<input id="folder_id" name="folder_id" type="text" class="regular-text" value="<?php echo esc_attr( is_string( $folder ) ? $folder : '' ); ?>" aria-describedby="translit-it-folder-help">
							<p class="description" id="translit-it-folder-help"><?php esc_html_e( 'Сохранён для совместимости. Для API-ключа сервисного аккаунта Yandex Cloud этот идентификатор не требуется.', 'translit-it' ); ?></p>
						</details>
					</fieldset>
				</section>

				<section class="translit-it-panel translit-it-cloud" data-cloud-panel aria-labelledby="translit-it-cloud-heading">
					<div class="translit-it-section-heading"><h2 id="translit-it-cloud-heading"><?php esc_html_e( 'Подключение к Яндексу', 'translit-it' ); ?></h2><span class="translit-it-key-status<?php echo $has_key ? ' has-key' : ''; ?>"><?php echo $has_key ? esc_html__( 'Ключ сохранён', 'translit-it' ) : esc_html__( 'Ключ не задан', 'translit-it' ); ?></span></div>
					<p class="translit-it-lead" data-cloud-help><?php esc_html_e( 'Для Yandex Cloud нужен API-ключ сервисного аккаунта с ролью ai.translate.user.', 'translit-it' ); ?> <a href="https://yandex.cloud/ru/docs/iam/operations/authentication/manage-api-keys" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Как получить ключ', 'translit-it' ); ?><span class="screen-reader-text"><?php esc_html_e( ' (откроется в новой вкладке)', 'translit-it' ); ?></span></a></p>
					<p class="translit-it-lead" data-legacy-help><?php esc_html_e( 'Для старого API 1.5 используйте выданный ему регистрационный ключ. Ключ Yandex Cloud для этого режима не подходит.', 'translit-it' ); ?></p>
					<label for="ya_api_key" class="translit-it-field-label"><?php echo $has_key ? esc_html__( 'Заменить API-ключ', 'translit-it' ) : esc_html__( 'API-ключ', 'translit-it' ); ?></label>
					<div class="translit-it-key-field">
						<input id="ya_api_key" name="ya_api_key[value]" type="password" class="regular-text" autocomplete="new-password" value="" spellcheck="false" aria-describedby="translit-it-key-help" placeholder="<?php echo $has_key ? esc_attr__( 'Оставьте пустым, чтобы сохранить прежний ключ', 'translit-it' ) : esc_attr__( 'Вставьте API-ключ', 'translit-it' ); ?>">
						<button type="button" class="button" data-key-toggle data-show-label="<?php esc_attr_e( 'Показать', 'translit-it' ); ?>" data-hide-label="<?php esc_attr_e( 'Скрыть', 'translit-it' ); ?>" aria-controls="ya_api_key" aria-pressed="false" hidden><?php esc_html_e( 'Показать', 'translit-it' ); ?></button>
					</div>
					<p class="description" id="translit-it-key-help"><?php esc_html_e( 'Сохранённый ключ не показывается повторно. Чтобы заменить его, введите новый и сохраните настройки.', 'translit-it' ); ?></p>
					<?php if ( $has_key ) : ?>
						<label class="translit-it-clear-key"><input type="checkbox" name="ya_api_key[clear]" value="1" data-clear-key> <?php esc_html_e( 'Удалить сохранённый ключ', 'translit-it' ); ?></label>
					<?php endif; ?>
					<div class="translit-it-fallback">
						<label><input type="checkbox" name="rtl_translate" value="1" <?php checked( $fallback ); ?>> <strong><?php esc_html_e( 'Подстраховать перевод транслитерацией', 'translit-it' ); ?></strong></label>
						<p class="description"><?php esc_html_e( 'Если Яндекс не отвечает, адрес будет создан локально. Нелатинские символы в ответе тоже будут преобразованы.', 'translit-it' ); ?></p>
					</div>
					<p class="translit-it-privacy"><?php esc_html_e( 'При переводе названия отправляются в Яндекс, в том числе названия черновиков. Использование сервиса может быть платным.', 'translit-it' ); ?></p>
				</section>
				<div class="translit-it-save-bar">
					<?php submit_button( __( 'Сохранить настройки', 'translit-it' ), 'primary', 'submit', false ); ?>
					<span data-save-status data-dirty-label="<?php esc_attr_e( 'Есть несохранённые изменения', 'translit-it' ); ?>" role="status"><?php esc_html_e( 'Изменения применятся после сохранения.', 'translit-it' ); ?></span>
				</div>
			</div>

			<aside class="translit-it-sidebar" aria-label="<?php esc_attr_e( 'Пример и подсказки', 'translit-it' ); ?>">
				<section class="translit-it-panel translit-it-preview">
					<h2><?php esc_html_e( 'Пример адреса', 'translit-it' ); ?></h2>
					<p class="translit-it-preview-label"><?php esc_html_e( 'Название записи', 'translit-it' ); ?></p>
					<p class="translit-it-example-title"><?php echo esc_html( $translit_it_example ); ?></p>
					<p class="translit-it-preview-label" data-preview-mode><?php echo esc_html( $translit_it_preview['title'] ); ?></p>
					<p class="translit-it-preview-result" data-preview-result aria-live="polite"><?php echo esc_html( $translit_it_preview['preview'] ); ?></p>
					<p class="description" data-preview-note-output><?php echo esc_html( $translit_it_preview['note'] ); ?></p>
				</section>
				<section class="translit-it-panel translit-it-note">
					<h2><?php esc_html_e( 'Что изменится', 'translit-it' ); ?></h2>
					<p><?php esc_html_e( 'Настройка применяется при создании новых адресов. Существующие ссылки автоматически не переписываются.', 'translit-it' ); ?></p>
					<p><?php esc_html_e( 'Используйте один плагин преобразования адресов, чтобы избежать конфликтов.', 'translit-it' ); ?></p>
				</section>
			</aside>
		</div>
	</form>
</div>
