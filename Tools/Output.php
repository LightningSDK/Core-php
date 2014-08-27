<?
/**
 * @file
 * A class for managing output.
 */

namespace Lightning\Tools;

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

    protected static $cookies = array();

    /**
     * Output data as json and end the request.
     *
     * @param array|integer $data
     *   The data to output as JSON.
     */
    public static function json($data) {
        // Predefined outputs.
        if ($data == self::ACCESS_DENIED) {
            $data = array('status' => 'access_denied');
        }
        elseif ($data == self::SUCCESS) {
            $data = array('status' => 'success');
        }

        // Add errors and messages.
        $data['errors'] = Messenger::getErrors();
        $data['messages'] = Messenger::getErrors();

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
            'domain' => $domain ?: Configuration::get('cookie_domain'),
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
        @ini_set('zlib.output_compression', "Off");
        @ini_set('implicit_flush', 1);

        ob_end_flush();
        ob_implicit_flush(true);
        echo str_repeat(' ', 9000);
    }
}
