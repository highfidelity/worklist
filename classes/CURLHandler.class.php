<?php
/**
 * CURLHandler handles simple HTTP GETs and POSTs via Curl
 *
 * @author SchizoDuckie
 * @version 1.0
 * @access public
 */
class CURLHandler {

    /**
     * CURLHandler::Get()
     *
     * Executes a standard GET request via Curl.
     * Static function, so that you can use: CurlHandler::Get('http://www.google.com');
     *
     * @param string $url url to get
     * @return string HTML output
     */
    public static function Get($url, $vars = array(), $async = false){
        if(count($vars) > 0){
            $url .= '?' . http_build_query($vars);
        }
        return self::doRequest('GET', $url, array(), false, false, $async);
    }

    /**
     * CURLHandler::Post()
     *
     * Executes a standard POST request via Curl.
     * Static function, so you can use CurlHandler::Post('http://www.google.com', array('q'=>'belfabriek'));
     * If you want to send a File via post (to e.g. PHP's $_FILES), prefix the value of an item with an @ !
     * @param string $url url to post data to
     * @param Array $vars Array with key=>value pairs to post.
     * @return string HTML output
     */
    public static function Post($url, $vars, $auth = false, $login = false, $async = false){
        return self::doRequest('POST', $url, $vars, $auth, $login, $async);
    }

    /**
     * CURLHandler::doRequest()
     * This is what actually does the request
     * <pre>
     * - Create Curl handle with curl_init
     * - Set options like CURLOPT_URL, CURLOPT_RETURNTRANSFER and CURLOPT_HEADER
     * - Set eventual optional options (like CURLOPT_POST and CURLOPT_POSTFIELDS)
     * - Call curl_exec on the interface
     * - Close the connection
     * - Return the result or throw an exception.
     * </pre>
     * @param mixed $method Request Method (Get/ Post)
     * @param mixed $url URI to get or post to
     * @param mixed $vars Array of variables (only mandatory in POST requests)
     * @param mixed $auth HTTP Authentication data (optional)
     * @param mixed $login
     * @param mixed $async boolean whether to make an asynchronous call or not
     * @return string HTML output
     */
    public static function doRequest($method, $url, $vars = array(), $auth = false, $login = false, $async = false){
        $curlInterface = curl_init();

        if ($login){
            curl_setopt_array($curlInterface, array(CURLOPT_URL => $url, CURLOPT_SSLVERSION => 3, CURLOPT_SSL_VERIFYPEER => FALSE, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_HEADER => 0));
        } else {
            curl_setopt_array($curlInterface, array(CURLOPT_URL => $url, CURLOPT_SSLVERSION => 3, CURLOPT_SSL_VERIFYPEER => FALSE, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_RETURNTRANSFER => 0, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_HEADER => 0));
        }
            curl_setopt_array($curlInterface, array(CURLOPT_URL => $url, CURLOPT_SSLVERSION => 3, CURLOPT_SSL_VERIFYPEER => FALSE, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_HEADER => 0));

        if (strtoupper($method) == 'POST'){
            curl_setopt_array($curlInterface, array(CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($vars)));
        }
        if ($auth !== false){
            curl_setopt($curlInterface, CURLOPT_USERPWD, $auth['username'] . ":" . $auth['password']);
        }
        if ($async) {
            curl_setopt($curlInterface, CURLOPT_FRESH_CONNECT, true);
            // in order to return the control to the main thread in async calls
            // let's give it a timeout of 2 seconds (enough to reach the server)
            curl_setopt($curlInterface, CURLOPT_TIMEOUT, 2);
        }
        //error_log("Curl invoked:".$url);
        $result = curl_exec($curlInterface);

        if($result === false ){
            error_log("Curl failed calling:".$url . ': ' . curl_error($curlInterface) . ' (' . curl_errno($curlInterface) . ')');
            curl_close($curlInterface);
            $output = array("error" => 1, "message" => 'We seem to be having some trouble with our connection. Please, let us know about it :<a href="mailto: admin@worklist.net">admin@lovemachineinc.com</a>'  );
            $output = json_encode($output);
            return $output;
        } else {
            curl_close($curlInterface);
            return ($result);
        }
    }

}
