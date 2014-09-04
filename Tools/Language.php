<?

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
    public static function getInstance() {
        return parent::getInstance();
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
    public function translate($key) {
        return !empty(self::$language[$key]) ? self::$language[$key] : '';
    }
}
