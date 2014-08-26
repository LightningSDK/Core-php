<?

namespace Lightning\Tools;

class Logger extends Singleton {

    const SEVERITY_LOW = 1;
    const SEVERITY_MED = 2;
    const SEVERITY_HIGH = 3;

    public static function error($error) {
        error_log($error);
    }

    public static function logIP($error, $ip) {
        // TODO: Fill this out.
    }
}
