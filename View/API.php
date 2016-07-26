<?php

namespace Overridable\Lightning\View;

use Exception;
use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;

/**
 * The basic html page handler.
 *
 * @package Overridable\Lightning\View
 */
class API extends Page {

    public function __construct() {
        // Override parent method.
        Output::setJson(true);

        try {
            if (!$this->hasAccess()) {
                Output::error(Output::ACCESS_DENIED);
            }
        } catch (Exception $e) {
            Output::error($e->getMessage());
        }
    }

    public function hasAccess() {
        return true;
    }

    public function execute() {
        try {
            $request_type = strtolower(Request::type());

            // If there is a requested action.
            if ($action = Request::get('action')) {
                $method = Request::convertFunctionName($request_type, $action);
                if (method_exists($this, $method)) {
                    $output = $this->{$method}();
                }
                else {
                    throw new Exception('Method not available');
                }
            } else {
                if (method_exists($this, $request_type)) {
                    $output = $this->$request_type();
                } else {
                    throw new Exception('Method not available');
                }
            }

            Output::json($output);
        } catch (Exception $e) {
            Output::error($e->getMessage());
        }
    }
}
