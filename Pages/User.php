<?php

namespace Lightning\Pages;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\View\Page;
use Lightning\Model\User as UserObj;

class User extends Page {
    public function get() {
        $user = ClientUser::getInstance();
        if($user->id > 0){
            // USER IS LOGGED IN, REDIRECT TO THE DEFAULT PAGE
            $this->loginRedirect();
        }

        Template::getInstance()->set('content', 'user');
    }

    public function postRegister() {
        $email = Request::post('email');
        $pass = Request::post('password');
        $pass2 = Request::post('password2');
        if ($email && $pass == $pass2){
            $user = ClientUser::getInstance();
            $previous_user = $user->id;
            if($user->create($email, $pass)){
                $user->login($email, $pass2);
                if($previous_user != 0)
                    $user->merge_users($previous_user);
                if($_POST['redirect'] != '' && !preg_match('|/?user[/$?]|', $_POST['redirect']))
                    Navigation::redirect($_POST['redirect']);
                else
                    Navigation::redirect($user->login_url);
                exit;
            } else {
                Messenger::error($user->error);
            }
        }
    }

    public function postLogin() {
        $user = ClientUser::getInstance();
        $login_result = $user->login($_POST['email'], $_POST['password']);
        $user = ClientUser::getInstance();
        if ($login_result === -1) {
            // BAD PASSWORD COMBO
            Database::getInstance()->query("INSERT INTO ban_log (time, ip, type) VALUE (".time().", '{$_SERVER['REMOTE_ADDR']}', 'L')");
            Messenger::error("You entered the wrong password. If you are having problems and would like to reset your password, <a href='{$user->reset_url}'>click here</a>");
        } else if ($login_result === -2) {
            // ACCOUNT UNCONFIRMED
            Messenger::error('Your email address has not been confirmed. Please look for the confirmation email and click the link to activate your account.');
        } else {
            $this->loginRedirect();
            exit;
        }
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
        if ($cyphserstring = Request::get('u', 'encrypted')) {
            $user = UserObj::loadByEncryptedUserReference($cyphserstring);
            $user->setActive(0);
            Messenger::message('Your email ' . $user->details['email'] . ' has been removed from all mailing lists.');
        } else {
            Messenger::error('Invalid request');
        }
    }

    /**
     * Send a temporary password.
     *
     * @todo This is not secure. There should be a security question and email should just be a link.
     */
    public function postReset() {
        if (!$email = Request::get('email', 'email')) {
            Messenger::error('Invalid email');
            return;
        }
        elseif (!$user = UserObj::loadByEmail($email)) {
            Messenger::error('User does not exist.');
            return;
        }
        if ($user->sendTempPass()) {
            Messenger::message('Your password has been reset. Please check your email for a temporary password.');
        }
    }

    public function getChangePass() {

    }

    /**
     * @todo this method needs to be updated.
     */
    public function postChangePass() {
        $template = Template::getInstance();
        $user = ClientUser::getInstance();
        $template->set('content', 'user_reset');
        if($_POST['new_pass'] == $_POST['new_pass_conf']){
            if(isset($_POST['new_pass'])){
                if($user->change_temp_pass($_POST['email'], $_POST['new_pass'], $_POST['code']))
                    $template->set("password_changed", true);
            } else {
                $template->set("change_password", true);
            }
        } else {
            Messenger::error('Your password is not secure. Please pick a more secure password.');
            $template->set("change_password", true);
        }
    }

    public function loginRedirect() {
        $redirect = Request::get('redirect');
        if ($redirect && !preg_match('|^[/?]user|', $redirect)) {
            Navigation::redirect($redirect);
        }
        else {
            Navigation::redirect(Configuration::get('user.login_url'));
        }
    }
}
