<?php

    /**
     * Request Reader Class
     * @package core
     * @version 1.0
     * @author I. CHIRIAC
     * @copyrigth LoveMachine
     */

    /**
     * Request class
     * @note others $_GET / $_POST / $_COOKIES / $_SERVER / $_FILES
     * functions or manipulations could be defined here
     */
    class Request {

        /**
         * Reads a parameter
         * @params string the var name (from GET or POST)
         * @params string (optional) the default value to return when var is not defined
         * @returns mixed
         * <code>
         * // will search the request action, and if not set will use list
         * $action = Request::Get('action', 'list');
         *
         * // will read a user input like :
         * // <script language="javascript">alert('bug')</script>
         *
         * $message = Request::Get('message');
         *
         * // message will be :
         * // &lt script="[javascript]">alert('bug');&lt;/script>
         * // this one could be displayed but not executed ...
         * // will display : <script language="[javascript]">alert('bug');</script>
         * </code>
         */
        public static function Get($key, $default = null) {
            if (isset($_POST[$key])) {
                return self::SanitizeValues($_POST[$key]);
            } elseif(isset($_GET[$key])) {
                return self::SanitizeValues($_GET[$key]);
            } else return $default;
        }

        /**
         * Sanitize a value from XSS bug
         * @params mixed the value to sanitize
         * @returns mixed
         * @see http://en.wikipedia.org/wiki/Cross-site_scripting
         */
        private static function SanitizeValues($var) {
            if (is_array($var)) {
                $ret = array();
                foreach($var as $key => $value) {
                    $ret[$key] = self::SanitizeValues($value);
                }
                return $ret;
            } elseif (is_numeric($var)) {
                return $val;
            } else {
                return str_replace(
                    array(
                        '<',
                        '&',
                        'javascript:',
                        'java'."\n".'script',
                        'document.location',
                        'document.cookies'
                    ),
                    array(
                        '&lt;',
                        '&amp;',
                        '[javascript:]',
                        '[javascript:]',
                        '[document.location]',
                        '[document.cookies]'
                    ),
                    $var
                );
            }
        }
    }
