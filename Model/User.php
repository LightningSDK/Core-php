<?php

namespace Lightning\Model;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\View\Field\Time;

class User{

    var $id = 0;
    var $details;
    var $login_url = '/user.php?redirect=';
    var $logout_url = '/';
    var $default_page = '/';
    var $unauthorized_url = '/user.php?p=login&redirect=';
    var $activation_url = "/user.php?p=activate";
    var $first_login_url = "/user.php?p=first_login&redirect="; // THIS WILL BE CALLED AFTER THE FIRST LOGIN
    var $timeout = 10080;//60*24*7; //NUMBER OF MINUTES
    var $confirmation_required = false;
    var $reset_url = "user.php?p=reset";
    var $month = 0;
    var $cookie_domain;
    var $cookie_expire_time;// 30 days
    var $sess_expire_time = 604800;// 7 days
    var $sess_expire_time_mobile = 2592000;// 30 days
    var $sess_reset_time = 86400; // 1 day
    var $track_first_login = false;
    var $require_activation = false;

    function __construct($email = ""){
        $this->cookie_expire_time = time()+2592000;

        global $cookie_domain, $sess_expire_time, $allow_mobile_session;//, $office_id;
        // check if the sess_expire_time has been set
        if($sess_expire_time) $this->sess_expire_time = $sess_expire_time;
        $this->cookie_domain = $cookie_domain;
        if(isset($_GET['ref'])){
            setcookie("ref",$_GET['ref'],$this->cookie_expire_time,"/",$this->cookie_domain);
        }
        if($email != ""){
            if($row = user::find_by_email($email)){
                $this->id = $row['user_id'];
                $this->load_info();
            } else {
                $this->id = 0;
            }
        } else {
            //CHECK THE COOKIE TO SEE IF A USER IS LOGGED IN
            $this->id = 0;
            if(isset($_COOKIE['sess'])){
                // load browser session
                $session = Database::getInstance()->selectRow(
                    'session LEFT JOIN user USING (user_id)',
                    array('session_key' => $_COOKIE['sess'], 'session_ip' => Request::server('ip_int'), 'session_type' => 0)
                );
                if($session['email']!=""){
                    //if the last action has been more than 5 minutes then log them out
                    if($session['last_active'] < (time() - $this->sess_expire_time)){
                        // session expired
                        Database::getInstance()->delete('session', array('session_id' => $session['session_id']));
                        setcookie("sess", '',$this->cookie_expire_time,"/",$this->cookie_domain);
                        $this->login_url = str_replace("?", "?expired=1&", $this->login_url);
                    } else {
                        // set the user
                        $updates = array('last_active' => time());
                        if($session['last_key'] < (time()-$this->sess_reset_time)){
                            $new_sess_key = $this->new_session_key();
                            setcookie("sess", $new_sess_key,$this->cookie_expire_time,"/",$this->cookie_domain);
                            $updates ['last_key'] = time();
                            $updates ['session_key'] = $new_sess_key;
                        }
                        Database::getInstance()->update('session', $updates, array('session_id' => $session['session_id']));
                        $this->id = $session['user_id'];
                    }
                }
                // CHECK IF THIS IS A MOBILE SESSION
            } else if (isset($_REQUEST['sess']) && $allow_mobile_session) {
                $session = Database::getInstance()->selectRow(
                    'session LEFT JOIN user USING (user_id)',
                    array('session_key' => $_REQUEST['sess'], 'session_type' => 1)
                );
                if($session['email']!=""){
                    if($session['last_active'] < (time()-$this->sess_expire_time_mobile)){
                        // session expired
                        Database::getInstance()->delete('session', array('session_id' => $session['session_id']));
                    } else {
                        // set the user
                        if($session['last_key'] < (time()-$this->sess_reset_time)){
                            $key_update = ", last_key=".time().", session_key='".$this->new_session_key()."'";
                        }
                        Database::getInstance()->query("UPDATE session SET last_active=".time().$key_update." WHERE session_id={$session['session_id']}");
                        $this->id = $session['user_id'];
                    }
                }
            }

            if($this->id > 0){
                //LOAD THE USER INFO
                $this->load_info();

                if($this->details['type'] == 0
                    && $this->require_activation
                    && !preg_match("/affiliate_program\.php/",$_SERVER['REQUEST_URI'])
                    && !preg_match("/user\.php/",$_SERVER['REQUEST_URI'])){

                    header("Location: {$this->activation_url}");
                    exit;
                }
                if(isset($this->details['first_login']) && $this->details['first_login'] == 0 && $this->details['type'] == 1 && $this->track_first_login && !preg_match("/user\.php/",$_SERVER['REQUEST_URI'])){
                    header("Location: {$this->first_login_url}".urlencode($_SERVER['REQUEST_URI']));
                    exit;
                }
            }

        }
    }

