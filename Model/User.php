<?php

namespace Lightning\Model;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Logger;
use Lightning\Tools\Mailer;
use Lightning\Tools\Messenger;
use Lightning\Tools\Security\Encryption;
use Lightning\Tools\Security\Random;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Session;
use Lightning\View\Field\Time;

class User {
    /**
     * A default user with no priviliges.
     */
    const TYPE_UNREGISTERED_USER = 0;

    /**
     * A registered user with a confirmed status.
     */
    const TYPE_REGISTERED_USER = 1;

    /**
     * An admin user with all access.
     */
    const TYPE_ADMIN = 5;

    /**
     * The row from the database.
     *
     * @var array
     */
    protected $details;

    /**
     * Instantiate a user object with it's data row.
     *
     * @param array $details
     *   The user's data row from the database.
     */
    public function __construct($details) {
        $this->details = $details;
    }

    /**
     * A magic getter function.
     *
     * This word for:
     *   ->id
     *   ->details
     *   ->user_id (item inside ->details)
     *
     * @param string $var
     *   The name of the requested variable.
     *
     * @return mixed
     *   The variable value.
     */
    public function __get($var) {
        switch($var) {
            case 'id':
                return $this->details['user_id'];
                break;
            case 'details':
                return $this->details;
                break;
            default:
                if(isset($this->details[$var]))
                    return $this->details[$var];
                else
                    return NULL;
                break;
        }
    }

    /**
     * Load a user by their email.
     *
     * @param $email
     * @return bool|User
     */
    public static function loadByEmail($email) {
        if($details = Database::getInstance()->selectRow('user', array('email' => array('LIKE', $email)))) {
            return new self($details);
        }
        return false;
    }

    /**
     * Load a user by their ID.
     *
     * @param $user_id
     * @return bool|User
     */
    public static function loadById($user_id) {
        if($details = Database::getInstance()->selectRow('user', array('user_id' => $user_id))) {
            return new self($details);
        }
        return false;
    }

    /**
     * Load a user for the provided session.
     *
     * @param $session
     * @return bool|User
     */
    public static function loadBySession($session) {
        if($session->user_id > 0) {
            return self::loadById($session->user_id);
        }
        return false;
    }

    /**
     * Create a new anonymous user.
     *
     * @return User
     */
    public static function anonymous() {
        return new self(array('user_id' => 0));
    }

    /**
     * Check if a user is a site admin.
     *
     * @return boolean
     *   Whther the user is a site admin.
     */
    public function isAdmin() {
        return $this->type == self::TYPE_ADMIN;
    }

    /**
     * Assign a new user type to this user.
     *
     * @param integer $type
     *   The new type.
     */
    public function setType($type) {
        $this->details['type'] = $type;
        Database::getInstance()->update('user', array('type' => $type), array('user_id' => $this->id));
    }

    /**
     * Change the user's active status.
     *
     * @param integer $new_value
     *   The new active status.
     */
    public function setActive($new_value) {
        $this->details['active'] = $new_value;
        Database::getInstance()->update('user', array('active' => $new_value), array('user_id' => $this->id));
    }

