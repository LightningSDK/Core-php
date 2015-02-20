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

    protected static $statusStrings = array(
        1 => 'access denied',
        2 => 'success',
        3 => 'error',
    );

    /**
     * Output data as json and end the request.
     *
     * @param array|integer $data
     *   The data to output as JSON.
     */
    public static function json($data = array()) {
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
        $data['errors'] = Messenger::getErrors();
        $data['messages'] = Messenger::getMessages();

        if (empty($data['status']) && empty($data['errors'])) {
            $data['status'] = self::$statusStrings[self::SUCCESS];
        }

        // Output the data.
        header('Content-type: application/json');
        echo json_encode($data);

        // Terminate the script.
        exit;
    }

    public static function jsonData($data) {
        echo json_encode(array(
            'data' => $data,
            'status' => 'success',
            'errors' => Messenger::getErrors(),
            'messages' => Messenger::getMessages(),
        ));
        exit;
    }

    /**
     * Die on an error with a message in json format.
     *
     * @param string $error
     *   The error message.
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
        if (strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) {
            Output::json();
        } else {
            Template::resetInstance();
            $page = new Message();
            $page->execute();
        }
        exit;
    }

    public static function error($error) {
        Messenger::error($error);
        Template::getInstance()->render('');
        exit;
    }

    public static function clearCookie($cookie) {
        self::setCookie($cookie, '');
    }

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

    public static function sendCookies() {
        foreach (self::$cookies as $cookie => $settings) {
            setcookie($cookie, $settings['value'], $settings['ttl'], $settings['path'], $settings['domain'], $settings['secure'], $settings['httponly']);
        }
    }

    public static function disableBuffering() {
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }
        @ini_set('zlib.output_compression', "Off");
        @ini_set('implicit_flush', 1);

        for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
        ob_implicit_flush(true);
        echo str_repeat(' ', 9000);
    }
}