    public function isAdmin(){
        return $this->details['type'] >= 5;
    }

    function check_pass($pass,$salt='',$hashed_pass=''){
        if($salt == ''){
            $this->load_info();
            $salt = $this->details['salt'];
            $hashed_pass = $this->details['password'];
        }
        if($hashed_pass == $this->pass_hash($pass,pack("H*",$salt)))
            return true;
        else return false;
    }

    function pass_hash($pass,$salt){
        return hash("sha256",$pass.$salt);
    }

    function get_salt(){
        return mcrypt_create_iv(32);
    }

    public static function url_key($user_id = -1){
        if($user_id == -1)
            $user_id = ClientUser::getInstance()->id;
        return $user_id . "." . user::salt_crypt($user_id);
    }

    function update(){
        Database::getInstance()->query("UPDATE user SET last_active = ".time()." WHERE user_id = {$this->id}");
    }

    function load_info($force = false){
        if(!isset($this->details) || $force){
            $this->details = Database::getInstance()->selectRow('user', array('user_id' => $this->id));
        }
    }

    function create($email, $pass){
        if(Database::getInstance()->query("SELECT * FROM user WHERE email = '".strtolower($email)."' AND password != ''")->fetch_assoc()){// AND office_id = {$office_id}
            // ACCOUNT ALREADY EXISTS
            $errors[] = "An account with that email already exists. Please try again. if you lost your password, click <a href='{$this->reset_url}'>here</a>";
            return false;
        }elseif($user_info = Database::getInstance()->query("SELECT * FROM user WHERE email = '".strtolower($email)."' AND password = '' ")->fetch_assoc()){//AND office_id = {$office_id}
            // EMAIL EXISTS IN MAILING LIST ONLY
            if($user_info['confirmed'] != 0)
                $confirmed = ", confirmed = ".rand(100000,9999999);
            if($_COOKIE['ref'] > 0)
                $ref = ", referer = {$_COOKIE['ref']}";
            $this->set_pass($pass, '',$user_info['user_id']);
            $today = GregorianToJD(date("m"), date("d"), date("Y"));
            Database::getInstance()->query("UPDATE user SET join_date = {$today} $confirmed $ref WHERE user_id = {$user_info['user_id']}");
            if($user_info['confirmed'] != 0)
                $this->send_confirmation_email($email);
            return true;
        } else {
            // EMAIL IS NOT IN MAILING LIST AT ALL
            $user_id = $this->insertUser($email, $pass);
            if(intval($_COOKIE['ref']) > 0)
                $ref = ", referer = {$_COOKIE['ref']}";
            Database::getInstance()->query("UPDATE user SET confirmed = ".rand(100000,9999999).", type=1 {$ref} WHERE user_id={$user_id}");//office_id={$office_id},
            $this->send_confirmation_email($email);
            return true;
        }
    }

    /**
     * Make sure that a user's email is listed in the database.
     *
     * @param string $email
     *   The user's email.
     * @param array $options
     *   Additional values to insert.
     * @param array $update
     *   Which values to update the user if the email already exists.
     *
     * @return mixed
     */
    public static function getInsertEmailID($email, $options = array(), $update = array()){
        // TODO: integrate with class_user
        $user_data = array();
        $user_data['email'] = strtolower($email);
        if ($user = Database::getInstance()->selectRow('user', $user_data)){
            if($update) {
                if (!isset($update['list_date'])) {
                    $update['list_date'] = time();
                }
                Database::getInstance()->update('user', $user_data, $update);
            }
            $user_id = $user['user_id'];
        } else {
            $user_data['list_date'] = time();
            $user_id = Database::getInstance()->insert('user', $options + $user_data);
        }
        return $user_id;
    }

