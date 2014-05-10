<?php

class VisitQueryTools {
    /**
     * Get results for given job id
     */
    function getJobResults($jobid, $token, $ids) {
        $ids        = "ga:$ids";
        $metrics    = 'ga:visits,ga:pageViews';
        $segment    = "dynamic%3A%3Aga%3ApagePath%3D~workitem.php.*%3Fjob_id%3D$jobid.*";

        $query = "ids=$ids&metrics=$metrics&segment=$segment&start-date=2009-01-01&end-date=2100-01-01";
        return self::getResults($query, $token, $ids);
    }

    /**
     * Get results for all jobs
     */
    function getAllJobResults($token, $ids) {
        $ids        = "ga:$ids";
        $metrics    = 'ga:pageViews';
        $filter     = 'ga%3ApagePath%3D~%5E%2Fworklist%2F.*job_id';
        $sort       = '-ga%3Apageviews';
        $dimensions = 'ga:pagePath';
        $start      = date('Y-m-d', strtotime("-30 days"));
        $end        = date('Y-m-d');
        $results    = 150;

        $query = "ids=$ids&dimensions=$dimensions&metrics=$metrics&filters=$filter&sort=$sort&start-date=$start&end-date=$end&max-results=$results";
        return self::getResults($query, $token, $ids);
    }

    /**
     * perform api query
     */
    function getResults($query, $token, $ids) {

        /* The items feed URL, used for queries, insertions and batch commands. */
        $url = "https://www.google.com/analytics/feeds/data";


        $ch = curl_init();    /* Create a CURL handle. */
        /* Set cURL options. */
        curl_setopt($ch, CURLOPT_URL, "$url?$query");
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'GData-Version: 2',
        'Authorization: AuthSub token="' . $token . '"',
        ));
        // don't check ssl
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //no post
        curl_setopt($ch, CURLOPT_POST, FALSE);

        $return = Array();

        $return['result'] = curl_exec($ch);  /* Execute the HTTP request. */

        if(curl_errno($ch))
        {
          $return['error'] = curl_error($ch);
        }
        else
        {
         $return['info'] = curl_getinfo($ch);
        }
        curl_close($ch);           /* Close the cURL handle.    */

        return $return;
    }


    /**
     * parses the singlejob XML and returns the required values
     */
    function parseItem($result) {

        $data = Array();
        /* We only need the two values, so lets get and return them */
        /* no need to do anything more complex with xml parsing at this stage*/
        preg_match('/[\"|\']ga:pageviews[\"|\'].*?value=[\"|\']([0-9]+)[\"|\']/', $result, $matches);
        $data['views'] = $matches[1];
        preg_match('/[\"|\']ga:visits[\"|\'].*?value=[\"|\']([0-9]+)[\"|\']/', $result, $matches);
        $data['visits'] = $matches[1];
        return($data);

    }

    /**
     * parses the multijob XML and returns the required values
     */
    function parseItems($result) {

        $data = Array();

        /* all this is doing is pulling out the pagePath and pageviews */
        preg_match_all('/\'ga:pagePath\'.*?value=\'([^\']+)\'.+?\'ga:pageviews\'.*?value=\'([0-9]+)\'/',$result, $matches);

        $total = count($matches[1]);

        for($i=0;$i<$total;$i++) {
            preg_match('/job_id=([0-9]+)/', $matches[1][$i], $jobmatch);
            $data[] = array('url' => $matches[1][$i], 'visits' => $matches[2][$i], 'job' => $jobmatch[1]);

        }
        return($data);

    }
}