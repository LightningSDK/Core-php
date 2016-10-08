<?php

namespace Lightning\Pages;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Form;
use Lightning\Tools\Output;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Session;
use Lightning\Tools\SocialDrivers\Facebook;
use Lightning\Tools\SocialDrivers\Google;
use Lightning\Tools\SocialDrivers\SocialMediaApi;
use Lightning\Tools\SocialDrivers\SocialMediaApiInterface;
use Lightning\Tools\Template;
use Lightning\View\Page;
use Lightning\Model\User as UserModel;

class User extends Page {

    protected $page = 'user';
    protected $rightColumn = false;

    protected function hasAccess() {
        return true;
    }

    public function get() {
        Form::requiresToken();
        $user = ClientUser::getInstance();
        Template::getInstance()->set('redirect', Scrub::toURL(Request::get('redirect', Request::TYPE_STRING)));
        if ($user->id > 0) {
            // USER IS LOGGED IN, REDIRECT TO THE DEFAULT PAGE
            $this->loginRedirect();
        }
    }

    public function getModal() {
        Template::getInstance()->setTemplate(['modal', 'Lightning']);
        if (!ClientUser::getInstance()->isAnonymous() && !Request::get('social', Request::TYPE_BOOLEAN)) {
            Messenger::message('You are already signed in.');
            $this->page = '';
        }
    }

    /**
     * Show just the registration page.
     */
    public function getRegister() {
        $template = Template::getInstance();
        $template->set('action', 'register');
        $template->set('redirect', Scrub::toURL(Request::get('redirect', Request::TYPE_STRING)));
    }

    /**
     * This is common user register algorithm.
     * It validates POST data and registers user.
     */
    public function postRegister() {

        $email = Request::post('email', Request::TYPE_EMAIL);
        $pass = Request::post('password');
        $pass2 = Request::post('password2');
        
        // Validate POST data
        if (!$this->validateData($email, $pass, $pass2)) {
            // No need to proceed. Just return, all errors are in Messenger stack
            return;
        }

        // Register user
        $res = UserModel::register($email, $pass2);
        if (!$res['success']) {
            if ($res['error'] == 'exists') {
                Messenger::error('An account with that email already exists. Please try again. if you lost your password, click <a href="/user?action=reset&email=' . urlencode($email) . '">here</a>');
                return $this->getRegister();
            } else {
                Messenger::error('User could not be created');
                return $this->getRegister();
            }
        }

        // See if they are being added to a specific list.
        $default_list = Configuration::get('mailer.default_list', 0);
        $mailing_list = Request::post('list_id', Request::TYPE_INT, null, $default_list);
        if (!empty($mailing_list)) {
            $user = UserModel::loadByEmail($email);
            $user->subscribe($mailing_list);
        }

        $this->loginRedirect();
    }

    /**
     * Validates POST data (email, password and confirmation).
     * 
     * @param string $email
     * @param string $pass
     * @param string $pass2
     * @return boolean Is data correct?
     */
    protected function validateData($email, $pass, $pass2) {
        
        // Default value
        $result = TRUE;
        
        // Are all fields filled?
        if (is_null($email) OR is_null($pass) OR is_null($pass2)) {
            Messenger::error('Please fill out all the fields');
            $result = FALSE;
        }
        
        // Is email correct?
        if ($email === FALSE) {
            Messenger::error('Please enter a valid email');
            $result = FALSE;
        }
        
        // Are passwords strong enough? Check its length
        $min_password_length = Configuration::get('user.min_password_length');
        if (strlen($pass) < $min_password_length OR strlen($pass2) < $min_password_length) {
            Messenger::error("Passwords must be at least {$min_password_length} characters");
            $result = FALSE;
        }

        // Are passwords match?
        if ($pass != $pass2) {
            Messenger::error('Passwords do not match');
            $result = FALSE;
        }
        
        return $result;
    }

    /**
     * Show just the login form, and not the registration form.
     */
    public function getLogin() {
        Template::getInstance()->set('action', 'login');
        return $this->get();
    }

    /**
     * Handle the user attempting to log in.
     */
    public function postLogin() {
        $email = Request::post('email', Request::TYPE_EMAIL);
        $pass = Request::post('password');
        $login_result = UserModel::login($email, $pass);
        if (!$login_result) {
            // BAD PASSWORD COMBO
            Messenger::error('You entered the wrong password. If you are having problems and would like to reset your password, <a href="/user?action=reset">click here</a>');
            Template::getInstance()->set('action', 'login');
            return $this->get();
        } else {
            $this->loginRedirect();
            exit;
        }
    }