    function random_pass(){
        $alphabet = "abcdefghijkmnpqrstuvwxyz";
        $arrangement = "aaaaaaaAAAAnnnnn";
        $pass = "";
        for($i = 0; $i < strlen($arrangement); $i++){
            if($arrangement[$i] == "a")
                $char = $alphabet[rand(0,25)];
            else if($arrangement[$i] == "A")
                $char = strtoupper($alphabet[rand(0,(strlen($alphabet)-1))]);
            else if($arrangement[$i] == "n")
                $char = rand(0,9);
            if(rand(0,1) == 0)
                $pass .= $char;
            else
                $pass = $char.$pass;
        }
        return $pass;
    }

    /**
     * Insert a new user if he doesn't already exist.
     *
     * @param $email
     * @param $pass
     * @param string $first_name
     * @param string $last_name
     * @return mixed
     */
    function insertUser($email, $pass = NULL, $first_name = '', $last_name = ''){
        $user_details = array(
            'email' => Scrub::email(strtolower($email)),
            'user_first' => $first_name,
            'user_last' => $last_name,
            'join_date' => Time::today(),
            'confirmed' => rand(100000,9999999),
            'type' => 0,
            // TODO: Need to get the referer id.
            'ref' => 0,
        );
        if ($pass) {
            $salt = $this->get_salt();
            $user_details['password'] = $this->pass_hash($pass, $salt);
            $user_details['salt'] = bin2hex($salt);
        }
        return Database::getInstance()->insert('user', $user_details);
    }

    function set_pass($pass,$email='',$user_id=0){
        if($email != '')
            $where = "email='".strtolower($email)."'";
        elseif($user_id>0)
            $where = "user_id=".$user_id;
        else
            $where = "user_id=".$this->id;

        $salt = $this->get_salt();
        Database::getInstance()->query("UPDATE user SET password = '".$this->pass_hash($pass,$salt)."', salt='".bin2hex($salt)."' WHERE {$where}");// AND office_id={$office_id}
    }

    function admin_create($email, $office_id, $first_name='', $last_name=''){
        $today = gregoriantojd(date('m'), date('d'), date('Y'));
        $user_info = Database::getInstance()->assoc1("SELECT * FROM user WHERE email = '".strtolower($email)."'");// AND office_id = {$office_id}
        if($user_info['password'] != ''){
            // user exists with password
            // return user_id
            return $user_info['user_id'];
        } else if(isset($user_info['password'])){
            // user exists without password
            // set password, send email
            $random_pass = $this->random_pass();
            $this->set_pass($random_pass, $email, $office_id);
            send_mail($email, '', "New Account", "Your account has been created with a temporary password. Your temporary password is: {$random_pass}\n\nTo reset your password, log in with your temporary password and click 'my profile'. Follow the instructions to reset your new password.");
            Database::getInstance()->query("UPDATE user SET join_date = {$today}, confirmed = ".rand(100000,9999999).", type=1 WHERE user_id = {$user_info['user_id']}");
            return $user_info['user_id'];
        } else {
            // user does not exist
            // create user with random password, send email to activate
            $random_pass = $this->random_pass();
            $user_id = $this->insertUser($email, $random_pass, $first_name, $last_name);
            send_mail($email, '', "New Account", "Your account has been created with a temporary password. Your temporary password is: {$random_pass}\n\nTo reset your password, log in with your temporary password and click 'my profile'. Follow the instructions to reset your new password.");
            Database::getInstance()->query("UPDATE user SET join_date = {$today}, confirmed = ".rand(100000,9999999).", type=1 WHERE user_id = {$user_id}");
            return $user_id;
        }

    }

