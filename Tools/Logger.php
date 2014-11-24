<?

namespace Lightning\Tools;

class Logger extends Singleton {

    const SEVERITY_LOW = 1;
    const SEVERITY_MED = 2;
    const SEVERITY_HIGH = 3;

    protected static $log;
    protected static $logFile;

    public static function error($error) {
        error_log($error);
    }

    public static function message($message) {
        if (!empty(self::$logFile)) {
            file_put_contents(self::$logFile, self::dateStamp() . ' ' . $message . "\n", FILE_APPEND | LOCK_EX);
        }
    }

    public static function setLog($logFile = null) {
        self::$logFile = $logFile;
        if (!empty(self::$logFile) && !preg_match('|^/|', self::$logFile)) {
            self::$logFile = HOME_PATH . '/' . self::$logFile;
        }
    }

    public static function logIP($error, $ip) {
        // TODO: Fill this out.
    }

    public static function dateStamp() {
        return '[' . date('Y-m-d H:i:s') . ']';
    }
}
