<?php

use \Mustache\Mustache;

class Layout Extends MVCObject {
    public $name = '';
    public $stylesheets = array();
    public $scripts = array();
    public $title = 'Worklist';
    public $redir_url = '';
    
    public $metadata = array(
        'title' => "Worklist: High Fidelity's exoskeleton for rapid software development.",
        'image' => 'https://public.highfidelity.com/hifi-worklist-logo.jpg',
        'description' => 'High Fidelity is an open source virtual world platform. We are building the software with a mix of full-time developers, part time developers who are paid here on the worklist, and open source collaborators. As use of the virtual world grows, Worklist will also host paid projects run by other teams.'
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

    public function __construct() {
        parent::__construct();

        $this->name = strtolower(preg_replace('/Layout$/', '', get_class($this)));
        
        $user_id = Session::uid();
        $user = User::find($user_id);
        
        $this->currentUser['id'] = $user_id;
        $this->currentUser['username'] = $user_id ? $user->getUsername() : '';
        $this->currentUser['nickname'] = $user_id ? $user->getNickname() : '';
        $this->currentUser['is_runner'] = empty($_SESSION['is_runner']) ? false : true;
        $this->currentUser['runningProjects'] = json_encode($user->getProjectsAsRunner());
        $this->currentUser['is_payer'] = empty($_SESSION['is_payer']) ? false : true;
        $this->currentUser['is_admin'] = !$user->getIs_admin() ? false : true;
        
        if ($user_id) {
            Utils::initUserById($user_id);
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
            $this->currentUser['is_internal'] = $user->isInternal();
            $this->currentUser['budgetAuthorized'] = (strpos(BUDGET_AUTHORIZED_USERS, "," . $user_id . ",") !== false);
        }
    }

    public function render($view) {
        return if !is_subclass_of($this, 'Layout')
        $name = $this->name;
        $base = VIEWS_DIR . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'mustache';
        $partials = VIEWS_DIR . DIRECTORY_SEPARATOR . 'mustache' . DIRECTORY_SEPARATOR . 'partials';
        $mustache = new Mustache_Engine(array(
            'loader' => new Mustache_Loader_FilesystemLoader($base),
            'partials_loader' => new Mustache_Loader_FilesystemLoader($partials)
        ));
        $template = $mustache->loadTemplate($this->name);
        return $template->render($view);
    }
}
