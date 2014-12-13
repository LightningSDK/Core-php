<?php

namespace Lightning\API;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\Model\User as UserModel;
use Lightning\View\API;

class User extends API {
    public function postLogin() {
        $email = Request::post('email', 'email');
        $pass = Request::post('password');
        $login_result = UserModel::login($email, $pass);
        $data = array();
        if (!$login_result) {
            // BAD PASSWORD COMBO
            Messenger::error('Invalid password.');
        } else {
            $session = Session::getInstance();
            $session->setState(Session::STATE_APP);
            $data['cookies'] = array('session' => $session->key);
        }
        Output::json($data);
    }

    public function postRegister() {
        $email = Request::post('email', 'email');
        $pass = Request::post('password');
        
        // Validate POST data
        if (!$this->validateData($email, $pass)) {
            // Immediately output all the errors
            Output::jsonError('');
        }
        
        // Register user
        $res = UserModel::register($email, $pass);
        if ($res['success']) {
            Output::json($res['data']);
        } else {
            Output::jsonError('User could not be created');
        }
    }
    
    /**
     * Validates POST data (email, password and confirmation).
     * 
     * @param string $email
     * @param string $pass
     */
    protected function validateData($email, $pass) {
        // Default value
        $result = TRUE;
        
        // Are all fields filled?
        if (is_null($email) OR is_null($pass)) {
            Messenger::error('Please fill out all the fields');
            $result = FALSE;
        }
        
        // Is email correct?
        if ($email === FALSE) {
            Messenger::error('Please enter a correct email');
            $result = FALSE;
        }

        // Are passwords strong enough? Check its length
        if (strlen($pass) < 6) {
            Messenger::error('Passwords must be at least 6 characters');
            $result = FALSE;
        }
    }
}
