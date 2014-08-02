<?

namespace Lightning\Tools;

class Logger extends Singleton {

    public static function error($error) {
        error_log($error);
    }
}
