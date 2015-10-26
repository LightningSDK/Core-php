<?php

namespace Lightning\Model;

trait ObjectDataStorage {
    /**
     * The data storage container.
     *
     * @var array
     */
    protected $__data = array();

    /**
     * To track changes in the data container.
     *
     * @var array
     */
    protected $__changed = array();

    protected $__changed_all = false;

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
                return !empty($this->__data[static::PRIMARY_KEY]);
                break;
            case 'data':
                return true;
                break;
            default:
                return isset($this->__data[$var]);
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
                if (!empty($this->__data[static::PRIMARY_KEY])) {
                    return $this->__data[static::PRIMARY_KEY];
                } else {
                    return false;
                };
                break;
            case 'data':
                return $this->__data;
                break;
            default:
                if (isset($this->__data[$var]))
                    return $this->__data[$var];
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
                $this->__data[static::PRIMARY_KEY] = $value;
                break;
            case 'data':
                $this->__changed_all = true;
                $this->__data = $value;
                break;
            default:
                $this->__changed[] = $var;
                $this->__data[$var] = $value;
                break;
        }
    }
}
