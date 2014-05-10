<?php
/**
 * Copyright 2014 - High Fidelity, Inc.
 */
require_once("config.php");

class Dispatcher {
    public function run() {
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        $path = '/' . $url;
        $dispatcher = new Pux\Mux;

        $dispatcher->get('/addjob', array('AddJob'));
        $dispatcher->get('/confirmation', array('Confirmation'));
        $dispatcher->post('/confirmation', array('Confirmation'));
        $dispatcher->get('/feedlist', array('FeedList'));
        $dispatcher->get('/feeds', array('Feeds'));
        $dispatcher->get('/forgot', array('Forgot'));
        $dispatcher->post('/forgot', array('Forgot'));

        $dispatcher->any('/github(/:method)', array('Github'), array(
            'require' => array('method' => '\w+'),
            'default' => array('method' => 'index')
        ));

        $dispatcher->get('/help', array('Help'));
        $dispatcher->get('/jobs', array('Jobs'));

        $dispatcher->get('/logout', array('Logout'));
        $dispatcher->get('/password', array('Password'));
        $dispatcher->post('/password', array('Password'));
        $dispatcher->get('/payments', array('Payments'));
        $dispatcher->post('/payments', array('Payments'));
        $dispatcher->get('/privacy', array('Privacy'));
        $dispatcher->get('/projectstatus', array('ProjectStatus'));
        $dispatcher->get('/projects', array('Projects'));
        $dispatcher->get('/reports', array('Reports'));
        $dispatcher->post('/reports', array('Reports'));
        $dispatcher->get('/resend', array('Resend'));
        $dispatcher->post('/resend', array('Resend'));
        $dispatcher->get('/resetpass', array('ResetPass'));
        $dispatcher->post('/resetpass', array('ResetPass'));
        $dispatcher->get('/status', array('Status'));
        $dispatcher->post('/status', array('Status', 'api'));
        $dispatcher->get('/settings', array('Settings'));
        $dispatcher->post('/settings', array('Settings'));
        $dispatcher->get('/team', array('Team'));
        $dispatcher->get('/timeline', array('Timeline'));
        $dispatcher->get('/uploads/:filename', array('Upload'), array('require' => array('filename' => '.+')));

        $dispatcher->get('/user(/:method)/:param', array('User'));
        $dispatcher->post('/user/:id', array('User'));

        $dispatcher->get('/welcome', array('Welcome'));
        $dispatcher->get('/:id', array('Job'), array('require' => array('id' => '\d+')));
        $dispatcher->post('/:id', array('Job'), array('require' => array('id' => '\d+')));
        $dispatcher->get('/:project', array('Project'));
        $dispatcher->post('/:project', array('Project'));

        $dispatcher->any('/login', array('Github', 'federated'));
        $dispatcher->any('/signup', array('Github', 'federated'));

        try {
            $route = $dispatcher->dispatch($path);
            $controller = isset($route[2][0]) ? $route[2][0] : DEFAULT_CONTROLLER_NAME;

            if (strlen($controller) < 10 || substr($controller, -10) != 'Controller') {
                $controller .= 'Controller';
            }

            $method = isset($route[2][1]) ? $route[2][1] : DEFAULT_CONTROLLER_METHOD;

            $variables = isset($route[3]['variables']) ? $route[3]['variables'] : array();
            $values = isset($route[3]['vars']) ? $route[3]['vars'] : array();
            $params = array();
            foreach($variables as $variable) {
                if (isset($values[$variable])) {
                    $params[$variable] = $values[$variable];
                }
            }

            $Controller = new $controller();
            call_user_func_array(array($Controller, $method), $params);
        } catch(Exception $e) {

        }
    }
}
