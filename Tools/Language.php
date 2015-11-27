<?php

namespace Lightning\Tools;

class Language extends Singleton {
    protected static $language;

    public function __construct() {
        if (empty(self::$language)) {
            include_once CONFIG_PATH . '/lang.' . Configuration::get('language') . '.php';
            self::$language = $language;
        }
    }

    /**
     * @return Language
     */
    public static function getInstance($create = true) {
        return parent::getInstance($create);
    }

    /**
     * Translate a string from it's key to it's value.
     *
     * @param string $key
     *   The name of the string.
     *
     * @return string
     *   The translated value.
     */
    public function translate($key, $replacements = array()) {
        // If a translation was found.
        if (!empty(self::$language[$key])) {
            $text = self::$language[$key];
            foreach ($replacements as $replace => $with) {
                $text = str_replace($replace, $with, $text);
            }
        } else {
            $text = $key;
        }

        // Return the translation.
        return $text;
    }
}
