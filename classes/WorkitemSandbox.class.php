<?php

class WorkitemSandbox extends Ajax {

    public function getDiffUrlView() {
        $this->validateRequest(array('sandbox_url', 'workitem_id'));

        $sandbox_url = urldecode($_REQUEST['sandbox_url']);
        $workitem_id = urldecode($_REQUEST['workitem_id']);

        if ($sandbox_url) {
            $sandbox_array = explode("/", $sandbox_url);

            $username = $sandbox_array[3];
            $username = substr($username, 1); // eliminate the tilde

            $sandbox = $sandbox_array[4];

            try {
                $url = SandBoxUtil::pasteSandboxDiff($username, $workitem_id, $sandbox);
                $result = '<a href="' . $url . '" target="_blank">' . $url . '</a>';
            } catch (Exception $ex) {
                if (strpos($ex, 'No changes found') !== false) {
                    $result =  '<div class="errorMsg">No changes made yet to sandbox</div>';
                } else {
                    $result =  '<div class="errorMsg">Error pasting diff</div>';
                }
                error_log("Could not paste diff: \n$ex");
            }
        } else {
            $result = '<p class="info-label">Please provide sandbox url:<br />
                           <input type="text" id="diff-sandbox-url" class="text-field"/>
                       </p>';
        }

        echo '<div id="urlContent">' . $result . '</div>';
    }
}
