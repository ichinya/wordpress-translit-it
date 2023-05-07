<?php
/*
  Plugin Name: translit it!
  Plugin URI: http://wordpress.org/extend/plugins/translit-it/
  Description: Осторожно, это БЕТА версия! Переводим или транслитерализируем адреса страниц. Используется для перевода ЧПУ с русского на английский язык.
  Version: 1.6
  Author: Ichi-nya
  Author URI: http://profiles.wordpress.org/ichi-nya
  Plugin URI: http://ichiblog.ru/
  License: GPL2
  Copyright: 2013
 */

function get_talk_array(): array
{
    return [
        "№" => "#",
        "є" => "eh",
        "а" => "a",
        "б" => "b",
        "в" => "v",
        "г" => "g",
        "д" => "d",
        "е" => "e",
        "ё" => "e",
        "ж" => "j",
        "з" => "z",
        "и" => "i",
        "й" => "y",
        "к" => "k",
        "л" => "l",
        "м" => "m",
        "н" => "n",
        "о" => "o",
        "п" => "p",
        "р" => "r",
        "с" => "s",
        "т" => "t",
        "у" => "u",
        "ф" => "f",
        "х" => "h",
        "ц" => "c",
        "ч" => "ch",
        "ш" => "sh",
        "щ" => "sch",
        "ъ" => "",
        "ы" => "y",
        "ь" => "",
        "э" => "e",
        "ю" => "yu",
        "я" => "ya",
        "—" => "-",
        "«" => "",
        "»" => "",
        "…" => "",
    ];
}

function get_iso_array(): array
{
    return [
        "а" => "a",
        "б" => "b",
        "в" => "v",
        "г" => "g",
        "д" => "d",
        "е" => "e",
        "ё" => "yo",
        "ж" => "zh",
        "з" => "z",
        "и" => "i",
        "й" => "j",
        "к" => "k",
        "л" => "l",
        "м" => "m",
        "н" => "n",
        "о" => "o",
        "п" => "p",
        "р" => "r",
        "с" => "s",
        "т" => "t",
        "у" => "u",
        "ф" => "f",
        "х" => "x",
        "ц" => "c",
        "ч" => "ch",
        "ш" => "sh",
        "щ" => "shh",
        "ъ" => "",
        "ы" => "y",
        "ь" => "",
        "э" => "e",
        "ю" => "yu",
        "я" => "ya",
        "—" => "-",
        "«" => "",
        "»" => "",
        "…" => "",
        "№" => "#"
    ];
}

class TryTranslate
{
    private static bool $try_translate = false;

    /**
     * @return bool
     */
    public static function is_try_translate(): bool
    {
        return self::$try_translate;
    }

    /**
     * @param bool $try_translate
     */
    public static function set_try_translate(bool $try_translate): void
    {
        self::$try_translate = $try_translate;
    }

}


/*
  Обрабатываем выбор
 */

/* Стандартная функция strtolower странно работает, пришлось доделать
 * (взять подобную из инета и модифицировал для своих нужд
 */

function str2lower($inputString): string
{
    return mb_strtolower($inputString, 'UTF-8');
}

function try_translate($title): string
{
    // Была попытка перевода
    TryTranslate::set_try_translate(false);

    // Используем транслитерацию $talk
    return strtr(str2lower($title), get_talk_array());
}

function sanitize_title_with_translit($title)
{
    $rtl_standard = get_option('rtl_standard');
    TryTranslate::set_try_translate((bool)get_option('rtl_translate'));

    switch ($rtl_standard) {
        case 'talk':
            return strtr(str2lower($title), get_talk_array());
        case 'iso':
            return strtr(str2lower($title), get_iso_array());
        case 'yandex':
            return yandex_sanitize_title($title);
        case 'cloud_yandex':
            return yandex_cloud_translate($title);
        default:
            return $title;
    }
}

function yandex_sanitize_title($title): string
{
    // Для Яндекс API 1.5
    $ya_api_15 = get_option('ya_api_key');
    // Проверяем, введен ли API ключ от Яндекса
    if ($ya_api_15 == '') {
        return try_translate($title);
    }
    $url = sprintf("https://translate.yandex.net/api/v1.5/tr.json/translate?key=%s&lang=ru-en&text=%s", $ya_api_15, urlencode($title));
    $translate = @file_get_contents($url);
    // Если не получается зайти по адресу переводчика
    $status = substr($http_response_header[0], 9, 3);
    // Если получить данные от переводчика Яндекса не получилось.
    if ($status != 200 && TryTranslate::is_try_translate()) {
        return try_translate($title);
    } elseif ($status != 200) {
        return $title;
    }
    $json = json_decode($translate, true);
    // Проверяем на ошибки получения ответа
    if ($json['code'] == !200) {
        return $title;
    } else {
        // Выбираем результат перевода
        $result = $json['text']['0'];
    };
    if (TryTranslate::is_try_translate()) {
        $result = strtr(str2lower($result), get_talk_array());
    }

    return $result;
}

