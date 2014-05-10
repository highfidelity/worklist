<?php

use \Michelf\Markdown;

class StatusView extends View {
    public $layout = 'NewWorklist';
    public $title = "Status - Worklist";
    public $stylesheets = array(
        'css/entries.css',
        'css/status.css'
    );
    public $scripts = array(
        'js/entries.js',
        'js/status.js'
    );

    public function render() {
        return parent::render();
    }

    public function biddingJobsCount() {
        $stats = getStats('currentlink');
        return $stats['count_b'];
    }

    public function entries() {
        $worklist_entries = array_reverse($this->read('entries'));
        $gh_events = $this->read('gh_events');
        $entries = array_merge($worklist_entries, $gh_events);
        usort($entries, array('StatusView', 'sortEntries'));
        $ret = '';
        $now = 0;

        foreach($entries as $entry) {
            if (!$now) {
                $now = strtotime(Model::now());
            }
            if (get_class($entry) == 'EntryModel') {
                $id = $entry->id;
                $type = 'worklist';
                $date = strtotime($entry->date);
                $content = self::formatWorklistEntry($entry);
            } else { // github event
                if (!preg_match('/^(Fork|PullRequest(ReviewComment)?|IssueComment)Event$/', $entry['type'])) {
                    continue;
                }
                $id = $entry['id'];
                $type = 'github' . preg_replace('/Event$/', '', $entry['type']);
                $date = strtotime($entry['created_at']);
                $content = self::formatGithubEntry($entry);
            }

            $ret .=
                  '<li entryid="' . $id . '" date="' . $date . '" type="' . $type . '">'
                .     '<h4>' . relativeTime($date - $now) . '</h4>'
                .     $content
                . '</li>';
        }
        return $ret;
    }

    static function sortEntries($a, $b) {
        if (get_class($a) == 'EntryModel') {
            $date_a = strtotime($a->date);
        } else { // github event
            $date_a = strtotime($a['created_at']);
        }
        if (get_class($b) == 'EntryModel') {
            $date_b = strtotime($b->date);
        } else { // github event
            $date_b = strtotime($b['created_at']);
        }
        return ($date_a == $date_b) ? 0 : ($date_a > $date_b ? -1 : +1);
    }

    /**
     * same than JobView::formatEntry
     * @todo: integrate with JobView::formatEntry
     */
    static function formatWorklistEntry($entry) {
        $ret = $entry->entry;

        // will only process new entries since #19490 deployment
        // @todo, remove this condition once history is removed/older
        if (strtotime($entry->date) > strtotime('2014-03-06 00:00:00')) {
            // linkify mentions and tasks references
            $ret = $ret;
            $mention_regex = '/(^|\s)@(\w+)/';
            $task_regex = '/(^|\s)\*\*#(\d+)\*\*/';
            $ret = preg_replace($mention_regex, '\1[\2](./user/\2)', $entry->entry);
            $ret = preg_replace($task_regex, '\1[\\\\#\2](./\2)', $ret);
            // proccesed entries are returned as markdown-processed html
            $ret = Markdown::defaultTransform($ret);
        }

        return $ret;
    }

    static function formatGithubEntry($entry) {
        $ret = '*GIT*: ';
        extract($entry);
        switch($type) {
            case 'ForkEvent':
                $author = self::markdownMention($actor['login'], true);
                $fork_name = $payload['forkee']['full_name'];
                $fork_url = $payload['forkee']['html_url'];
                $action = ' forked [' . $fork_name . '](' . $fork_url . ')';
                $ret .= $author . $action;
                break;
            case 'PullRequestEvent':
                $author = self::markdownMention($actor['login'], true);
                $pullreq_number = $payload['number'];
                $pullreq_url = $payload['pull_request']['html_url'];
                $pullreq_title = self::markdownEscape(trim($payload['pull_request']['title']));
                $action = ' ' . $payload['action'] . ' [#' . $payload['number'] . '](' . $pullreq_url . ')';
                $ret .= $author . $action . "\n\n**" . $pullreq_title . '**';
                break;
            case 'IssueCommentEvent':
                $author = self::markdownMention($actor['login'], true);
                $issue_number = $payload['issue']['number'];
                $issue_url = $payload['issue']['html_url'];
                $issue_title = self::markdownEscape(trim($payload['issue']['title']));
                $action = ' commented on [#' . $issue_number . '](' . $issue_url . ')';
                $ret .= $author . $action . "\n\n**" . $issue_title . '**';
                break;
            case 'PullRequestReviewCommentEvent':
                $author = self::markdownMention($actor['login'], true);
                $comment_url = $payload['comment']['html_url'];
                $pullreq_url = $payload['comment']['pull_request_url'];
                $pullreq_number = (int) preg_replace('/.+\/pulls\//', '', $pullreq_url);
                $action = ' commented on [#' . $pullreq_number . '](' . $comment_url . ')';
                $ret .= $author . $action;
                break;
        }
        return Markdown::defaultTransform($ret);
    }

    static function markdownMention($username, $github = false) {
        $url = ($github ? 'http://github.com/' : './') . $username;
        return '[' . $username . '](' . $url . ')';
    }

    /**
     * Adds escape backslashes to characters that are used for markdown syntax,
     * useful for wraping text we don't need to be parsed as markdown.
     *
     * It only takes care of asterisk and underscores chars (used for <em> and <strong>)
     *
     * @todo: review the markdown syntax and complete this regular expression
     */
    static function markdownEscape($text) {
        $pattern = '/(\*|_)/';
        $replacement = '\\\\$1';
        return preg_replace($pattern, $replacement, $text);
    }
}