    public function postFacebookLogin() {
        if ($token = SocialMediaApi::getRequestToken()) {
            $fb = Facebook::getInstance(true, $token, $token['auth']);
            $this->finishSocialLogin($fb);
        }
        Messenger::error('Login Failed');
        return $this->get();
    }

    public function postGoogleLogin() {
        if ($token = SocialMediaApi::getRequestToken()) {
            $google = Google::getInstance(true, $token['token'], $token['auth']);
            $this->finishSocialLogin($google);
        }
        Messenger::error('Login Failed');
        return $this->get();
    }

    /**
     * @param SocialMediaApiInterface $social_api
     */
    public function finishSocialLogin($social_api) {
        $social_api->setupUser();
        $social_api->activateUser();
        $social_api->afterLogin();

        // Output the new cookie.
        $this->loginRedirect();
    }

    /**
     * Log the user out and redirect them to the exit page.
     */
    public function getLogout() {
        ClientUser::getInstance()->logOut();
        Navigation::redirect(Configuration::get('logout_url') ?: '/');
        exit;
    }

    /**
     * Unsubscribe the user from all mailing lists.
     */
    public function getUnsubscribe() {
        $this->page = 'unsubscribe_confirm';
        Template::getInstance()->set('user_token', Request::get('u', 'encrypted'));
    }

    public function postConfirmUnsubscribe() {
        if ($cyphserstring = Request::get('u', 'encrypted')) {
            $user = UserModel::loadByEncryptedUserReference($cyphserstring);
            if (empty($user)) {
                Output::error('Invalid request');
            }
            $user->unsubscribeAll();
            Messenger::message('Your email ' . $user->email . ' has been removed from all mailing lists.');
        } else {
            Messenger::error('Invalid request');
        }
    }

    /**
     * Confirm the user account via the confirmation link.
     */
    public function getConfirm() {
        if ($cyphserstring = Request::get('u', Request::TYPE_ENCRYPTED)) {
            $user = UserModel::loadByEncryptedUserReference($cyphserstring);
            $user->setConfirmed();
            Messenger::message('Your account ' . $user->email . ' has been confirmed.');
            $this->loginRedirect();
        } else {
            Messenger::error('Invalid request');
        }
    }

    public function getReset() {
        Template::getInstance()->set('action', 'reset');
    }

    /**
     * Send a temporary password.
     *
     * @todo This is not secure. There should be a security question and email should just be a link.
     */
    public function postReset() {
        if (!$email = Request::get('email', Request::TYPE_EMAIL)) {
            Output::error('Invalid email');
        } elseif (!$user = UserModel::loadByEmail($email)) {
            Output::error('User does not exist.');
        } elseif ($user->sendResetLink()) {
            Navigation::redirect('message', ['msg' => 'reset']);
        }
    }

    public function getSetPassword() {
        $key = Request::get('key', Request::TYPE_BASE64);
        if ($user = UserModel::loadByTempKey($key)) {
            Template::getInstance()->set('action', 'set_password');
            Template::getInstance()->set('key', $key);
        } else {
            $this->page = '';
            Messenger::error('Invalid Access Key');
        }
    }

    public function postSetPassword() {
        if ($user = UserModel::loadByTempKey(Request::get('key', Request::TYPE_BASE64))) {
            if (($pass = Request::post('password')) && $pass == Request::post('password2')) {
                $user->setPass($pass);
                $user->registerToSession();
                $user->removeTempKey();
                $this->loginRedirect();
            } else {
                Messenger::error('Please enter a valid password and verify it by entering it again..');
            }
        } else {
            $this->page = '';
            Messenger::error('Invalid Access Key');
        }
    }

    public function loginRedirect($page = null, $params = []) {
        $redirect = Request::post('redirect', Request::TYPE_URL_ENCODED);
        if ($redirect && !preg_match('|^[/?]user|', $redirect)) {
            Navigation::redirect($redirect, $params);
        } elseif (!empty($page)) {
            Navigation::redirect($page, $params);
        } else {
            Navigation::redirect(Configuration::get('user.login_url'), $params);
        }
    }

    public function getStopImpersonating() {
        $session = Session::getInstance();
        if (ClientUser::getInstance()->isImpersonating()) {
            if (!empty($session->content->impersonate)) {
                unset($session->content->impersonate);
                $session->save();
            }
            Navigation::redirect('/');
        }
    }
}
