<?php

namespace Lightning\API;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Messenger;
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
            $data['cookies'] = array('session' => $session->session_key);
        }
        Output::json($data);
    }

    public function postRegister() {
        $email = Request::post('email', 'email');
        $pass = Request::post('password');
        
        // Validate POST data
        if (!$this->validateData($email, $pass)) {
            // Immediately output all the errors
            Output::error("Invalid Data");
        }
        
        // Register user
        $res = UserModel::register($email, $pass);
        if ($res['success']) {
            Output::json($res['data']);
        } else {
            Output::error($res['error']);
        }
    }

    public function postReset() {
        if (!$email = Request::get('email', 'email')) {
            Output::error('Invalid email');
        }
        elseif (!$user = UserModel::loadByEmail($email)) {
            Output::error('User does not exist.');
        }
        $user->sendResetLink();
    }

    public function postLogout() {
        $user = ClientUser::getInstance();
        $user->logOut();
    }
    
    /**
     * Validates POST data (email, password and confirmation).
     * 
     * @param string $email
     * @param string $pass
     *
     * @return boolean
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
        $min_password_length = Configuration::get('user.min_password_length');
        if (strlen($pass) < $min_password_length) {
            Messenger::error("Passwords must be at least {$min_password_length} characters");
            $result = FALSE;
        }

        return $result;
    }
}
