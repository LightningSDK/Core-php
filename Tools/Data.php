<?php

namespace Lightning\Tools;

class Data {

    const GET = 1;
    const SET = 2;
    const PUSH = 3;
    const REMOVE = 4;

    /**
     * Find a variable in a data set using a path locator.
     *
     * $var = 'this.that.test'
     * will return
     * $dataset['this']['that']['test']
     *
     * @param string $var
     *   The name of the variable.
     * @param array $content
     *   The hierarchy of values to search.
     * @param mixed $default
     *   A default value.
     *
     * @return mixed
     */
    public static function getFromPath($var, &$content, $default = null) {
        return self::getFromPathArray(explode('.', $var), $content, $default);
    }

    public static function getFromPathArray($path, &$content, $default = null) {
        $next = array_shift($path);
        if (isset($content[$next])) {
            if (!empty($path)) {
                return self::getFromPathArray($path, $content[$next], $default);
            } else {
                return $content[$next];
            }
        } else {
            return $default;
        }
    }

    /**
     * Set a variable in a data set using a path locator.
     *
     * $var = 'this.that.test'
     * will set
     * $dataset['this']['that']['test']
     *
     * @param string $var
     *   The name of the variable.
     * @param array $value
     *   The new value to set.
     * @param array $content
     *   The hierarchy of values to fill.
     */
    public static function setInPath($var, $value, &$content) {
        self::processInPathArray(explode('.', $var), $value, $content, self::SET);
    }

    public static function pushInPath($var, $value, &$content) {
        self::processInPathArray(explode('.', $var), $value, $content, self::PUSH);
    }

    protected static function processInPathArray($path, $value, &$content, $action) {
        $next = array_shift($path);
        if (empty($path)) {
            switch ($action) {
                case self::PUSH:
                    $content[$next][] = $value;
                    return;
                case self::SET:
                    $content[$next] = $value;
                    return;
            }
            return;
        } elseif (!isset($content[$next]) || !is_array($content[$next])) {
            $content[$next] = [];
        }
        self::processInPathArray($path, $value, $content[$next], $action);
    }

    /**
     * Remove a variable from the data structure.
     *
     * @param string $var
     *   The name of the variable.
     * @param array $content
     *   The hierarchy of values to fill.
     */
    public static function removeFromPath($var, &$content) {
        self::removeFromPathArray(explode('.', $var), $content);
    }

    public static function removeFromPathArray($path, &$content) {
        $next = array_shift($path);
        if (isset($content[$next])) {
            if (!empty($path)) {
                self::removeFromPathArray($path, $content[$next]);
            } else {
                unset($content[$next]);
            }
        }
    }
}
