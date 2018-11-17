<?php

namespace Lightning\Tools;

use DateTime;

class LoggerOverridable extends Singleton {

    const SEVERITY_LOW = 1;
    const SEVERITY_MED = 2;
    const SEVERITY_HIGH = 4;

    protected static $log;
    protected static $logFile;
    protected static $securityLogFile;

    protected static $errorTypes = [
        E_ERROR           => 'error',
        E_WARNING         => 'warning',
        E_PARSE           => 'parsing error',
        E_NOTICE          => 'notice',
        E_CORE_ERROR      => 'core error',
        E_CORE_WARNING    => 'core warning',
        E_COMPILE_ERROR   => 'compile error',
        E_COMPILE_WARNING => 'compile warning',
        E_USER_ERROR      => 'user error',
        E_USER_WARNING    => 'user warning',
        E_USER_NOTICE     => 'user notice'
    ];

    /**
     * Load the log settings.
     */
    public static function init() {
        if (Configuration::get('site.logtype') == 'stacktrace') {
            set_error_handler([\Lightning\Tools\Logger::class, 'errorLogStacktrace']);
        }

        if ($logfile = Configuration::get('site.log')) {
            self::setLog($logfile);
        }
    }

    /**
     * Log a string includign a stacktrace.
     *
     * @param $error
     */
    public static function error($error) {
        $trace = debug_backtrace();
        array_shift($trace);
        self::errorLogStacktrace(0, $error, $trace[0]['file'], $trace[0]['line'], null, $trace);
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

    public static function info($message) {
        $message = '[INFO] ' . $message;
        self::message($message);
    }

    public static function print($message) {
        echo self::dateStamp() . ' ' . $message . PHP_EOL;
    }

    /**
     * Set the log file for writing.
     *
     * @param string $logFile
     *   Absolute or relative address to the log.
     *   MUST HAVE WRITE PERMISSION FOR THE USER RUNNING PHP
     */
    public static function setLog($logFile) {
        self::$logFile = $logFile;
        if (!preg_match('|^/|', self::$logFile)) {
            self::$logFile = HOME_PATH . '/' . self::$logFile;
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
        self::message($message);
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
