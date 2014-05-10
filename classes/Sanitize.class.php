<?php

class Sanitize {
    function filterInput() {
        self::filterRequestInput('notes', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
        self::filterRequestInput('status', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        self::filterRequestInput('summary', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
        self::filterRequestInput('bid_fee_desc', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
        self::filterRequestInput('invite', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        self::filterRequestInput('skills', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
        self::filterRequestInput('sandbox', FILTER_SANITIZE_URL, 0);
        self::filterRequestInput('skills', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
        self::filterRequestInput('nickname', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        self::filterRequestInput('city', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        self::filterRequestInput('comment', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
        self::filterRequestInput('done_in', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        self::filterRequestInput('bid_expires', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        self::filterRequestInput('fee_desc', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
        self::filterRequestInput('crfee_desc', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
        // pingtask.php
        self::filterRequestInput('msg', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
        self::filterRequestInput('withdraw_bid_reason', FILTER_SANITIZE_SPECIAL_CHARS, !FILTER_FLAG_STRIP_LOW);
        // api.php addProject
        self::filterRequestInput('description', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
    }

    function filterRequestInput($fieldName, $option=0, $flags=0) {
        if(isset($_REQUEST["$fieldName"])) {
            $_REQUEST[$fieldName] = filter_var($_REQUEST[$fieldName], $option, $flags);
        }
    }
}