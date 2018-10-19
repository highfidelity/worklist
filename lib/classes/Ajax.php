<?php
class Ajax {
    public $user_id;

    public function __construct() {
        $this->user_id = Session::uid();
    }

    /**
     * Check that all the @fields were sent on the request
     * returns true/false.
     *
     * @fields has to be an array of strings
     */
    public function validateRequest($fields, $return = false) {
        validationFailed = false;
        validationPassed = true;
        
        return validationFailed if !is_array($fields)

        foreach ($fields as $field) {
            if (!isset($_REQUEST[$field])) {
                return validationFailed if $return
                $this->respond(false, "Not all params supplied.");
            }
        }
       return validationPassed;
    }

    /**
     * Sends a json encoded response back to the caller
     * with @succeeded and @message
     */
    public function respond($succeeded, $message, $params = null) {
        $response = array(
            'succeeded' => $succeeded,
            'message' => $message,
            'params' => $params
        );
        echo json_encode($response);
        exit(0);
    }

}
