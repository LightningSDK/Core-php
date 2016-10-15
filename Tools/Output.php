<?php
/**
 * @file
 * A class for managing output.
 */

namespace Lightning\Tools;
use Lightning\Pages\Page as PageView;
use Lightning\Model\Page as PageModel;
use Lightning\View\JS;
use Exception;

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
     * A login is required.
     */
    const LOGIN_REQUIRED = 4;

    /**
     * A list of cookies to output.
     *
     * @var array
     */
    protected static $cookies = [];
    protected static $sentCookies = [];

    protected static $isJson = false;

    protected static $isPlainText = false;

    protected static $jsonCookies = false;

    protected static $statusStrings = [
        1 => 'access denied',
        2 => 'success',
        3 => 'error',
    ];
    
    protected static $httpErrorMessages = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        598 => 'Network read timeout error',
        599 => 'Network connect timeout error'
    ];

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
     * Set whether the output should be in plain text, without HTML.
     *
     * @param $isPlainText
     *   Whether or not to use plain text output.
     */
    public static function setPlainText($isPlainText) {
        static::$isPlainText = $isPlainText;
    }

    /**
     * Output data as json and end the request.
     *
     * @param array|integer $data
     *   The data to output as JSON.
     * @param boolean $suppress_status
     *   Whether to suppress the default success/error message.
     */
    public static function json($data = [], $suppress_status = false) {
        // Predefined outputs.
        if ($data === self::SUCCESS || $data === true) {
            $data = ['status' => 'success'];
        }
        elseif ($data == self::ACCESS_DENIED) {
            $data = ['status' => 'access_denied'];
            Messenger::error('Access Denied');
        }
        elseif ($data == self::ERROR || $data === false) {
            $data = ['status' => 'error'];
        }
        elseif ($data == self::LOGIN_REQUIRED) {
            $template = Template::getInstance();
            $template->set('modal', true);
            $template->set('content', 'user');
            $data = [
                'status' => 'login_required',
                'content' => $template->render('modal', true),
            ];
            $data += self::getJSONJSOutput();
        }
        
        // If $data is a string, it is an error message.
        elseif (is_string($data)) {
            Messenger::error($data);
            $data = ['status' => 'error'];
        }
        
        // The rest of these conditions assume $data is an array.
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
    
    public static function getJSONJSOutput() {
        $output = [];
        if ($vars = JS::getAll()) {
            $output['js_vars'] = $vars;
        }
        if ($init = JS::getStartups()) {
            if (!empty($init)) {
                $output['js_startup'] = $init;
            }
        }

        return $output;
    }

    public static function jsonData($data, $include_cookies = false) {
        $output = [
            'data' => $data,
            'status' => 'success',
            'errors' => Messenger::getErrors(),
            'messages' => Messenger::getMessages(),
        ];
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
            $database = Database::getInstance(false);
            if ($database) {
                $output['database'] = [
                    'queries' => $database->getQueries(),
                    'time' => Performance::timeReport(),
                ];
            }
        }

        // Output the data.
        static::setContentType('application/json');
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
        $data = [
            'errors' => Messenger::getErrors(),
            'messages' => Messenger::getMessages(),
            'status' => 'error',
        ];

        if (!empty($error)) {
            $data['errors'][] = $error;
        }

        // Output the data.
        static::setContentType('application/json');
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
        self::error('Access Denied');
    }

    /**
     * Terminate the script with an error.
     *
     * @param string $error
     *   An error to output to the user.
     */
    public static function error($error) {
        if (static::isJSONRequest()) {
            static::json($error);
        } elseif (static::$isPlainText || Request::isCLI()) {
            $errors = Messenger::getErrors();
            echo implode($errors, "\n") . "\n";
        } else {
            Messenger::error($error);
            $template = Template::getInstance();
            if ($error_template = Configuration::get('template.error')) {
                $template->setTemplate($error_template);
            }
            Template::getInstance()->render('');
        }
        exit;
    }
    
    /**
     * Terminate the script with a success message.
     *
     * @param string $message
     *   A message to output to the user.
     */
    public static function success($message) {
        Messenger::message($message);
        if(static::isJSONRequest()) {
            static::json(static::SUCCESS);
        } else {
            Template::getInstance()->render('');
        }
        exit;
    }

    public static function notFound() {
        Messenger::error('Not Found');
        header('HTTP/1.0 404 NOT FOUND');
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
     *
     * @throws Exception
     *   If the headers have already been sent.
     */
    public static function setCookie($cookie, $value, $ttl = null, $path = '/', $domain = null, $secure = null, $httponly = true) {
        if (headers_sent()) {
            throw new Exception('Headers already sent.');
        }

        $settings = [
            'value' => $value,
            'ttl' => $ttl ? $ttl + time() : 0,
            'path' => $path,
            'domain' => $domain !== null ? $domain : Configuration::get('cookie_domain'),
            'secure' => $secure !== null ? $secure : (!empty($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) == 'on')),
            'httponly' => $httponly,
        ];

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
        self::$sentCookies = self::$cookies;
        self::$cookies = [];
    }

    /**
     * Disable output buffering for streaming output.
     *
     * @param boolean $kickstart
     *   Whether to output 9k of blank data to force apache to stream.
     */
    public static function disableBuffering($kickstart = true) {
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
        if ($kickstart) {
            // Send 9kb of output to force apache to start sending.
            echo str_repeat(' ', 9000);
        }
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
        Output::disableBuffering(false);
    }

    /**
     * Set the content type header.
     *
     * @param string $content_type
     *   The formatted content type, such as text/html.
     */
    public static function setContentType($content_type) {
        header('Content-Type: ' . $content_type);
    }

    public static function http($response_code, $editable = false) {
        // Attempt to load from ###.html
        http_response_code($response_code);

        // Use the Page handler for output.
        $page = new PageView();

        // Attempt to load a page from the database.
        $full_page = PageModel::loadByURL($response_code);
        if (empty($full_page)) {
            // If the page doesn't exist, fill it with default content.
            $full_page['title'] = $response_code . ' ' . self::$httpErrorMessages[$response_code];
            $full_page['keywords'] = '';
            $full_page['description'] = '';
            $full_page['url'] = $response_code;
            $full_page['body'] = '';
            $full_page['full_width'] = 0;
            $full_page['right_column'] = 1;
            $full_page['site_map'] = 0;
            $full_page['error'] = 404;
        }

        // Render the page and exit.
        $page->setPage($full_page);
        $page->prepare($editable);
        $page->output();
        exit;
    }
}
