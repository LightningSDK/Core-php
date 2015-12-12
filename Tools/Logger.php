<?php

namespace Lightning\Tools;

class Logger extends Singleton {

    const SEVERITY_LOW = 1;
    const SEVERITY_MED = 2;
    const SEVERITY_HIGH = 4;

    protected static $log;
    protected static $logFile;
    protected static $securityLogFile;

    protected static $errorTypes = array(
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
    );

    public static function init() {
        if (Configuration::get('errorlog') == 'stacktrace') {
            set_error_handler(['Lightning\\Tools\\Logger', 'errorLogStacktrace']);
        }

        if ($logfile = Configuration::get('site.log')) {
            self::setLog($logfile);
        }
    }

    public static function error($error) {
        error_log($error);
    }

    public static function exception($exception) {
        self::errorLogStacktrace($exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTrace());
    }

    /**
     * Write an unaltered message to the log.
     *
     * @param string $message
     *   The message.
     */
    public static function message($message, $explicit_log = null) {
        if (!empty(self::$logFile)) {
            file_put_contents($explicit_log ?: self::$logFile, self::dateStamp() . ' ' . $message . "\n", FILE_APPEND | LOCK_EX);
        } else {
            error_log($message);
        }
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
        return '[' . date('Y-m-d H:i:s') . ']';
    }

    public static function errorLogStacktrace($errno, $errstr, $errfile, $errline, $trace = null) {
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

        self::message($method . ' ' . $protocol . $server . $uri);
        self::message('    ' . $type . ': ' . $errstr);

        if ($output) {
            echo ('    ' . $type . ': ' . $errstr . ' in ' . $errfile . ' on line ' . $errline . "\n");
        }

        $formatted_stack = self::formatStacktrace($trace, $errfile, $errline);

        foreach ($formatted_stack as $line) {
            self::message($line);
            if ($output) {
                echo $line . "\n";
            }
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
            if ($started || (!empty($row['file']) && !empty($errfile) && $row['file'] == $errfile && $row['line'] == $errline)) {
                $started = true;
                $line = '    in ' . (!empty($row['file']) ? $row['file'] : '?') . ' on line ' . (!empty($row['line']) ? $row['line'] : '?');
                $output[] = $line;
            }
        }

        return $output;
    }
}
