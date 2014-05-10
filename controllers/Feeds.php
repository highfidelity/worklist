<?php

require_once('config.php');
require_once('Zend/Db.php');
require_once('Zend/Feed/Writer/Feed.php');

class FeedsController extends Controller {
    public function run() {
        $this->view = null;

        $config = Zend_Registry::get('config');
        // This does not make a db connection.  Must be done later using $db->getConnection();
        Zend_Registry::set('db', Zend_Db::factory($config->database));

        $format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'rss';
        $job_id = isset($_REQUEST['job_id']) ? $_REQUEST['job_id'] : false;

        if (!($format == 'atom' || $format == 'rss')) {
            $format = 'rss';
        }
        $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : 'completed';
        // Connect to db
        try {
            $db = Zend_Registry::get('db');
            $db->getConnection();
        } catch (Zend_Db_Adapter_Exception $e) {
            // failed login, db not running

        } catch (Zend_Exception $e) {
            // factory() failed load of mysqli
        }

        switch ($name) {
            case 'priority' :
                $name = 'priority';
                $title = 'Worklist Top Priority Bidding Jobs';
                $projects = isset($_REQUEST['projects']) ? preg_split('/,/', $_REQUEST['projects']) : array();
                $description = 'Worklist, highest priority jobs Bidding';
                $entryDescription = 'Worklist priority item';
                $cond = "w.status = 'Bidding'";
                $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit'] : 20;
                if (! empty($projects)) {
                    $projectList = '';
                    foreach ($projects as $project) {
                        $projectList .= (strlen($projectList) > 0 ? ',' : '') . "'" . addslashes($project) . "'";
                    }
                    $cond .= ' AND p.name IN (' . $projectList . ')';
                }
                $query = "
                    SELECT
                        w.id as worklist_id,
                        u1.nickname as author,
                        username as email,
                        summary as title,
                        notes as content
                    FROM " . WORKLIST . " w
                        JOIN " . USERS . " u1 ON u1.id = w.creator_id
                        JOIN " . PROJECTS . " p ON p.project_id = w.project_id
                    WHERE " . $cond . "
                    ORDER BY priority
                    LIMIT " . $limit;
                break;
            case 'comments' :
                $name = 'comments';
                if (isset($job_id) && $job_id) {
                    $where = "WHERE worklist_id = " . $job_id;
                    $title = 'Worklist - Comments for Job #' . $job_id;
                    $description = 'Worklist, latest Comments for Job #' . $job_id;
                    $entryDescription = 'Worklist latest Comments for Job #' . $job_id;
                } else {
                    $where = "";
                    $title = 'Worklist Latest Comments';
                    $description =  'Worklist, latest comments';
                    $entryDescription = 'Worklist latest comments';
                }
                $query = "
                    SELECT
                        c.id AS comment_id,
                        c.worklist_id AS worklist_id,
                        c.date AS timestamp,
                        c.comment AS summary,
                        c.comment AS content,
                        u.nickname as author,
                        u.username as email
                    FROM ".COMMENTS." c
                    JOIN ".USERS." u on u.id = c.user_id
                    " . $where . "
                    ORDER BY timestamp DESC LIMIT 20";
                break;
            case 'completed' :
            default :
                $name = 'completed';
                $title = 'Worklist most Recent completed jobs';
                $description = 'Worklist, Most recent completed Jobs';
                $entryDescription = 'Worklist priority item';
                $query = "SELECT w.id as worklist_id, u1.nickname as author, username as email, summary as title, notes as content
                            FROM ".WORKLIST." w
                            JOIN ".USERS." u1 ON u1.id = w.creator_id AND w.status = 'Done'
                            ORDER BY created DESC LIMIT 20";
        }

        $url = WORKLIST_URL;
        $link = $url . "feed?name=$name&format=$format";
        $writer = new Zend_Feed_Writer_Feed;
        $writer->setTitle($title);
        $writer->setLink(WORKLIST_URL);
        $writer->setFeedLink($link, $format);
        $writer->setDescription($description);
        $writer->setDateModified(time()); //Atom needs this
        $this->loadFeed($writer, $query, $entryDescription);

        echo $writer->export($format);
    }

    private function addEntry($writer, $entryData, $entryDescription) {
        global $name;

        $entry = $writer->createEntry();
        $entry->setLink(SERVER_URL . $entryData['worklist_id']);
        // must supply a non-empty value for description
        $content = !empty($entryData['content']) ? html_entity_decode($entryData['content'], ENT_QUOTES) : "N/A";
        $entry->setDescription($content);
        $entry->addAuthor($entryData['author'], $entryData['email']);

        if ($name == 'comments') {
            $entry->setTitle($entryData['author'] . ' added a comment to job #' . $entryData['worklist_id']);
            $entry->setDateCreated(strtotime($entryData['timestamp']));
            $entry->setDateCreated(strtotime($entryData['timestamp']));
        } else {
            $entry->setTitle(html_entity_decode($entryData['title'], ENT_QUOTES));
            $entry->setDateCreated(time());
            $entry->setDateModified(time());
        }
        $writer->addEntry($entry); // manual addition post-creation
    }

    private function loadFeed($writer, $query, $entryDescription) {
        $db = Zend_Registry::get('db');
        $result = $db->fetchAll($query);
        if ($result) {
            foreach ($result as $row) {
                $this->addEntry($writer, $row, $entryDescription);
            }
        }
    }
}
