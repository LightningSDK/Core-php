<?php

namespace Lightning\API;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\Model\User as UserObj;
use Lightning\View\API;

class User extends API {
    public function postLogin() {
        $email = Request::post('email', 'email');
        $pass = Request::post('password');
        $login_result = UserObj::login($email, $pass);
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
        $pass2 = Request::post('password2');
        if ($email && $pass == $pass2){
            $user = ClientUser::getInstance();
            $previous_user = $user->id;
            if($user_id = UserObj::create($email, $pass)){
                UserObj::login($email, $pass2);
                $user = ClientUser::getInstance();

                if($previous_user != 0) {
                    // TODO: This should only happen if the user is a placeholder.
                    $user->merge_users($previous_user);
                }
                Output::json(array('user_id' => ClientUser::getInstance()->id));
            }
            Output::jsonError('User could not be created');
        }
        Output::jsonError('Missing data');
    }
}