    public static function add_to_mailing_list($email){
        $today = gregoriantojd(date('m'), date('d'), date('Y'));
        if($user = user::find_by_email($email)){
            $user_id = $user['user_id'];
            Database::getInstance()->query("UPDATE user SET active = 1, list_date = $today WHERE user_id = {$user_id}");
        }else{
            if(intval($_COOKIE['ref']) > 0)
                $ref = ", referer = {$_COOKIE['ref']}";
            $user_id = Database::getInstance()->exec_id("INSERT IGNORE INTO user SET email = '".strtolower($email)."', `list_date` = {$today}, active = 1 $ref");
        }
        return $user_id;
    }

    function reset_password($email){
        $pass = $this->random_pass();
        $salt = $this->get_salt();
        Database::getInstance()->query("UPDATE user SET password = '".$this->pass_hash($pass,$salt)."', salt='".bin2hex($salt)."' WHERE email = '".strtolower($email)."'");
        if(send_mail($email, $name, "Password reset", "Your password has been reset. Your temporary password is: $pass\n\nTo reset your password, log in with your temporary password and click 'my profile' in the top right corner of the web page. Follow the instructions to reset your new password.")){
            $messages[] = 'Your password has been reset. A temporary password has been sent to your email.';
            // this mneed to go somewhere else
//			$template::get->assign("redirect", "/profile.php");
        } else {
            $errors[] = 'There was an error sending the email. Please try again later.';
        }
    }

    function login_app($email, $pass){
        $session_type = 1;
        if($row = user::find_by_email($email)){
            //the user exists
            if(!$this->check_pass($pass,$row['salt'],$row['password'])){
                return Array("error"=>"That is an incorrect email/password combination.");
            }
            if($row['confirmed'] != 0 && $this->confirmation_required){
                //the user is not confirmed
                $this->load_info();
                return Array("error"=>"Confirmation Required.");
            } else{
                //user is confirmed
                $new_sess = $this->new_session_key();

                // delete old sessions -- how?? cron job to remove sessions that have not been renewed in 5 days?
                /* 				$db->query("DELETE FROM session WHERE user_id={$row['user_id']} AND session_type={$session_type}"); */
                // insert new session
                Database::getInstance()->query("INSERT INTO session SET user_id={$row['user_id']}, session_type={$session_type}, session_key='{$new_sess}', last_key=".time().", last_active=".time());
                /* 				$db->query("UPDATE user SET session_id = '$new_sess', session_ip = '$ip', last_active = ".time().", last_login = ".time()." WHERE user_id = {$row['user_id']}"); */
                $this->id = $row['user_id'];
                $this->load_info();
                return Array("session_key"=>$new_sess);
            }
        } else {
            return Array("error"=>"That is an incorrect email/password combination.");
        }
    }

    public static function find_by_email($email){
        return Database::getInstance()->assoc1("SELECT * FROM user WHERE email = '".strtolower($email)."'");
    }

    function login($email, $pass){
        $session_type = 0;
        if($row = user::find_by_email($email)){
            //the user exists
            if(!$this->check_pass($pass,$row['salt'],$row['password'])){
                setcookie("sess", 0,$this->cookie_expire_time,"/",$this->cookie_domain);
                $errors[] = "That is an incorrect email/password combination.";
                return -1;
            }
            if($row['confirmed'] != 0 && $this->confirmation_required){
                //the user is not confirmed
                $this->load_info();
                return -2;
            } else{
                //user is confirmed
                $new_sess = $this->new_session_key();
                setcookie("sess", $new_sess,$this->cookie_expire_time,"/",$this->cookie_domain);

                // delete old sessions
                Database::getInstance()->query("DELETE FROM session WHERE user_id={$row['user_id']} AND session_type={$session_type}");
                // insert new session
                Database::getInstance()->query("INSERT INTO session SET user_id={$row['user_id']}, session_type={$session_type}, session_ip = INET_ATON('{$_SERVER['REMOTE_ADDR']}'), session_key='{$new_sess}', last_key=".time().", last_active=".time());
                /* 				$db->query("UPDATE user SET session_id = '$new_sess', session_ip = '$ip', last_active = ".time().", last_login = ".time()." WHERE user_id = {$row['user_id']}"); */
                $this->id = $row['user_id'];
                $this->load_info();
                return $row['user_id'];
            }
        } else {
            setcookie("sess", 0,$this->cookie_expire_time,"/",$this->cookie_domain);
            $errors[] = "That is an incorrect email/password combination.";
            return -1;
        }
    }

