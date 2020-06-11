<?php

namespace lightningsdk\core\View;

use lightningsdk\core\Tools\Messenger;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\Tools\Request;

class AjaxPage {
    /**
     * A container for the data to output.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Output the data.
     */
    public function output() {
        Output::json(
            [
                'data' => $this->data,
                'messages' => Messenger::getMessages(),
                'errors' => Messenger::getErrors(),
            ]
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
                // TODO: This can probably be deleted.
                $this->output = [];
                // TODO: show 302
                echo 'Method not available';
                exit;
            }
        }
    }
} 