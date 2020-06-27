<?php

namespace lightningsdk\core\Tools;

use DateTime;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;

class LoggerCore extends Singleton {

    const SEVERITY_LOW = 1;
    const SEVERITY_MED = 2;
    const SEVERITY_HIGH = 4;

    /**
     * @var \Monolog\Logger
     */
    protected static $log;
    protected static $logFile;
    protected static $logLevel;
    protected static $securityLogFile;

    protected static $stacktrace;

    protected static function init() {
        if (!empty(self::$log)) {
            return;
        }
        if (Configuration::get('site.logtype') == 'stacktrace') {
            set_error_handler([\lightningsdk\core\Tools\Logger::class, 'errorLogStacktrace']);
        }

        static::setLog(Configuration::get('log.file', 'php://stdout'));
    }

    public static function setLog($file) {
        self::$logFile = File::absolute($file);
        self::$logLevel = Configuration::get('log.level', Monolog::WARNING);
        self::$log = new Monolog('name');
        self::$log->pushHandler(new StreamHandler(self::$logFile, self::$logLevel));
        self::$stacktrace = Configuration::get('log.stacktrace');
    }

    /**
     * Log a string includign a stacktrace.
     *
     * @param $error
     */
    public static function error($message) {
        self::init();
        self::$log->error($message);
//        if (static::$stacktrace) {
//            $trace = debug_backtrace();
//            array_shift($trace);
//            self::errorLogStacktrace(0, $error, $trace[0]['file'], $trace[0]['line'], null, $trace);
//        }
    }

    public static function errorf($message, ...$params) {
        self::init();
        self::$log->error(sprintf($message, ...$params));
    }

    public static function warning($message) {
        self::init();
        self::$log->warning($message);
    }

    public static function warningf($message, ...$params) {
        self::init();
        self::$log->warning(sprintf($message, ...$params));
    }

    public static function info($message) {
        self::init();
        self::$log->info($message);
    }

    public static function infof($message, ...$params) {
        self::init();
        self::$log->info(sprintf($message, ...$params));
    }

    public static function debug($message) {
        self::init();
        self::$log->debug($message);
    }

    public static function debugf($message, ...$params) {
        self::init();
        self::$log->debug(sprintf($message, ...$params));
    }

    /**
     * Log an exception and print the stacktrace.
     *
     * @param $exception
     */
    public static function exception($exception) {
        self::errorLogStacktrace($exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine(), null, $exception->getTrace());
    }

    /**
     * Write an unaltered message to the log.
     *
     * @param string $message
     *   The message.
     */
    public static function message($message, $explicit_log = null) {
        if (!empty(self::$logFile)) {
            file_put_contents($explicit_log ?: self::$logFile, self::dateStamp() . ' ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        } else {
            error_log(self::dateStamp() . ' ' . $message);
        }
    }

    /**
     * Log a security error.
     *
     * @param string $message
     * @param integer $severity
     */
    public static function security($message, $severity) {
        $severity_message = str_pad(str_repeat('*', $severity), 5, ' ');
        $ip_message = $severity_message . '[' . str_pad(Request::server('ip'), 15, ' ') . '] '. $message;
        self::message($ip_message);
    }

    protected static function dateStamp() {
        return '[' . (new DateTime('now', new \DateTimeZone(date_default_timezone_get())))->format('Y-m-d H:i:s') . ']';
    }

    public static function errorLogStacktrace($errno, $errstr, $errfile, $errline, $context = null, $trace = null) {
        // This is required to skip errors caught with the @ symbol.
        if (error_reporting() === 0) {
            return;
        }

        $server = !isset($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST'];
        $uri = !isset($_SERVER['REQUEST_URI']) ? '' : $_SERVER['REQUEST_URI'];
        $method = !isset($_SERVER['REQUEST_METHOD']) ? '' : $_SERVER['REQUEST_METHOD'];
        $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $type = empty(self::$errorTypes[$errno]) ? 'Unknown Error Type' : self::$errorTypes[$errno];
        $output = (ini_get('display_errors') == 'On' || ini_get('display_errors') == 1);

        $message[] = $method . ' ' . $protocol . $server . $uri;
        if ($method == 'POST' && $referrer = Request::getReferrer()) {
            $message[] = 'Referred by: ' . $referrer;
        }
        $message[] = $type . ': ' . $errstr;

        $formatted_stack = self::formatStacktrace($trace, $errfile, $errline);

        foreach ($formatted_stack as $line) {
            $message[] = $line;
        }

        $message = implode(PHP_EOL . str_repeat(' ', 22), $message);
        self::error($message);
        if ($output) {
            echo $message . PHP_EOL;
        }
    }

    public static function formatStacktrace($trace = null, $errfile = null, $errline = null) {
        // If a stacktrace was not supplied (from an exception) get the current stack trace.
        if ($trace === null) {
            $trace = debug_backtrace();
        }

        $started = false;
        $output = [];
        foreach ($trace as $row) {
            if ($started || (!empty($row['file']) && $row['file'] == $errfile)) {
                $started = true;
                $line = '    in ' . (!empty($row['file']) ? $row['file'] : '?') . ' on line ' . (!empty($row['line']) ? $row['line'] : '?');
                $output[] = $line;
            }
        }

        return $output;
    }
}
