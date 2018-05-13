<?php

namespace Lightning\Tools;

use Lightning\Tools\Session\BrowserSession;

class Language {

    protected static $inited = false;

    protected static $language;

    protected static function init() {
        if (empty(self::$language)) {
            $language = [];
            include_once CONFIG_PATH . '/lang.' . static::get() . '.php';
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

    /**
     * Switch the output language.
     *
     * @param string $new_language
     *   ISO Language
     */
    public static function switchTo($new_language) {
        if (self::get() !== $new_language) {
            $session = BrowserSession::getInstance();
            $session->language = $new_language;
            $session->save();
        }
    }

    /**
     * Get the current language that the user is expecting.
     *
     * @return string
     *   IOS language
     */
    public static function get() {
        $session = BrowserSession::getInstance();
        if (!empty($session->language)) {
            return $session->language;
        } else {
            return Configuration::get('language.default');
        }
    }

    /**
     * Check whether the user is browsing in the default language.
     *
     * @return boolean
     *   Whether the language is the default.
     */
    public static function isDefault() {
        $session = BrowserSession::getInstance();
        return empty($session->language) || ($session->language === Configuration::get('language.default'));
    }

    /**
     * Returns a query for language from the database. It does not include the field.
     * Calling this function should look like:
     *   $where = ['url' => $url, 'language' => Language::query()];
     */
    public static function query() {
        $query = self::get();
        if (self::isDefault()) {
            $query = ['IN' => ['', $query]];
        }

        return $query;
    }
}
