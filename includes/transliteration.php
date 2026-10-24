<?php
/** Original transliteration tables, retained for stable slug generation.
 * @package Translit_It
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
function translit_it_get_talk_array(): array
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

function translit_it_get_iso_array(): array
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


/** Convert Cyrillic to lowercase Latin text using the original tables. */
function translit_it_convert( $title, $mode = 'talk' ) {
    if ( function_exists( 'mb_strtolower' ) ) {
        $title = mb_strtolower( $title, 'UTF-8' );
        return strtr( $title, 'iso' === $mode ? translit_it_get_iso_array() : translit_it_get_talk_array() );
    }
    // ASCII plus Cyrillic uppercase mapping avoids a hard mbstring dependency.
    $upper = array_combine(
        preg_split( '//u', 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯЄ', -1, PREG_SPLIT_NO_EMPTY ),
        preg_split( '//u', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяє', -1, PREG_SPLIT_NO_EMPTY )
    );
    $title = strtolower( strtr( $title, $upper ) );
    return strtr( $title, 'iso' === $mode ? translit_it_get_iso_array() : translit_it_get_talk_array() );
}
