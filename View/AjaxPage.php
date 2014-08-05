<?php

namespace Lightning\View;

use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;

class AjaxPage {
    /**
     * A container for the data to output.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Output the data.
     */
    public function output() {
        Output::json(
            array(
                'data' => $this->data,
                'messages' => Messenger::getMessages(),
                'errors' => Messenger::getErrors(),
            )
        );
    }

    /**
     * Execute the callback.
     */
    public function execute() {
        $action = ucfirst(Request::get('action'));
        $request_type = strtolower(Request::type());

        if ($action) {
            if (in_array($request_type . $action, get_class_methods($this))) {
                $this->{$request_type . $action}();
                $this->output();
            }
            else {
                Messenger::error('There was an error processing your submission.');
            }
        } else {
            if (in_array($request_type, get_class_methods($this))) {
                $this->$request_type();
                $this->output();
            } else {
                $this->output = array();
                // TODO: show 302
                echo 'Method not available';
                exit;
            }
        }
    }
} 