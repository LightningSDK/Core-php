<?php

namespace Lightning\Tools;

class Data {
    /**
     * Find a variable in a data set using a path locator.
     *
     * $var = 'this.that.test'
     * will return
     * $dataset['this']['that']['test']
     *
     * @param $var
     * @param $content
     * @param $default
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
}
