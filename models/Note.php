<?php
/**
 * Worklist
 * Copyright (c) 2010 LoveMachine, LLc.
 * All rights reserved.
 */
if (!defined('NOTES'))   define('NOTES', 'notes');

class Note extends DataObject {
    public $id;
    public $user_id;
    public $author_id;
    public $note;

    public $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->table_name = NOTES;
    }

    /**
     * Destructor
     */
    public function __destructor() {
        parent::__destruct();
    }


    /**
     * Load a note by id
     */
    public function loadById($authorId,$user_id) {
        $objectData = $this->dbFetchArray(" `author_id`={$authorId} AND `user_id`={$user_id} ");
        return $this->loadObject($objectData);
    }

    /**
     * Get an index for note
     */
    public function getIndex() {
        $objectData = $this->dbFetchArray(" `id` > 0 ORDER BY `id` ");

        if (!$objectData && is_array($objectData)) {
            return null;
        }

        return $objectData;
    }

    public function insertNew($values) {
        return $this->dbInsert($values);
    }

    public function updateNote($sql) {
        return $this->dbUpdate($sql);
    }
}
?>
