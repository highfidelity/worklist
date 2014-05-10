<?php

class SettingsView extends View {
    public $layout = 'NewWorklist';
    public $title = 'Account Settings - Worklist';
    public $stylesheets = array(
        'css/settings.css'
    );

    public $scripts = array(
        'js/ajaxupload/ajaxupload.js',
        'js/skills.js',
        'js/userSkills.js',
        'js/sendlove.js',
        'js/utils.js',
        'js/settings.js'
    );

    public function render() {
        $this->user = $this->read('user');
        $this->userInfo = $this->read('userInfo');

        return parent::render();
    }

    public function timezoneSelectBox() {
        global $timezoneTable;
        $userInfo = $this->read('userInfo');

        $ret = '<select id="timezone" name="timezone">';
        foreach($timezoneTable as $key => $value) {
            $selected = '';
            if ($key == $userInfo['timezone']) {
                $selected = 'selected = "selected"';
            }
            $ret .= '<option value = "'.$key.'" '.$selected.'>'.$value.'</option>';
        }
        $ret .= '</select>';
        return $ret;
    }

    public function countrySelectBox() {
        global $countrylist;
        $userInfo = $this->read('userInfo');

        $ret = '<select id="country" name="country">';
        foreach($countrylist as $code => $name) {
            $selected = '';
            if ($code == $userInfo['country']) {
                $selected = 'selected = "selected"';
            }
            $ret .= '<option value = "'.$code.'" '.$selected.'>'.$name.'</option>';
        }
        $ret .= '</select>';
        return $ret;
    }

    public function picture() {
        $userInfo = $this->read('userInfo');
        return APP_IMAGE_URL . $userInfo['picture'];

    }

    public function receivesBiddingJobsAlerts() {
        $userInfo = $this->read('userInfo');
        $notifications = $userInfo['notifications'];
        return Notification::isNotified($notifications, Notification::BIDDING_EMAIL_NOTIFICATIONS);
    }

    public function receivesReviewJobsAlerts() {
        $userInfo = $this->read('userInfo');
        $notifications = $userInfo['notifications'];
        return Notification::isNotified($notifications, Notification::REVIEW_EMAIL_NOTIFICATIONS);
    }

    public function receivesSelfActionsAlerts() {
        $userInfo = $this->read('userInfo');
        $notifications = $userInfo['notifications'];
        return Notification::isNotified($notifications, Notification::SELF_EMAIL_NOTIFICATIONS);
    }

    public function ppConfirmed() {
        return (int) isset($_REQUEST['ppconfirmed']);
    }

    public function emConfirmed() {
        return (int) isset($_REQUEST['emconfirmed']);
    }

    public function uploadApiKey() {
        return API_KEY;
    }

}
