<?

namespace Overridable\Lightning\View;

use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\View\Page;

/**
 * The basic html page handler.
 *
 * @package Overridable\Lightning\View
 */
class API extends Page {

    public function __construct() {
        // Override parent method.
    }

    public function execute() {
        $request_type = strtolower(Request::type());

        // If there is a requested action.
        $output = [];
        if ($action = Request::get('action')) {
            $method = Request::convertFunctionName($request_type, $action);
            if (method_exists($this, $method)) {
                $output = $this->{$method}();
            }
            else {
                Messenger::error('Method not available');
            }
        } else {
            if (method_exists($this, $request_type)) {
                $output = $this->$request_type();
            } else {
                Messenger::error('Method not available');
            }
        }
        Output::json($output);
    }
}
