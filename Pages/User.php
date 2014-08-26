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
        if (!empty($_POST['email']) && $_POST['password'] == $_POST['password2']){
            $user = ClientUser::getInstance();
            $previous_user = $user->id;
            if($user->create($_POST['email'], $_POST['password'])){
                $user->login($_POST['email'], $_POST['password']);
                if($previous_user != 0)
                    $user->merge_users($previous_user);
                if($_POST['redirect'] != "" && !strstr($_POST['redirect'],"user.php"))
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

    public function getLogout() {
        ClientUser::getInstance()->logout();
        Navigation::redirect('Location: ' . Configuration::get('logout_url') ?: '/');
        exit;
    }

    public function postReset() {
        if($email = Request::get('email', 'email')){
            if (ClientUser::getInstance()->reset_password($email)) {
                Messenger::message('Your password has been reset. Please check your email for a temporary password.');
                return Message::end();
            }
        }
    }

    public function getChangePass() {

    }

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
            $errors[] = "Your password is not secure. Please pick a more secure password.";
            $template->set("change_password", true);
        }
        $template->set("code",$_GET['code'].$_POST['code']);
        $template->set("email",$_GET['email'].$_POST['email']);
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

//// SET DEFAULT PAGE
//$sub_page = Request::get('p');
//$action = Request::get('action');
//$user = ClientUser::getInstance();
//$template = Template::getInstance();
//
//$page = "user";
//
//if(isset($_GET['redirect'])) {
//  $template->set('redirect',$_GET['redirect']);
//}
//elseif(isset($_POST['redirect'])) {
//  $template->set('redirect',$_POST['redirect']);
//}
//// else if cookie
//// else if referer
//
//
//$template->set("l_page", $sub_page);
//
//Template::getInstance()->set('content', $page);
