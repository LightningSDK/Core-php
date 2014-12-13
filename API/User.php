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
        if ($email && $pass) {

            $res = UserModel::register($email, $pass);
            if ($res['success']) {
                Output::json($res['data']);
            } else {
                Output::jsonError('User could not be created');
            }
        }
        Output::jsonError('Missing data');
    }
}
