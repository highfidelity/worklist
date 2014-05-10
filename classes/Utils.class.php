<?php

class Utils{

    public static $keys = array(
        "about",
        "contactway",
        "payway",
        "skills",
        "timezone",
        "phone",
        "smsaddr",
        "country",
		"city",
        "provider",
        "paypal_email",
        "sms_flags",
        "findus",
        "int_code",
        "notifications"
    );
    public static function registerKey($key){
        return in_array($key, self::$keys);
    }

    public static function setUserSession($id, $username, $nickname, $admin) {
        $_SESSION["userid"]   = $id;
        $_SESSION["username"] = $username;
        $_SESSION["nickname"] = $nickname;
        $_SESSION["admin"]    = $admin;
        // user just logged  in, let's update the last seen date in session
        // date will be checked against db in initUserById
        $_SESSION['last_seen'] = date('Y-m-d');

        initSessionDataByUserId($id);
    }

    function currentPageUrl() {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") { $pageURL .= "s"; }

        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

    /**
     * Returns a random string
     *
     * A-Z, a-z, 0-9:
     * Functions::randomString(10, 48, 122, array(58, 59, 60, 61, 62, 63, 64, 91, 92, 93, 94, 95, 96))
     *
     * Default is any printable ASCII character without whitespaces
     *
     * @param int   $len  The length of the string
     * @param int   $from The ASCII decimal range start
     * @param int   $to   The ASCII decimal range stop
     * @param array $skip ASCII decimals to skip
     *
     * @return string Random string
     */
    public static function randomString($len, $from = 33, $to = 126, $skip = array()) {
        $str = '';
        $i = 0;
        while ($i < $len) {
            $dec = rand($from, $to);
            if (in_array($dec, $skip))
                continue;
            $str .= chr($dec);
            $i++;
        }
        return $str;
    }

    /**
     * Encrypts a cleartext password via the crypt() function
     *
     * @param string $clearText Cleartext password
     * @return string Encrypted password
     */
    public static function encryptPassword($clearText) {
        switch (true) {
        case (defined('CRYPT_SHA512') && CRYPT_SHA512 == 1):
            $salt = '$6$' . self::randomString(16);
            break;

        case (defined('CRYPT_SHA256') && CRYPT_SHA256 == 1):
            $salt = '$5$' . self::randomString(16);
            break;

        case (defined('CRYPT_MD5') && CRYPT_MD5 == 1):
            $salt = '$1$' . self::randomString(12);
            break;

        case (defined('CRYPT_STD_DES') && CRYPT_STD_DES == 1):
            $salt = self::randomString(
                2,
                48,
                122,
                array(58, 59, 60, 61, 62, 63, 64, 91, 92, 93, 94, 95, 96)
            );
            break;
        }

        return crypt($clearText, $salt);
    }

    public static function redirect($url, $app_relative = true) {
        $redirect = ($app_relative ? WORKLIST_URL : '') . $url;
        header('Location: ' . $redirect);
        die;
    }

    /**
     * International phone number validation
     */
    public static function validPhone($number) {
        $number = preg_replace('/[_()\ -]+/', '', $number);
        return preg_match('/^[0-9]{6}[0-9]+$/', $number);
    }
}