    function new_session_key(){
        return hash('sha256',microtime().rand(0,29384723));
    }

    function logout(){
        if($this->id > 0){
            Database::getInstance()->query("DELETE FROM session WHERE user_id = {$this->id} AND session_type=0");
            $this->details = array();
            $this->id = 0;
        }
    }

    function logout_mobile(){
        Database::getInstance()->delete('session', array('session_key' => Scrub::hex($_REQUEST['sess'])));
    }

    function create_new_key(){
        return hash('sha256',$this->details['email'].microtime());
    }

    function reset_code($email){
        $acct_details = user::find_by_email($email);
        return hash('sha256',($acct_details['email']."*".$acct_details['password']."%".$acct_details['user_id']));
    }

    public static function unsubscribe_key($user_id, $email){
        return hash('sha256',($user_id.$email."8347fgsoidug".hash('sha256',$email)."8437fwehdsu"));
    }

    // REDIRECTS THE USER IF THEY ARE NOT LOGGED IN
    function login_required($auth = 0){
        if($this->id == 0){
            header("Location: ".$this->login_url.urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        if($this->authority < $auth){
            header("Location: ".$this->unauthorized_url.urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    // CREATES A TEMPORARY USER IF THIS USER IS NOT LOGGED IN - USED FOR SESSION TRACKING
    /* NEEDS TO BE UPDATED TO SESSION BASED
        function user_id_required(){
            global $db;
            if($this->id == 0){
                $not_unique = true;
                while($not_unique){
                    $new_sess = hash('sha256',microtime());
                    if(!$db->check("SELECT * FROM user WHERE session_id='{$new_sess}'"))
                        $not_unique = false;
                }
                setcookie("sess", $new_sess,$this->cookie_expire_time,"/",$this->cookie_domain);
                $this->id = $db->exec_id("INSERT INTO user SET session_id = '$new_sess', session_ip = '{$_SERVER['REMOTE_ADDR']}', last_active = ".time().", last_login = ".time());
                $this->load_info();
            }
        }
    */


    function send_confirmation_email($email){
        $acct_details = user::find_by_email($email);
        if($acct_details['confirmed'] == "" || $acct_details['confirmed'] == "confirmed"){
            $acct_details['confirmed'] = hash('sha256',microtime());
            Database::getInstance()->query("UPDATE user SET confirmed = '{$acct_details['confirmed']}' WHERE user_id = {$acct_details['user_id']}");
        }
        global $mail_from_name,$mail_site_name,$email_domain_name,$site_contact_page;
        send_mail($email, $acct_details['user_first']." ".$acct_details['user_last'], $mail_from_name, "You new account has been created. To activate your account, <a href='http://{$email_domain_name}/user.php?confirm={$acct_details['user_id']}.{$acct_details['confirmed']}'>click here</a> or copy and paste this link into your browser:<br /><br />
	http://{$email_domain_name}/user.php?confirm={$acct_details['user_id']}.{$acct_details['confirmed']}
	<br /><br /> If you did not open an account with {$mail_site_name}, please let us know by contacting us at http://{$email_domain_name}/{$site_contact_page}");
    }

    // WHEN A USER LOGS IN TO AN EXISTING ACCOUNT, THIS IS CALLED TO MOVE OVER ANY INFORMATION FROM AN ANONYMOUS SESSION
    // CUSTOMIZE THIS ACCORDING TO THE SITES REQUIREMENTS
    function merge_users($anon_user){
        // FIRST MAKE SURE THIS USER IS ANONYMOUS
        if(Database::getInstance()->check("SELECT * FROM user WHERE user_id = {$anon_user} AND email = ''")){// AND office_id = {$office_id}")){
            // $db->query("UPDATE {$table} SET user_id = {$this->id} WHERE user_id = {$anon_user}");

            // MOVE UPLOADS FROM OLD USER
            // NOT NECESSARY?
            // WONT UPLOAD UNTIL AFTER ACCOUNT IS CREATED?

            // REMOVE OLD USER
            Database::getInstance()->query("DELETE FROM user WHERE user_id = {$anon_user}");
        }

    }



}
