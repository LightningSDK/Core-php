<?
/**
 * @file
 * A class for managing output.
 */

namespace Lightning\Tools;
use Lightning\Pages\Message;

/**
 * Class Output
 *
 * @package Lightning\Tools
 */
class Output {
    /**
     * The default output for access denied errors.
     */
    const ACCESS_DENIED = 1;

    /**
     * The default output for successful executions.
     */
    const SUCCESS = 2;

    /**
     * The default output for an error.
     */
    const ERROR = 3;

    /**
     * A list of cookies to output.
     *
     * @var array
     */
    protected static $cookies = array();

    protected static $isJson = false;

    protected static $jsonCookies = false;

    protected static $statusStrings = array(
        1 => 'access denied',
        2 => 'success',
        3 => 'error',
    );

    /**
     * Determine if the output should be json.
     *
     * @return boolean
     *   Whether the output should be json.
     */
    public static function isJSONRequest() {
        if (static::$isJson) {
            return true;
        }

        if (function_exists("apache_request_headers")) {
            // Check for a JSON request header in apache.
            $headers = apache_request_headers();
            if (!empty($headers['Accept']) && strpos($headers['Accept'], 'json') > 0) {
                return true;
            }
        } else {
            // Check for a JSON request header in nginx.
            if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set whether the output should be explicitly JSON.
     *
     * @param boolean $isJson
     *   Whether to force json output.
     */
    public static function setJson($isJson) {
        static::$isJson = $isJson;
    }

    /**
     * Output data as json and end the request.
     *
     * @param array|integer $data
     *   The data to output as JSON.
     * @param boolean $suppress_status
     *   Whether to suppress the default success/error message.
     */
    public static function json($data = array(), $suppress_status = false) {
        // Predefined outputs.
        if ($data == self::ACCESS_DENIED) {
            $data = array('status' => 'access_denied');
        }
        elseif ($data == self::SUCCESS) {
            $data = array('status' => 'success');
        }
        elseif ($data == self::ERROR) {
            $data = array('status' => 'error');
        }
        elseif (!empty($data['status']) && !empty(self::$statusStrings[$data['status']])) {
            // Convert numeric status to string.
            $data['status'] = self::$statusStrings[$data['status']];
        }

        // Add errors and messages.
        if (Messenger::hasErrors()) {
            $data['errors'] = Messenger::getErrors();
        }
        if (Messenger::hasMessages()) {
            $data['messages'] = Messenger::getMessages();
        }

        if (!$suppress_status && empty($data['status']) && empty($data['errors'])) {
            $data['status'] = self::$statusStrings[self::SUCCESS];
        }

        // Output and terminate.
        self::jsonOut($data);
    }

    public static function jsonData($data, $include_cookies = false) {
        $output = array(
            'data' => $data,
            'status' => 'success',
            'errors' => Messenger::getErrors(),
            'messages' => Messenger::getMessages(),
        );
        if ($include_cookies) {
            $output['cookies'] = self::$cookies;
        }
        // Output and terminate.
        self::jsonOut($output);
    }

    protected static function jsonOut($output) {
        // Send the cookies if enabled.
        if (static::$jsonCookies) {
            self::sendCookies();
        }

        // Add debug data.
        if (Configuration::get('debug')) {
            $database = Database::getInstance();
            $output['database'] = array(
                'queries' => $database->getQueries(),
                'time' => $database->timeReport(),
            );
        }

        // Output the data.
        header('Content-type: application/json');
        echo json_encode($output);
        exit;
    }

    /**
     * @param boolean $setCookies
     */
    public static function setJsonCookies($setCookies) {
        static::$jsonCookies = $setCookies;
    }

    /**
     * Die on an error with a message in json format.
     *
     * @param string $error
     *   The error message.
     *
     * @deprecated
     *   The error() function will determine if json should be output based on the headers.
     */
    public static function jsonError($error = '') {
        $data = array(
            'errors' => Messenger::getErrors(),
            'messages' => Messenger::getMessages(),
            'status' => 'error',
        );

        if (!empty($error)) {
            $data['errors'][] = $error;
        }

        // Output the data.
        header('Content-type: application/json');
        echo json_encode($data);

        // Terminate the script.
        exit;
    }

    public static function XMLSegment($items, $type = null) {
        $output = '';
        foreach ($items as $key => $item) {
            if (is_numeric($key) && $type) {
                $key = $type;
            }
            if (is_array($item)) {
                $output .= "<$key>" . self::XMLSegment($item) . "</$key>";
            } else {
                $output .= "<$key>" . Scrub::toHTML($item) . "</$key>";
            }
        }
        return $output;
    }

    /**
     * Load and render the access denied page.
     */
    public static function accessDenied() {
        Messenger::error('Access Denied');
        // TODO : This can be simplified using the error function below.
        if (static::isJSONRequest()) {
            Output::json();
        } else {
            Template::resetInstance();
            $page = new Message();
            $page->execute();
        }
        exit;
    }

    /**
     * Terminate the script with an error.
     *
     * @param string $error
     *   An error to output to the user.
     */
    public static function error($error) {
        Messenger::error($error);
        if(static::isJSONRequest()) {
            static::json(static::ERROR);
        } else {
            Template::getInstance()->render('');
        }
        exit;
    }

    /**
     * Queue a cookie to be deleted.
     *
     * @param string $cookie
     *   The cookie name.
     */
    public static function clearCookie($cookie) {
        self::setCookie($cookie, '');
    }

    /**
     * Queue a cookie for output.
     *
     * @param string $cookie
     *   The name of the cookie.
     * @param string $value
     *   The value.
     * @param integer $ttl
     *   How long the cookie should last in seconds from the time it's created.
     *   This is not an expiration date like the php setcookie() function.
     * @param string $path
     *   The cookie path.
     * @param string $domain
     *   The cookie domain.
     * @param boolean $secure
     *   Whether the cookie can only be used over https.
     * @param boolean $httponly
     *   Whether the cookie can only be used as an http header.
     */
    public static function setCookie($cookie, $value, $ttl = null, $path = '/', $domain = null, $secure = null, $httponly = true) {
        $settings = array(
            'value' => $value,
            'ttl' => $ttl ? $ttl + time() : 0,
            'path' => $path,
            'domain' => $domain !== null ? $domain : Configuration::get('cookie_domain'),
            'secure' => $secure !== null ? $secure : (!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) == 'on')),
            'httponly' => $httponly,
        );
        if (isset(self::$cookies[$cookie])) {
            self::$cookies[$cookie] = $settings + self::$cookies[$cookie];
        } else {
            self::$cookies[$cookie] = $settings;
        }
    }

    /**
     * Set the cookie headers.
     * Does not actually send data until the content begins.
     */
    public static function sendCookies() {
        foreach (self::$cookies as $cookie => $settings) {
            setcookie($cookie, $settings['value'], $settings['ttl'], $settings['path'], $settings['domain'], $settings['secure'], $settings['httponly']);
        }
    }

    /**
     * Disable output buffering for streaming output.
     */
    public static function disableBuffering() {
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }
        // For apache.
        @ini_set('zlib.output_compression', "Off");
        @ini_set('implicit_flush', 1);
        // For nginx.
        header('X-Accel-Buffering: no');

        for ($i = 0; $i < ob_get_level(); $i++) {
            ob_end_flush();
        }
        ob_implicit_flush(true);
        echo str_repeat(' ', 9000);
    }

    /**
     * Prepare headers to output a downloaded file.
     *
     * @param string $file_name
     *   The name that the browser should save the file as.
     * @param int $size
     *   The size of the content if known.
     */
    public static function download($file_name, $size = null) {
        header('Content-disposition: attachment; filename=' . $file_name);
        if ($size) {
            header('Content-Length: ' . $size);
        }
        Output::disableBuffering();
    }
}
