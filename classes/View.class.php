<?php
/**
 * This class should do the messaging part between logic and views.
 * Common used values as header/footer location or page title should
 * use a public static variable, and view-specific values should be
 * stored through read/write methods
 *
 *
 */
use \Mustache\Mustache;

class View extends AppObject {
    public $name = '';
    public $layout = null;
    public $title = 'Worklist';
    public $app = array(
        'url' => WORKLIST_URL,
        'metadata' => array(
            'title' => "Worklist: High Fidelity's exoskeleton for rapid software development.",
            'image' => 'images/hifi-logo-notext.png',
            'description' => 'High Fidelity is an open source virtual world platform. We are building the software with a mix of full-time developers, part time developers who are paid here on the worklist, and open source collaborators. As use of the virtual world grows, Worklist will also host paid projects run by other teams.'
        ),
        'self_url' => '',
        'feeds_url' => 'feedlist'
    );

    public $currentUser = array(
        'id' => 0,
        'username' => '',
        'nickname' => 'Guest',
        'is_runner' => false,
        'runner_id' => 0,
        'is_admin' => false,
        'is_payer' => false,
        'is_paypal_verified' => false,
        'can' => array(
            'addProject' => false
        ),
        'budget' => array(
            'feeSums' => .0,
            'totalManaged' => .0,
            'remainingFunds' => .0,
            'allocated' => .0,
            'submitted' => .0,
            'paid' => .0,
            'transfered' => .0,
            'transfersDetails' => array()
        ),
        'can' => array(
            'addProject' => false
        )
    );

    public $jumbotron = '';

    public $config = array(
        'ajaxRefresh' => AJAX_REFRESH
    );

    public $stylesheets = array();
    public $scripts = array();

    /**
     * Constructor method
     */
    public function __construct() {
        $this->name = strtolower(preg_replace('/View$/', '', get_class($this)));
    }

    /**
     * Load default views global values/properties
     */
    public function loadGlobals() {
        $user_id = getSessionUserId();
        $user = new User();
        if ($user_id) {
            initUserById($user_id);
            $user->findUserById($user_id);
            $this->currentUser['budget'] = array(
                'feeSums' => Fee::getSums(),
                'totalManaged' => money_format('$ %i', $user->getTotalManaged()),
                'remainingFunds' => money_format('$ %i', $user->setRemainingFunds()),
                'allocated' => money_format('$ %i', $user->getAllocated()),
                'submitted' => money_format('$ %i', $user->getSubmitted()),
                'paid' => money_format('$ %i', $user->getPaid()),
                'transfered' => money_format('$ %i', $user->getTransfered()),
                'transfersDetails' => $user->getBudgetTransfersDetails(),
                'available' => $user->getBudget()
            );
            $this->currentUser['can'] = array(
                'addProject' => ($user->getIs_admin() || $user->isRunner() || $user->isPaypalVerified())
            );
        }

        $this->app['self_url'] = $_SERVER['PHP_SELF'];
        $this->currentUser['id'] = $user_id;
        $this->currentUser['username'] = $user_id ? $user->getUsername() : '';
        $this->currentUser['nickname'] = $user_id ? $user->getNickname() : '';
        $this->currentUser['is_runner'] = empty($_SESSION['is_runner']) ? false : true;
        $this->currentUser['is_payer'] = empty($_SESSION['is_payer']) ? false : true;
        $this->currentUser['is_admin'] = empty($_SESSION['is_admin']) ? false : true;
    }

    /**
     * Add a css stylesheet to be used in the view
     */
    public function addStyle($path) {
        if (array_key_exists($path, self::$stylesheets)) {
            return false;
        }
        $this->stylesheets[] = $path;
        return true;
    }

    /**
     * Removes an existing stylesheet from the view
     */
    public function removeStyle($path) {
        if (!array_key_exists($path, $this->stylesheets)) {
            return false;
        }
        unset($this->stylesheets[$path]);
        return true;
    }

    /**
     * Add a script to be ran in the view side
     */
    private function addScript($path) {
        if (array_key_exists($path, $this->scripts)) {
            return false;
        }
        $this->scripts[] = $path;
        return true;
    }


    /**
     * Outputs stylesheets references to the view/layout
     */
    public function styleTags() {
        $layout = $this->layout;
        if (!is_null($layout)) {
            $stylesheets = array_merge($layout->stylesheets, $this->stylesheets);
        }
        $ret = '';
        foreach ($stylesheets as $path) {
            $ret .= sprintf('<link rel="stylesheet" href="%s">', $path);
        }
        return $ret;
    }

    /**
     * Outputs scripts references to the view/layout
     */
    public function scriptTags() {
        $layout = $this->layout;
        if (!is_null($layout)) {
            $scripts = array_merge($layout->scripts, $this->scripts);
        }
        $ret = '';
        foreach ($scripts as $path) {
            $ret .= sprintf('<script type="text/javascript" src="%s"></script>', $path);
        }
        return $ret;
    }

    /**
     * Removes an existing script from the view
     */
    private function removeScript($path) {
        if (!array_key_exists($path, $this->scripts)) {
            return false;
        }
        unset($this->scripts[$path]);
        return true;
    }

    public function render() {
        $this->loadGlobals();

        $layout = $this->layout;
        if (is_string($layout) && class_exists($layout . 'Layout')) {
            $layoutClass = $layout . 'Layout';
            $layout = $this->layout = new $layoutClass();
        } elseif (is_null($layout) && class_exists('DefaultLayout')) {
            $layout = $this->layout = new DefaultLayout();
        }
        $base = VIEWS_DIR . DIRECTORY_SEPARATOR . 'mustache';
        $partials = VIEWS_DIR . DIRECTORY_SEPARATOR . 'mustache' . DIRECTORY_SEPARATOR . 'partials';
        $mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader($base),
            'partials_loader' => new Mustache_Loader_FilesystemLoader($partials)
        ));
        $template = $mustache->loadTemplate($this->name);
        $content = $this->content = $template->render($this);
        return is_null($layout) ? $content : $layout->render($this);
    }
}
