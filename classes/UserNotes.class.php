<?php

require_once('models/Note.php');

class UserNotes {

    public function __construct() {
    }

    /**
     * Get the note
     */
    public function getNote() {
        $ret = $this->validateRequest(array('userId'));
        $authorId = getSessionUserId();
        $reqUser = new User();
        if ($authorId > 0) {
            $reqUser->findUserById($authorId);
        } else {
            echo "You have to be logged in to access user info!";
        }

        $userId = (int) $_REQUEST['userId'];
        $note = new Note();
        if ($note->loadById($authorId,$userId) ){
            echo '<textarea class="form-control userNotes">' . $note->note . '</textarea>';
        }else{
            echo '<textarea class="form-control userNotes"></textarea>';
        }

        echo '<script type="text/javascript">$(".userNotes").autogrow();</script>';
        exit(0);
    }

    /**
     * Verify that the code entered by the user is the same in the database
     */
    public function saveUserNotes() {
        $ret = $this->validateRequest(array('userNotes','userId'));

        $authorId = getSessionUserId();
        $reqUser = new User();
        if ($authorId > 0) {
            $reqUser->findUserById($authorId);
        } else {
            $this->respond(false, "You have to be logged in to access user info!",'');
        }

        $userNotes = $_REQUEST['userNotes'];
        $userId = (int) $_REQUEST['userId'];
        $note = new Note();
        if ($note->loadById($authorId,$userId) ){
            if ($userNotes == "") {
                $userNotes = " ";
            }
            $note->note = $userNotes;
            if ($note->save('id')) {
                $this->respond(true, "Note saved.",'');
            } else {
                $this->respond(false, "Cannot update note! Please retry later.",'');
            }
        } else {
            if ($userNotes != "") {
                $values = array(
                    'author_id' => $authorId,
                    'user_id' => $userId,
                    'note' => $userNotes
                );

                if ($note->insertNew($values)) {
                    $this->respond(true, "Note saved.",'');
                } else {
                    $this->respond(false, "Cannot create new note! Please retry later.",'');
                }
            } else {
                    $this->respond(true, "New empty note is not saved.",'');
            }
        }
     }


    /**
     * Check that all the @fields were sent on the request
     * returns true/false.
     *
     * @fields has to be an array of strings
     */
    public function validateRequest($fields, $return=false) {
        // If @fields ain't an array return false and exit
        if (!is_array($fields)) {
            return false;
        }

        foreach ($fields as $field) {
            if (!isset($_REQUEST[$field])) {
                // If we specified that the function must return do so
                if ($return) {
                    return false;
                } else { // If not, send the default reponse and exit
                    $this->respond(false, "Not all params supplied.");
                }
            }
        }
    }


    /**
     * Sends a json encoded response back to the caller
     * with @succeeded and @message
     */
    public function respond($succeeded, $message, $params=null) {
        $response = array('succeeded' => $succeeded,
                          'message' => $message,
                          'params' => $params);
        echo json_encode($response);
        exit(0);
    }
}