function yandex_cloud_translate(string $title): string
{
    $curl = curl_init();

    $post = [
        'sourceLanguageCode' => 'ru',
        'targetLanguageCode' => 'en',
        'format' => 'PLAIN_TEXT',
        'texts' => [$title],
        'folderID' => get_option('folder_id')
    ];

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://translate.api.cloud.yandex.net/translate/v2/translate',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($post, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Api-Key ' . get_option('ya_api_key'),
        ],
    ));

    $response = @json_decode(curl_exec($curl), true);

    curl_close($curl);
    $translate = $response['translations'][0]['text'] ?? $title;
    if (TryTranslate::is_try_translate()) {
        $translate = strtr(str2lower($translate), get_talk_array());
    }
    return $translate;
}

function rtl_options_page()
{
    ?>
    <div class="wrap">
        <h2>Настройки Плагина</h2>
        <p>Вы можете выбрать способ, по которому будет производиться транслитерация заголовков.</p>
        <?php
        if ($_POST['rtl_standard'] ?? false) {
            // set the post formatting options
            update_option('rtl_standard', $_POST['rtl_standard'] ?? '');
            update_option('rtl_translate', $_POST['rtl_translate'] ?? '');
            update_option('ya_api_key', $_POST['ya_api_key'] ?? '');
            update_option('folder_id', $_POST['folder_id'] ?? '');
            echo '<div class="updated"><p>Настройки обновлены.</p></div>';
        }
        ?>

        <form method="post">
            <fieldset class="options">
                <p>Транслитерация происходит только в новых постах</p>
                <legend>Производить транслитерацию способом:</legend>
                <?php
                // Загружаем настройки из базы
                $rtl_standard = get_option('rtl_standard');
                $rtl_translate = get_option('rtl_translate');
                $ya_api_15 = get_option('ya_api_key');
                $folderID = get_option('folder_id');
                ?>
                <!-- Choice of the method of transliteration -->
                <select name="rtl_standard">
                    <option value="yandex" <?= ($rtl_standard == 'yandex') ? ' selected="selected"' : ''; ?>>
                        Yandex Translate
                    </option>
                    <option
                        value="cloud_yandex" <?= ($rtl_standard == 'cloud_yandex') ? ' selected="selected"' : ''; ?>>
                        Cloud Yandex Translate
                    </option>
                    <option value="talk" <?= ($rtl_standard == 'talk') ? ' selected="selected"' : ''; ?>>
                        Разговорный
                    </option>
                    <option value="iso" <?= ($rtl_standard == 'iso') ? ' selected="selected"' : ''; ?>>
                        ISO 9-95
                    </option>
                    <option
                        value="off" <?= ($rtl_standard == 'off' || $rtl_standard == '') ? ' selected="selected"' : ''; ?>>
                        Отключена
                    </option>
                </select>
                <br/>
                <div>
                    <input type="checkbox" name="rtl_translate"
                           value='1' <?= ($rtl_translate) ? 'checked="checked"' : ''; ?> />
                    Производить транслитерацию после переводчика
                </div>

                <?php
                if ($rtl_standard == 'google') {
                    echo 'Гугл не работает';
                }
                ?>
                <?php if ($rtl_standard == 'yandex') : ?> <label for="ya_api_key">Введите Ваш уникальный
                    API-ключ:</label>
                    <br/>
                    <input id="ya_api"
                           type="text"
                           name="ya_api_key"
                           size="150"
                           value="<?= $ya_api_15 ?? ''; ?>"/>
                    <br/>
                    Получить api-ключ Яндекса можно
                    <a href="https://translate.yandex.ru/developers/keys" target=_blank>тут</a>

                <?php endif; ?>
                <?php if ($rtl_standard == 'cloud_yandex') : ?> <label for="ya_api_key">
                    Введите Ваш API-ключ и folderID :</label>
                    <br/>
                    <input id="ya_api"
                           type="text"
                           name="ya_api_key"
                           size="150"
                           value="<?= $ya_api_15 ?? ''; ?>"/>
                    <input id="ya_api"
                           type="text"
                           name="folder_id"
                           size="150"
                           value="<?= $folderID ?? ''; ?>"/>
                    <br/>
                    Получить api-ключ Яндекса можно
                    <a href="https://translate.api.cloud.yandex.net/translate/v2/translate" target=_blank>тут</a>

                <?php endif; ?>
                <br/>
                <input type="submit" value="Изменить"/>
            </fieldset>
        </form>
    </div>
    <?php
}

// Добавляем опции настроек
function rtl_add_menu()
{
    add_options_page('Транслитерируй это!', 'Транслитерируй это!', 8, __FILE__, 'rtl_options_page');
}

add_action('admin_menu', 'rtl_add_menu');

add_action('sanitize_title', 'sanitize_title_with_translit', 0);
