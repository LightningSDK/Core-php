<?php

namespace Lightning\Tools;

use Exception;
use Lightning\View\Field;
use Lightning\View\Field\BasicHTML;

class Form {
    protected $id = 'form';
    protected $fields = [];
    protected $settings = [];
    protected $submittedValues = [];
    protected $valid;

    public function __construct($id = null, $fields = null, $settings = null) {
        if (!empty($id)) {
            $this->id = $id;
        }
        if (!empty($fields)) {
            $this->fields = $fields;
        }
        if (!empty($settings)) {
            $this->settings = $settings;
        }
    }

    /**
     * Render the entire form contents.
     *
     * @return string
     *   Fully rendered form HTML.
     *
     * @todo This needs to implement rendering a default table/form
     *   structure for basic form with fields, as well as loading a
     *   custom form template, and elements with custom types.
     */
    public function render() {
        $output = $this->open();
        // TODO: Iterate over all fields here.
        $output .= '</form>';
        return $output;
    }

    public function open() {
        $output = '<form method="post" action="' . (!empty($this->settings['action']) ? $this->settings['action'] : '') . '">';
        $output .= self::renderTokenInput();
        return $output;
    }

    public function renderField($field) {
        // Make sure the field exists.
        if (!isset($this->fields[$field])) {
            throw new Exception('Invalid Field');
        }

        // Set the default type.
        if (!isset($this->fields[$field]['type'])) {
            $this->fields[$field]['type'] = 'text';
        }
        // Get the right default value.
        if (!empty($this->submittedValues)) {
            $default = !empty($this->submittedValues[$field]) ? $this->submittedValues[$field] : '';
        } else {
            $default = !empty($this->fields[$field]['default']) ? $this->fields[$field]['default'] : '';
        }
        // See if there are any additional attributes.
        $attributes = !empty($this->fields[$field]['attributes']) ? $this->fields[$field]['attributes'] : [];

        // Make sure the name and id exist.
        switch ($this->fields[$field]['type']) {
            case 'select':
                return BasicHTML::select($field, $this->fields[$field]['options'], $default, $attributes);
                break;
            case 'radio':
                return BasicHTML::radioGroup($field, $this->fields[$field]['options'], $default, $attributes);
            case 'password':
                // Passwords should not be directly republished, but might be cached on the server.
                $default = !empty($this->fields[$field]['cached']) ? str_repeat('*', strlen($default)) : '';
                return BasicHTML::password($field, $default, $attributes);
            case 'textarea':
                return BasicHTML::textarea($field, $default, $attributes);
            case 'text':
            default:
                return BasicHTML::text($field, $default, $attributes);
        }
    }

    public static function validateToken() {
        $expected = Session::getInstance()->getToken();
        $actual = Request::post('token', 'base64');
        if (empty($actual) || $actual != $expected) {
            throw new Exception('Invalid Token.');
        }
        return true;
    }

    /**
     * Fill the post array. For setting defaults without overwriting the actual defaults.
     *
     * @param array $data
     */
    public function setPostedValues($data) {
        if (is_array($data)) {
            $this->submittedValues = $data + $this->submittedValues;
        }
    }

    public function getPostedValues() {
        return $this->submittedValues;
    }

    public function validate() {
        $this->valid = true;
        foreach ($this->fields as $field => $settings) {
            $field_name = !empty($settings['name']) ? $settings['name'] : $field;
            if (!empty($settings['validation'])) {
                if (is_callable($settings['validation'])) {
                    // This field uses a custom validation method.
                    $sanitized_value = $_POST[$field];
                    $this->valid &= call_user_func($settings['validation'], $sanitized_value);
                    $this->submittedValues[$field] = $sanitized_value;
                    continue;
                }
                $type = !empty($settings['validation']['type']) ? $settings['validation']['type'] : '';
                $posted_value = Request::post($field, $type);

                // Check other constraints.
                if (in_array($type, ['float', 'int', 'decimal'])) {
                    if (isset($settings['validation']['positive']) && $settings['validation']['positive'] == false) {
                        if ($posted_value > 0) {
                            $posted_value = null;
                        }
                    }
                    if (isset($settings['validation']['negative']) && $settings['validation']['negative'] == false) {
                        if ($posted_value < 0) {
                            $posted_value = null;
                        }
                    }
                    if (isset($settings['validation']['zero']) && $settings['validation']['zero'] == false) {
                        if ($posted_value == 0) {
                            $posted_value = null;
                        }
                    }
                }

                // Make sure the field is present if required.
                if (!empty($settings['validation']['required']) && empty($_POST[$field])) {
                    Messenger::error('The field ' . $field_name . ' is required.');
                    $this->submittedValues[$field] = Request::post($field);
                    $this->valid = false;
                    continue;
                }

                // Make sure the value is the right format.
                if ($posted_value === null && !empty($_POST[$field])) {
                    Messenger::error('Please enter a valid ' . $field_name . '.');
                    $this->submittedValues[$field] = Request::post($field);
                    $this->valid = false;
                    continue;
                } else {
                    $this->submittedValues[$field] = $posted_value;
                }
            } else {
                $this->submittedValues[$field] = Request::post($field);
            }
        }
        return $this->valid;
    }

    /**
     * Make sure a session is started so there can be a token.
     */
    public static function requiresToken() {
        Session::getInstance(true);
    }

    /**
     * Render a hidden token field.
     *
     * @return string
     *   The full HTML.
     */
    public static function renderTokenInput() {
        if (Session::isInitialized()) {
            return BasicHTML::hidden('token', Session::getInstance()->getToken());
        } else {
            return '<small class="error" style="display: block">Session Not Initialized</small>';
        }
    }
}
