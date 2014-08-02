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

    public function translate($string) {
        return self::$language[$string];
    }
}
