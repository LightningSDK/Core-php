<?php

namespace Pages\User;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Request;
use Lightning\Tools\Template;

// SET DEFAULT PAGE
$sub_page = Request::get('p');
$action = Request::get_any('action');
$user = ClientUser::getInstance();
$template = Template::getInstance();

// CHECK IF LOGGING OUT
if($sub_page == "logout" || $sub_page == 'logout'){
  $user->logout();
  $logout_url = Configuration::get('logout_url') ?: '/';
	header('Location: ' . $logout_url);
	exit;
}

if($user->details['type'] > 1){
	// USER IS LOGGED IN, REDIRECT TO THE DEFAULT PAGE
  $logout_url = Configuration::get('loged_in_redirect') ?: '/';
  header('Location: ' . $logout_url);
	exit;
}

$page = "user";

if(Request::get('error') == 1){
	$errors[] = "You must be logged in to access premium content.";
}

if($action == 'reset'){
	if(isset($_POST['email'])){
		$user->reset_password($_POST['email']);
		$messages[] = "Your password has been reset. Please check your email for a temporary password.";
	}
}

if($action == 'change_pass'){
	$page = "user_reset";
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

if($_POST['action'] == 'register'){
	if (!empty($_POST['email']) && $_POST['password'] == $_POST['password2']){
		$previous_user = $user->id;
		if($user->create($_POST['email'], $_POST['password'])){
			$user->login($_POST['email'], $_POST['password']);
			if($previous_user != 0)
				$user->merge_users($previous_user);
			if($_POST['redirect'] != "" && !strstr($_POST['redirect'],"user.php"))
		  		header("Location: {$_POST['redirect']}");
		  	else
		  		header("Location: {$user->login_url}");
	  		exit;
	  	} else {
	  		$template->set("error", $user->error);
	  	}
	}

}

// IF THEY ARE LOGGING IN
if($_POST['action'] == 'login'){
	$previous_user = $user->id;
	$login_result = $user->login($_POST['email'], $_POST['password']);
	if ($login_result == -1){
		// BAD PASSWORD COMBO
		Database::getInstance()->query("INSERT INTO ban_log (time, ip, type) VALUE (".time().", '{$_SERVER['REMOTE_ADDR']}', 'L')");
		$template->set("error", "You entered the wrong password. If you are having problems and would like to reset your password, <a href='{$user->reset_url}'>click here</a>");
	}else if ($login_result == -2){
		// ACCOUNT UNCONFIRMED
		$template->set("error", "Your email address has not been confirmed. Please look for the confirmation email and click the link to activate your account.");
	} else {
		if ($previous_user != 0) {
      $user->merge_users($previous_user);
    }
		if($redirect == Request::get('redirect') && !preg_match('|^[/?]user|', $redirect)) {
      header("Location: {$redirect}");
    }
		else {
      header("Location: {$user->default_page}");
    }
		exit;
	}
}


if(isset($_GET['redirect'])) {
  $template->set('redirect',$_GET['redirect']);
}
elseif(isset($_POST['redirect'])) {
  $template->set('redirect',$_POST['redirect']);
}
// else if cookie
// else if referer


$template->set("l_page", $sub_page);

Template::getInstance()->set('content', $page);

include 'include/footer.php';
