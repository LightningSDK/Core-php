<?php

namespace Lightning\API;

use Exception;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\Model\User as UserModel;
use Lightning\Tools\SocialDrivers\Facebook;
use Lightning\Tools\SocialDrivers\Google;
use Lightning\Tools\SocialDrivers\SocialMediaApi;
use Lightning\Tools\SocialDrivers\SocialMediaApiInterface;
use Lightning\Tools\SocialDrivers\Twitter;
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
            $data['user_id'] = ClientUser::getInstance()->id;
            Output::setJsonCookies(true);
            return $data;
        }
    }

    public function postFacebookLogin() {
        if ($token = SocialMediaApi::getRequestToken()) {
            $fb = Facebook::getInstance(true, $token['token'], $token['auth']);
            $this->finishSocialLogin($fb);
            exit;
        }
        Output::error('Invalid Token');
    }

    public function postGoogleLogin() {
        if ($token = SocialMediaApi::getRequestToken()) {
            Google::setApp(true);
            $google = Google::getInstance(true, $token['token'], $token['auth']);
            $this->finishSocialLogin($google);
            exit;
        }
        Output::error('Invalid Token');
    }

    public function postTwitterLogin() {
        // Do not verify the twitter token against the session,
        // because it's coming in through the API which means
        // an app created it.
        if ($token = Twitter::getAccessToken(false)) {
            $twitter = Twitter::getInstance(true, $token);
            $this->finishSocialLogin($twitter);
            exit;
        }
        Output::error('Invalid Token');
    }

    /**
     * @param SocialMediaApiInterface $social_api
     */
    protected function finishSocialLogin($social_api) {
        $social_api->setupUser();
        $social_api->activateUser();
        $social_api->afterLogin();

        // Output the new cookie.
        $data['cookies'] = array('session' => Session::getInstance()->session_key);
        $data['user_id'] = ClientUser::getInstance()->id;
        Output::json($data);
    }

    public function postRegister() {
        $email = Request::post('email', 'email');
        $pass = Request::post('password');

        if (empty($email)) {
            throw new Exception('Invalid email');
        }
        if (empty($pass)) {
            throw new Exception('Missing pasword');
        }

        $min_password_length = Configuration::get('user.min_password_length');
        if (strlen($pass) < $min_password_length) {
            throw new Exception("Passwords must be at least {$min_password_length} characters");
        }

        $data = [];
        if ($name = Request::post('name')) {
            $data['name'] = $name;
        }
        if ($first = Request::post('first')) {
            $data['first'] = $first;
        }
        if ($last = Request::post('last')) {
            $data['last'] = $last;
        }
        
        // Register user
        $user = UserModel::register($email, $pass, $data);
        return ['user_id' => $user->id];
    }

    public function postReset() {
        if (!$email = Request::get('email', 'email')) {
            Output::error('Invalid email');
        }
        elseif (!$user = UserModel::loadByEmail($email)) {
            Output::error('User does not exist.');
        }
        elseif ($user->sendResetLink()) {
            return Output::SUCCESS;
        }
        Output::error('Could not reset password.');
    }

    public function postLogout() {
        $user = ClientUser::getInstance();
        $user->logOut();
    }
}
