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

        if (!$this->hasAccess()) {
            Output::error(Output::ACCESS_DENIED);
        }
    }

    public function hasAccess() {
        return true;
    }

    public function execute() {
        $request_type = strtolower(Request::type());

        // If there is a requested action.
        $output = [];
        if ($action = Request::get('action')) {
            $method = Request::convertFunctionName($request_type, $action);
            if (method_exists($this, $method)) {
                try {
                    $output = $this->{$method}();
                } catch (Exception $e) {
                    Output::error($e->getMessage());
                }
            }
            else {
                Messenger::error('Method not available');
            }
        } else {
            if (method_exists($this, $request_type)) {
                try {
                    $output = $this->$request_type();
                } catch (Exception $e) {
                    Output::error($e->getMessage());
                }
            } else {
                Messenger::error('Method not available');
            }
        }
        Output::json($output);
    }
}
