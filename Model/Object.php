<?php

namespace Lightning\Model;

class Object {
    /**
     * The primary key form the database.
     */
    const PRIMARY_KEY = '';

    /**
     * Build an object from a data array.
     *
     * @param array $data
     */
    public function __construct($data = array()) {
        $this->data = $data;
    }

    /**
     * Assist the getter function by checking for isset()
     *
     * @param string $var
     *   The name of the variable.
     *
     * @return boolean
     *   Whether the variable is set.
     */
    public function __isset($var) {
        switch($var) {
            case 'id':
            case 'data':
                return true;
                break;
            default:
                return isset($this->data[$var]);
                break;
        }
    }

    /**
     * A getter function.
     *
     * This works for:
     *   ->id
     *   ->data
     *   ->{primary_key} (item inside ->data)
     *
     * @param string $var
     *   The name of the requested variable.
     *
     * @return mixed
     *   The variable value.
     */
    public function __get($var) {
        switch($var) {
            case 'id':
                return $this->data[self::PRIMARY_KEY];
                break;
            case 'data':
                return $this->data;
                break;
            default:
                if(isset($this->data[$var]))
                    return $this->data[$var];
                else
                    return NULL;
                break;
        }
    }

    /**
     * A setter function.
     *
     * This works for:
     *   ->id
     *   ->data
     *   ->user_id (item inside ->data)
     *
     * @param string $var
     *   The name of the variable to set.
     * @param mixed $value
     *   The value to set.
     *
     * @return mixed
     *   The variable value.
     */
    public function __set($var, $value) {
        switch($var) {
            case 'id':
                $this->data['user_id'] = $value;
                break;
            case 'data':
                $this->data = $value;
                break;
            default:
                $this->data[$var] = $value;
                break;
        }
    }
}
