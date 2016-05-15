<?php

namespace Lightning\Tools;

class Language {

    protected static $inited = false;

    protected static $language;

    public static function init() {
        if (empty(self::$language)) {
            $language = [];
            include_once CONFIG_PATH . '/lang.' . Configuration::get('language') . '.php';
            self::$language = $language;
        }
    }

    /**
     * Translate a string from it's key to it's value.
     *
     * @param string $key
     *   The name of the string.
     * @param array $replacements
     *   An array of strings to replace.
     *
     * @return string
     *   The translated value.
     */
    public static function translate($key, $replacements = []) {
        if (!self::$inited) {
            self::init();
        }

        // If a translation was found.
        if (!$text = Data::getFromPath($key, self::$language)) {
            $text = $key;
        }

        foreach ($replacements as $replace => $with) {
            $text = str_replace($replace, $with, $text);
        }

        // Return the translation.
        return $text;
    }
}
