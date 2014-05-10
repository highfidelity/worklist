<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

/**
 * ShortUrl
 *
 * @package ShortUrl
 */
class ShortUrl {
    protected $apiKey = 'api_key';
    protected $login = 'login_bitly';
    protected $longUrl = '';
    protected $shortUrl = '';

    protected $version = '2.0.1';

    /**
     * Constructor
     *
     * @param String $url The url to be shortened
     */
    public function __construct($url)
    {
        if (defined('BITLY_USERNAME')) {
            $this->login = BITLY_USERNAME;
        }
        if (defined('BITLY_APIKEY')) {
            $this->apiKey = BITLY_APIKEY;
        }
        $this->longUrl = $url;
    }

    /**
     * Fetch the short url from google shortener
     */
    public function getShortUrl()
    {
        if (parse_url($this->longUrl) !== false) {
            if (strlen($this->shortUrl) > 0) {
                return $this->shortUrl;
            }


            $params = http_build_query (array(
                'version'   => $this->version,
                'login'     => $this->login,
                'apiKey'    => $this->apiKey,
                'longUrl'   => $this->longUrl,
                'format'    => 'json'
            ));

            $url = 'http://api.bit.ly/shorten?' . $params;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, $url);
            $contents = curl_exec ($curl);
            curl_close($curl);

            $data = json_decode($contents);
            if (! count($data->results) > 0) {
                return '';
            }
            // only get the first array item from $data->results in $url_data
            foreach ($data->results as $longUrl => $url_data) {
                break;
            }
            return $url_data->shortUrl;
        } else {
            return null;
        }
    }
}
