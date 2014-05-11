<?php

class AppObject {
    static protected $values = array();

    /**
     * Read a value added from controller/logic side,
     * should be called from the view side
     */
    public function read($key, $default = null) {
        if (! array_key_exists($key, self::$values)) {
            return $default;
        }
        return self::$values[$key];
    }

    /**
     * Add/overwrites a value to be read in the view,
     * should be called from a controller/logic side
     */
    public function write($key, $value) {
        $ret = array_key_exists($key, self::$values) ? true : 1;
        try {
            self::$values[$key] = $value;
        } catch (Exception $e) {
            $ret = false;
        }
        return $ret;
    }
}