    /**
     * Check if the supplied password is correct.
     *
     * @param string $pass
     *   The supplied password.
     * @param string $salt
     *   The salt from the database.
     * @param string $hashed_pass
     *   The hashed password from the database.
     *
     * @return boolean
     *   Whether the correct password was supplied.
     */
    public function checkPass($pass, $salt = '', $hashed_pass = '') {
        if($salt == '') {
            $this->load_info();
            $salt = $this->details['salt'];
            $hashed_pass = $this->details['password'];
        }
        if ($hashed_pass == $this->passHash($pass, pack("H*",$salt))) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Create a password hash from a password and salt.
     *
     * @param string $pass
     *   The password.
     * @param string $salt
     *   The salt.
     *
     * @return string
     *   The hashed password.
     */
    protected static function passHash($pass, $salt) {
        return hash("sha256", $pass . $salt);
    }

    /**
     * Get a new salt string.
     *
     * @return string
     *   A binary string of salt.
     */
    protected static function getSalt() {
        return Random::getInstance()->get(32, Random::BIN);
    }

    public static function urlKey($user_id = -1, $salt = null) {
        if($user_id == -1) {
            $user_id = ClientUser::getInstance()->id;
            $salt = ClientUser::getInstance()->details['salt'];
        } elseif (!$salt) {
            $user = Database::getInstance()->selectRow('user', array('user_id' => $user_id));
            $salt = $user['salt'];
        }
        // TODO: This should be stronger.
        return $user_id . "." . self::passHash($user_id . $salt, $salt);
    }

    /**
     * Update the user's last active time.
     *
     * This should happen on each page load.
     */
    public function update() {
        Database::getInstance()->query("UPDATE user SET last_active = ".time()." WHERE user_id = {$this->id}");
    }

    /**
     * Reload the user's info from the database.
     *
     * @param boolean $force
     *   Whether to force the data to load and overwrite current data.
     */
    public function load_info($force = false) {
        if(!isset($this->details) || $force) {
            $this->details = Database::getInstance()->selectRow('user', array('user_id' => $this->id));
        }
    }

    /**
     * Create a new user.
     *
     * @param string $email
     *   The user's email address.
     * @param string $pass
     *   The new password.
     *
     * @return boolean
     *   Whether the user could be created.
     */
    public static function create($email, $pass) {
        if (Database::getInstance()->check('user', array('email' => strtolower($email), 'password' => array('!=', '')))) {
            // ACCOUNT ALREADY EXISTS
            Messenger::error('An account with that email already exists. Please try again. if you lost your password, click <a href="/user?action=reset&email=' . urlencode($email) . '">here</a>');
            return false;
        } elseif ($user_info = Database::getInstance()->selectRow('user', array('email' => strtolower($email), 'password' => ''))) {
            // EMAIL EXISTS IN MAILING LIST ONLY
            $updates = array();
            if ($user_info['confirmed'] != 0) {
                $updates['confirmed'] = rand(100000,9999999);
            }
            if ($ref = Request::cookie('ref', 'int')) {
                $updates['referrer'] = $ref;
            }
            $user = new self($user_info['user_id']);
            $user->setPass($pass, '', $user_info['user_id']);
            $updates['register_date'] = Time::today();
            Database::getInstance()->update('user', $updates, array('user_id' => $user_info['user_id']));
            if($user_info['confirmed'] != 0) {
                $user->sendConfirmationEmail($email);
            }
        } else {
            // EMAIL IS NOT IN MAILING LIST AT ALL
            $user_id = self::insertUser($email, $pass);
            $updates = array();
            if ($ref = Request::cookie('ref', 'int')) {
                $updates['referrer'] = $ref;
            }
            $updates['confirmed'] = rand(100000,9999999);
            $updates['type'] = 1;
            Database::getInstance()->update('user', $updates, array('user_id' => $user_id));
            $user = new self($user_id);
            $user->sendConfirmationEmail($email);
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
    public static function getInsertEmailID($email, $options = array(), $update = array()) {
        // TODO: integrate with class_user
        $user_data = array();
        $user_data['email'] = strtolower($email);
        if ($user = Database::getInstance()->selectRow('user', $user_data)) {
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

    /**
     * Create a new random password.
     *
     * @return string
     *   A random password.
     */
    public function randomPass() {
        $alphabet = "abcdefghijkmnpqrstuvwxyz";
        $arrangement = "aaaaaaaAAAAnnnnn";
        $pass = "";
        for($i = 0; $i < strlen($arrangement); $i++) {
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
     * @param string $email
     *   The new email
     * @param string $pass
     *   The new password
     * @param string $first_name
     *   The first name
     * @param string $last_name
     *   The last name.
     *
     * @return integer
     *   The new user's ID.
     */
    public static function insertUser($email, $pass = NULL, $first_name = '', $last_name = '') {
        $user_details = array(
            'email' => Scrub::email(strtolower($email)),
            'first' => $first_name,
            'last' => $last_name,
            'register_date' => Time::today(),
            'confirmed' => rand(100000,9999999),
            'type' => 0,
            // TODO: Need to get the referrer id.
            'referrer' => 0,
        );
        if ($pass) {
            $salt = self::getSalt();
            $user_details['password'] = self::passHash($pass, $salt);
            $user_details['salt'] = bin2hex($salt);
        }
        return Database::getInstance()->insert('user', $user_details);
    }

    public function setPass($pass,$email='',$user_id=0) {
        if($email != '')
            $where = "email='".strtolower($email)."'";
        elseif($user_id>0)
            $where = "user_id=".$user_id;
        else
            $where = "user_id=".$this->id;

        $salt = $this->getSalt();
        Database::getInstance()->query("UPDATE user SET password = '".$this->passHash($pass,$salt)."', salt='".bin2hex($salt)."' WHERE {$where}");
    }

    public function admin_create($email, $first_name='', $last_name='') {
        $today = gregoriantojd(date('m'), date('d'), date('Y'));
        $user_info = Database::getInstance()->selectRow('user', array('email' => strtolower($email)));
        if($user_info['password'] != '') {
            // user exists with password
            // return user_id
            return $user_info['user_id'];
        } else if(isset($user_info['password'])) {
            // user exists without password
            // set password, send email
            $randomPass = $this->randomPass();
            $this->setPass($randomPass, $email);
            send_mail($email, '', "New Account", "Your account has been created with a temporary password. Your temporary password is: {$randomPass}\n\nTo reset your password, log in with your temporary password and click 'my profile'. Follow the instructions to reset your new password.");
            Database::getInstance()->query("UPDATE user SET register_date = {$today}, confirmed = ".rand(100000,9999999).", type=1 WHERE user_id = {$user_info['user_id']}");
            return $user_info['user_id'];
        } else {
            // user does not exist
            // create user with random password, send email to activate
            $randomPass = $this->randomPass();
            $user_id = $this->insertUser($email, $randomPass, $first_name, $last_name);
            send_mail($email, '', "New Account", "Your account has been created with a temporary password. Your temporary password is: {$randomPass}\n\nTo reset your password, log in with your temporary password and click 'my profile'. Follow the instructions to reset your new password.");
            Database::getInstance()->query("UPDATE user SET register_date = {$today}, confirmed = ".rand(100000,9999999).", type=1 WHERE user_id = {$user_id}");
            return $user_id;
        }

    }

    public static function add_to_mailing_list($email) {
        // These will be set on either insert or update.
        $user_values = array(
            'list_date' => Time::today(),
            'active' => 1,
        );
        $user_id = Database::getInstance()->insert(
            'user',
            array_merge($user_values,
                array(
                    'email' => strtolower($email),
                    // Ref should only be added for new users.
                    'ref' => Request::cookie('ref'),
                )
            ),
            $user_values
        );
        return $user_id;
    }

    public function fullName() {
        return $this->details['first'] . ' ' . $this->details['last'];
    }

    /**
     * Send a new random password via email.
     */
    public function sendTempPass() {
        $pass = $this->randomPass();
        $salt = $this->getSalt();
        Database::getInstance()->update(
            'user',
            array(
                'password' => $this->passHash($pass,$salt),
                'sald' => bin2hex($salt),
            ),
            array(
                'email' => strtolower($this->details['email']),
            )
        );
        $mailer = new Mailer();
        $mailer->to($this->details['email'], $this->fullName())
            ->subject('Password reset')
            ->message("Your password has been reset. Your temporary password is: $pass\n\nTo reset your password, log in with your temporary password and click 'my profile' in the top right corner of the web page. Follow the instructions to reset your new password.");
        return $mailer->send();
    }

    public static function find_by_email($email) {
        return Database::getInstance()->selectRow('user', array('email' => strtolower($email)));
    }

    /**
     * Makes sure there is a session, and checks the user password.
     * If everything checks out, the global user is created.
     *
     * @param $email
     * @param $password
     * @param bool $remember
     *   If true, the cookie will be permanent, but the password and pin state will still be on a timeout.
     * @param boolean $auth_only
     *   If true, the user will be authenticated but will not have the password state set.
     *
     * @return bool
     */
    public static function login($email, $password, $remember = FALSE, $auth_only = FALSE) {
        // If $auth_only is set, it has to be remembered.
        if ($auth_only) {
            $remember = TRUE;
        }

        $user = ClientUser::getInstance();

        // If a user is already logged in, cancel that user.
        if($user->id > 0) {
            $user->destroy();
        }

        if($temp_user = self::loadByEmail($email)) {
            // user found
            if($temp_user->checkPass($password)) {
                // We need to create a new session if:
                //  There is no session
                //  The session is blank
                //  The session user is not set to this user
                $session = Session::getInstance();
                if((!is_object($session)) || ($session->id == 0) || ($session->user_id != $temp_user->id)) {
                    if(is_object($session)) {
                        // If there is some other session here, we can destroy it.
                        $session->destroy();
                    }
                    $session = Session::create($temp_user->id, $remember);
                }
                if (!$auth_only) {
                    $session->setState(Session::STATE_PASSWORD);
                }
                ClientUser::setInstance($temp_user);
                return true;
            } else {
                Logger::logIP('Bad Password', Logger::SEVERITY_HIGH);
            }
        } else {
            Logger::logIP('Bad Username', Logger::SEVERITY_MED);
        }
        $errors[] = "Invalid Login"; // Could not log in.
        return false;
    }

    /**
     * Destroy a user object and end the session.
     */
    public function destroy() {
        global $session;
        if($this->id > 0) {
            $this->details = NULL;
            $this->id = 0;
            if(is_object($session))
                $session->destroy();
        }
    }

    public function reset_code($email) {
        $acct_details = user::find_by_email($email);
        return hash('sha256',($acct_details['email']."*".$acct_details['password']."%".$acct_details['user_id']));
    }

    /**
     * Get a link to unsubscribe this user.
     *
     * @return string
     *   The absolute web url.
     */
    public function getUnsubscribeLink() {
        return Configuration::get('web_root')
            . '/user?action=unsubscribe&u=' . $this->getEncryptedUserReference();
    }

    /**
     * Get this users encrypted email.
     *
     * @return string
     *   The encrypted email reference.
     */
    public function getEncryptedUserReference() {
        return Encryption::aesEncrypt($this->details['email'], Configuration::get('user.key'));
    }

    /**
     * Load a user by an encrypted reference.
     *
     * @param string $cypher_string
     *   The encrypted email address.
     *
     * @return bool|User
     *   The user if loading was successful.
     */
    public static function loadByEncryptedUserReference($cypher_string) {
        $email = Encryption::aesDecrypt($cypher_string, Configuration::get('user.key'));
        return self::loadByEmail($email);
    }

    // REDIRECTS THE USER IF THEY ARE NOT LOGGED IN
    public function login_required($auth = 0) {
        if($this->id == 0) {
            header("Location: ".$this->login_url.urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        if($this->authority < $auth) {
            header("Location: ".$this->unauthorized_url.urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public function sendConfirmationEmail($email) {
        $acct_details = user::find_by_email($email);
        if($acct_details['confirmed'] == "" || $acct_details['confirmed'] == "confirmed") {
            $acct_details['confirmed'] = hash('sha256',microtime());
            Database::getInstance()->query("UPDATE user SET confirmed = '{$acct_details['confirmed']}' WHERE user_id = {$acct_details['user_id']}");
        }
        global $mail_site_name,$email_domain_name,$site_contact_page;
        $mailer = new Mailer();
        $mailer->to($email, $acct_details['first']." ".$acct_details['last'])
            ->subject('Activate your account')
            ->message("You new account has been created. To activate your account, <a href='http://{$email_domain_name}/user.php?confirm={$acct_details['user_id']}.{$acct_details['confirmed']}'>click here</a> or copy and paste this link into your browser:<br /><br />
	http://{$email_domain_name}/user.php?confirm={$acct_details['user_id']}.{$acct_details['confirmed']}
	<br /><br /> If you did not open an account with {$mail_site_name}, please let us know by contacting us at http://{$email_domain_name}/{$site_contact_page}")
            ->send();
    }

    // WHEN A USER LOGS IN TO AN EXISTING ACCOUNT, THIS IS CALLED TO MOVE OVER ANY INFORMATION FROM AN ANONYMOUS SESSION
    // CUSTOMIZE THIS ACCORDING TO THE SITES REQUIREMENTS
    public function merge_users($anon_user) {
        // FIRST MAKE SURE THIS USER IS ANONYMOUS
        if(Database::getInstance()->check("SELECT * FROM user WHERE user_id = {$anon_user} AND email = ''")) {// AND office_id = {$office_id}")) {
            // $db->query("UPDATE {$table} SET user_id = {$this->id} WHERE user_id = {$anon_user}");

            // MOVE UPLOADS FROM OLD USER
            // NOT NECESSARY?
            // WONT UPLOAD UNTIL AFTER ACCOUNT IS CREATED?

            // REMOVE OLD USER
            Database::getInstance()->query("DELETE FROM user WHERE user_id = {$anon_user}");
        }

    }



}
