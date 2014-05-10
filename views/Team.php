<?php

class TeamView extends View {
    public $layout = 'NewWorklist';
    public $title = 'Team Members - Worklist';
    public $stylesheets = array(
        'css/team.css'
    );

    public $scripts = array(
        'js/jquery/jquery.timeago.js',
        'js/jquery/jquery.metadata.js',
        'js/team.js'
    );

    public function render() {
        $this->cur_letter = $this->read('cur_letter');
        $this->cur_page = $this->read('cur_page');
        $showUser = $this->read('showUser');
        $showUserTab = $this->read('showUserTab');
        if($showUser) {
            $tab = "";
            if($showUserTab) {
                $tab = "?tab=" . $_REQUEST['tab'];
            }
            $this->showUserLink = "./user/" . $showUser . $tab;
        }

        return parent::render();
    }

    public function newStats() {
        $newStats = $this->read('newStats');
        return
            ($newStats['newUsers'] == 0 ? 'no' : $newStats['newUsers']) . ' user' . ($newStats['newUsers'] > 1 ? 's have' : ' has') . ' signed up,<br />' .
            ($newStats['newUsersLoggedIn'] == 0 ? 'no one' : $newStats['newUsersLoggedIn']) . ($newStats['newUsersLoggedIn'] > 1 ? ' have' : ' has') . ' logged in,<br />' .
            ($newStats['newUsersWithFees'] == 0 ? 'no one' : $newStats['newUsersWithFees']) . ($newStats['newUsersWithFees'] > 1 ? ' have' : ' has') . ' added fees &amp;<br />' .
            ($newStats['newUsersWithBids'] == 0 ? 'no one' : $newStats['newUsersWithBids']) . ($newStats['newUsersWithBids'] > 1 ? ' have' : ' has') . ' added bids';
    }

}
