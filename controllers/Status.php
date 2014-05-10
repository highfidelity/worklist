<?php

use \Michelf\Markdown;

class StatusController extends Controller {
    protected $token = GITHUB_API_TOKEN;
    protected $client = null;

    public function run() {
        $gh_events = array();
        try {
            $this->client = new Github\Client(
                new Github\HttpClient\CachedHttpClient(array(
                    'cache_dir' => TEMP_DIR . DIRECTORY_SEPARATOR . 'github'
                ))
            );
            $this->client->authenticate($this->token, '', Github\Client::AUTH_HTTP_TOKEN);

            // first: request github for 3 pages with 30 events data each
            $gh_events = array_merge(
                $this->listGithubEvents(GITHUB_ORGANIZATION, 1),
                $this->listGithubEvents(GITHUB_ORGANIZATION, 2),
                $this->listGithubEvents(GITHUB_ORGANIZATION, 3)
            );

            // then: find out $seconds_ago based on the last event in order to
            // use as date limit when looking for worklist entries
            $fromTime = strtotime(self::olderGithubEventDate($gh_events));
            $toTime = strtotime(Model::now());
        } catch(Exception $e) {
            error_log('StatusController::run exception: ' . $e->getMessage());
            $fromTime = strtotime('10 days ago');
            $toTime = strtotime(Model::now());
        }
        $seconds_ago = abs($toTime - $fromTime);
        $this->write('gh_events', $gh_events);

        // and, finally: fetches worklist data
        $entry = new EntryModel();
        $this->write('entries', $entry->latest($seconds_ago, 90));

        // alright, ready to go :)
        parent::run();
    }

    /**
     * Status api dispatcher
     */
    public function api() {
        $this->view = new JsonView();
        $ret = array();
        try {
            if (!isset($_REQUEST['action'])) {
                throw new Exception("Invalid action", 1);
            }
            $action = $_REQUEST['action'];
            switch($action) {
                case 'worklist_longpoll':
                    $ret = $this->worklist_longpoll();
                    break;
            }
            $ret = array(
                'success' => true,
                'data' => $ret
            );
        } catch(Exception $e) {
            $ret = array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
        $this->write('output', $ret);
    }

    /**
     * Worklist entries longpoll, retrieves new entries by simulating server pushes
     * to the status page. Refer to client side code at js/status.js to clarify.
     */
    public function worklist_longpoll() {
        $since = $_POST['since'];
        $entry = new EntryModel();
        $ret = array();

        // this is a 30 seconds timeout long poll, so let's loop up to 25 times
        // with 1 sec delays at the end of each iteration
        $fromTime = (int) $since;
        for ($i = 0; $i < 25; $i++) {
            $toTime = strtotime(Model::now());
            $seconds_ago = abs($toTime - $fromTime);

            // we are searching for new worklist entries
            $entries = $entry->latest($seconds_ago, 90);
            if ($entries) {
                $now = 0;
                foreach($entries as $entry) {
                    if (!$now) {
                        $now = strtotime(Model::now());
                    }
                    $date = strtotime($entry->date);
                    $relativeDate = relativeTime($date - $now);

                    $mention_regex = '/(^|\s)@(\w+)/';
                    $task_regex = '/(^|\s)\*\*#(\d+)\*\*/';
                    $content = preg_replace($mention_regex, '\1[\2](./user/\2)', $entry->entry);
                    $content = preg_replace($task_regex, '\1[\\\\#\2](./\2)', $content);
                    // proccesed entries are returned as markdown-processed html
                    $content = Markdown::defaultTransform($content);

                    $ret[] = array(
                        'id' => $entry->id,
                        'date' => $date,
                        'relativeDate' => $relativeDate,
                        'content' => $content
                    );
                }
                // if we found new entries, no need to keep looping so we can return data inmediatly
                break;
            }
            sleep(1);
        }
        return $ret;
    }

    /**
     * returns the older date from the latest status-renderable github event, used
     * when requesting for worklist entries (when setting a $seconds_ago date limit)
     */
    static function olderGithubEventDate($events) {
        $reverse_events = array_reverse($events);
        foreach($reverse_events as $event) {
            if (!preg_match('/^(Fork|PullRequest(ReviewComment)?|IssueComment)Event$/', $event['type'])) {
                continue;
            }
            return $event['created_at'];
        }
    }

    /**
     * Brings events from github for a specified organization/user at specified page.
     * Accorging to github api, each page request returns 30 entries.
     */
    protected function listGithubEvents($org, $page = 1) {
        try {
            $response = $this->client->getHttpClient()->get('orgs/' . $org . '/events?page=' . $page);
            $events = Github\HttpClient\Message\ResponseMediator::getContent($response);
            return $events;
        } catch(Exception $e) {
            return false;
        }
    }
}
