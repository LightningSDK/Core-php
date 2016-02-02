<?php

namespace Overridable\Lightning\Model;

use Lightning\Model\Object;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Logger;
use Lightning\Tools\Mailer;
use Lightning\Tools\Navigation;
use Lightning\Tools\Security\Encryption;
use Lightning\Tools\Security\Random;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Session;
use Lightning\Tools\SocialDrivers\SocialMediaApi;
use Lightning\Tools\Tracker;
use Lightning\View\Field\Time;
use Lightning\Model\Permissions;

class User extends Object {

    /**
     * A registered user who has not been confirmed.
     */
    const UNCONFIRMED = 0;

    /**
     * A registered user with a confirmed status.
     */
    const CONFIRMED = 1;

    /**
     * An admin user with all access.
     */
    const TYPE_ADMIN = 5;

    /**
     * How long a temporary reset key is available.
     */
    const TEMP_KEY_TTL = 86400;

    const PRIMARY_KEY = 'user_id';
    const TABLE = 'user';

    /**
     * Load a user by their email.
     *
     * @param $email
     * @return User|boolean
     */
    public static function loadByEmail($email) {
        if ($details = Database::getInstance()->selectRow('user', array('email' => array('LIKE', $email)))) {
            return new static($details);
        }
        return false;
    }

    /**
     * Load a user by their ID.
     *
     * @param $user_id
     * @return User|boolean
     */
    public static function loadById($user_id) {
        if ($details = Database::getInstance()->selectRow('user', array('user_id' => $user_id))) {
            return new static($details);
        }
        return false;
    }

    /**
     * Load a user by their temporary access key, from a password reset link.
     *
     * @param string $key
     *   A temporary access key.
     * @return User|boolean
     */
    public static function loadByTempKey($key) {
        if ($details = Database::getInstance()->selectRow(
            array(
                'from' => 'user_temp_key',
                'join' => array(
                    'LEFT JOIN',
                    'user',
                    'using (`user_id`)',
                )
            ),
            array(
                'temp_key' => $key,
                // The key is only good for 24 hours.
                'time' => array('>=', time() - static::TEMP_KEY_TTL),
            )
        )) {
            return new static ($details);
        }
        return false;
    }

    public function update($values) {
        $this->data = $values + $this->data;
        Database::getInstance()->update('user', $values, array('user_id' => $this->id));
    }

    /**
     * Create a new anonymous user.
     *
     * @return User
     */
    public static function anonymous() {
        return new static(array('user_id' => 0));
    }

    /**
     * Check if the current user is being impersonated by an admin.
     *
     * @return boolean
     *   Whether the current user is impersonated.
     */
    public function isImpersonating() {
        return (boolean) Session::getInstance()->getSetting('impersonate');
    }

    /**
     * If the current user is impersonating another user, this will return the
     * impersonating admin user's id.
     */
    public function impersonatingParentUser() {
        if ($this->isImpersonating()) {
            return Session::getInstance()->user_id;
        }
        return null;
    }

    /**
     * Check if a user is a site admin.
     *
     * @return boolean
     *   Whether the user is a site admin.
     */
    public function isAdmin() {
        return $this->hasPermission(Permissions::ALL);
    }

    /**
     * Check if a user is anonymous.
     *
     * @return boolean
     *   Whether the user is anonymous.
     */
    public function isAnonymous() {
        return empty($this->id);
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
        if ($salt == '') {
            $this->load_info();
            $salt = $this->salt;
            $hashed_pass = $this->password;
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
    public static function passHash($pass, $salt) {
        return hash("sha256", $pass . $salt);
    }

    /**
     * Get a new salt string.
     *
     * @return string
     *   A binary string of salt.
     */
    public static function getSalt() {
        return Random::getInstance()->get(32, Random::BIN);
    }

    public static function urlKey($user_id = -1, $salt = null) {
        if ($user_id == -1) {
            $user_id = ClientUser::getInstance()->id;
            $salt = ClientUser::getInstance()->salt;
        } elseif (!$salt) {
            $user = Database::getInstance()->selectRow('user', array('user_id' => $user_id));
            $salt = $user['salt'];
        }
        // TODO: This should be stronger.
        return $user_id . "." . static::passHash($user_id . $salt, $salt);
    }

    /**
     * Update the user's last active time.
     *
     * This should happen on each page load.
     */
    public function ping() {
        Database::getInstance()->update('user', array('last_active' => time()), array('user_id' => $this->id));
    }

    /**
     * Reload the user's info from the database.
     *
     * @param boolean $force
     *   Whether to force the data to load and overwrite current data.
     */
    public function load_info($force = false) {
        if (!isset($this->data) || $force) {
            $this->data = Database::getInstance()->selectRow('user', array('user_id' => $this->id));
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
     * @return Array
     *   When creation is successful:
     *      [Status of creation, user id]
     *   When not:
     *      [Status of creation, Error short code]
     */
    public static function create($email, $pass) {
        if (Database::getInstance()->check('user', array('email' => strtolower($email), 'password' => array('!=', '')))) {
            // An account already exists with that email.
            return [
                'success'   => false,
                'error'     => 'A user with that email already exists.'
            ];
        } elseif ($user_info = Database::getInstance()->selectRow('user', array('email' => strtolower($email), 'password' => ''))) {
            // EMAIL EXISTS IN MAILING LIST ONLY
            $updates = array();
            // Set the referrer.
            if ($ref = Request::cookie('ref', 'int')) {
                $updates['referrer'] = $ref;
            }
            $user = new self($user_info);
            $user->setPass($pass, '', $user_info['user_id']);
            $updates['registered'] = Time::today();
            Database::getInstance()->update('user', $updates, ['user_id' => $user_info['user_id']]);
            $user->sendConfirmationEmail();
            return [
                'success'   => true,
                'data'      => $user_info['user_id']
            ];
        } else {
            // EMAIL IS NOT IN MAILING LIST AT ALL
            $user_id = static::insertUser($email, $pass);
            $updates = array();
            if ($ref = Request::cookie('ref', 'int')) {
                $updates['referrer'] = $ref;
            }
            if (!empty($updates)) {
                Database::getInstance()->update('user', $updates, array('user_id' => $user_id));
            }
            $user = static::loadById($user_id);
            $user->sendConfirmationEmail();
            return [
                'success'   => true,
                'data'      => $user_id
            ];
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
     * @return User
     */
    public static function addUser($email, $options = array(), $update = array()) {
        $user_data = array();
        $user_data['email'] = strtolower($email);
        $db = Database::getInstance();
        if ($user = $db->selectRow('user', $user_data)) {
            if ($update) {
                $db->update('user', $user_data, $update);
            }
            $user_id = $user['user_id'];
            return static::loadById($user_id);
        } else {
            $user_id = $db->insert('user', $options + $user_data + ['created' => Time::today()]);
            $user = static::loadById($user_id);
            $user->new = true;
            return $user;
        }
    }

    /**
     * Add the user to the mailing list.
     *
     * @param $list_id
     *   The ID of the mailing list.
     *
     * @return boolean
     *   Whether they were actually inserted.
     */
    public function subscribe($list_id) {
        if (Database::getInstance()->insert(
            'message_list_user',
            array(
                'message_list_id' => $list_id,
                'user_id' => $this->id,
                'time' => time(),
            ),
            true
        )) {
            // If a result was returned, they were added to the list.
            Tracker::trackEvent('Subscribe', $list_id, $this->id);
            return true;
        } else {
            // They were already in the list.
            return false;
        }
    }

    /**
     * Remove this user from all mailing lists.
     */
    public function unsubscribeAll() {
        Database::getInstance()->delete('message_list_user', array('user_id' => $this->id));
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
            if ($arrangement[$i] == "a")
                $char = $alphabet[rand(0,25)];
            else if ($arrangement[$i] == "A")
                $char = strtoupper($alphabet[rand(0,(strlen($alphabet)-1))]);
            else if ($arrangement[$i] == "n")
                $char = rand(0,9);
            if (rand(0,1) == 0)
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
    protected static function insertUser($email, $pass = NULL, $first_name = '', $last_name = '') {
        $user_details = array(
            'email' => Scrub::email(strtolower($email)),
            'first' => $first_name,
            'last' => $last_name,
            'created' => Time::today(),
            'confirmed' => static::requiresConfirmation() ? static::UNCONFIRMED : static::CONFIRMED,
            // TODO: Need to get the referrer id.
            'referrer' => 0,
        );
        if ($pass) {
            $salt = static::getSalt();
            $user_details['password'] = static::passHash($pass, $salt);
            $user_details['salt'] = bin2hex($salt);
            $user_details['registered'] = Time::today();
        }
        return Database::getInstance()->insert('user', $user_details);
    }

    /**
     * Update a user's password.
     *
     * @param string $pass
     *   The new password.
     * @param string $email
     *   Their email if updating by email.
     * @param integer $user_id
     *   The user_id if updating by user_id.
     *
     * @return boolean
     *   Whether the password was updated.
     */
    public function setPass($pass, $email='', $user_id = 0) {
        if ($email != '') {
            $where['email'] = strtolower($email);
        } elseif ($user_id>0) {
            $where['user_id'] = $user_id;
        } else {
            $where['user_id'] = $this->id;
        }

        $salt = $this->getSalt();
        return (boolean) Database::getInstance()->update(
            'user',
            array(
                'password' => $this->passHash($pass,$salt),
                'salt' => bin2hex($salt),
            ),
            $where
        );
    }

    /**
     * Has to be redone. Not currently in use.
     */
    public function admin_create($email, $first_name='', $last_name='') {
        $today = gregoriantojd(date('m'), date('d'), date('Y'));
        $user_info = Database::getInstance()->selectRow('user', array('email' => strtolower($email)));
        if ($user_info['password'] != '') {
            // user exists with password
            // return user_id
            return $user_info['user_id'];
        } else if (isset($user_info['password'])) {
            // user exists without password
            // set password, send email
            $randomPass = $this->randomPass();
            $this->setPass($randomPass, $email);
            $mailer = new Mailer();
            $mailer
                ->to($email)
                ->subject('New Account')
                ->message("Your account has been created with a temporary password. Your temporary password is: {$randomPass}\n\nTo reset your password, log in with your temporary password and click 'my profile'. Follow the instructions to reset your new password.")
                ->send();
            Database::getInstance()->update(
                'user',
                array(
                    'registered' => $today,
                    'confirmed' => static::requiresConfirmation() ? static::UNCONFIRMED : static::CONFIRMED,
                ),
                array(
                    'user_id' => $user_info['user_id'],
                )
            );
            return $user_info['user_id'];
        } else {
            // user does not exist
            // create user with random password, send email to activate
            $randomPass = $this->randomPass();
            $user_id = $this->insertUser($email, $randomPass, $first_name, $last_name);
            $mailer = new Mailer();
            $mailer
                ->to($email)
                ->subject('New Account')
                ->message("Your account has been created with a temporary password. Your temporary password is: {$randomPass}\n\nTo reset your password, log in with your temporary password and click 'my profile'. Follow the instructions to reset your new password.")
                ->send();
            Database::getInstance()->update(
                'user',
                array(
                    'registered' => $today,
                    'confirmed' => static::requiresConfirmation() ? static::UNCONFIRMED : static::CONFIRMED,
                ),
                array(
                    'user_id' => $user_id,
                )
            );
            return $user_id;
        }

    }

    public function fullName() {
        return $this->first . ' ' . $this->last;
    }

    /**
     * Send a new random password via email.
     */
    public function sendResetLink() {
        // Create a temporary key.
        $reset_key = base64_encode($this->getSalt());
        Database::getInstance()->insert(
            'user_temp_key',
            array(
                'user_id' => $this->id,
                'temp_key' => $reset_key,
                'time' => time(),
            ),
            array(
                'temp_key' => $reset_key,
                'time' => time(),
            )
        );

        // Send a message.
        $mailer = new Mailer();
        $mailer->to($this->email, $this->fullName())
            ->subject('Password reset')
            ->message('A request was made to reset your password. If you did not make this request, please <a href="' . Configuration::get('web_root') . '/contact' . '">notify us</a>. To reset your password, <a href="' . Configuration::get('web_root') . '/user?action=set-password&key=' . $reset_key . '">click here</a>.');
        return $mailer->send();
    }

    /**
     * Delete the temoporary password reset key.
     */
    public function removeTempKey() {
        Database::getInstance()->delete(
            'user_temp_key',
            array(
                'user_id' => $this->id,
            )
        );
    }

    public static function removeExpiredTempKeys() {
        return Database::getInstance()->delete(
            'user_temp_key',
            array(
                'time' => array('<', time() - static::TEMP_KEY_TTL)
            )
        );
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
        if ($user->id > 0) {
            $user->destroy();
        }

        if ($temp_user = static::loadByEmail($email)) {
            // user found
            if ($temp_user->checkPass($password)) {
                $temp_user->registerToSession($remember, $auth_only ?: Session::STATE_PASSWORD);
                return true;
            } else {
                Logger::security('Bad Password', Logger::SEVERITY_HIGH);
            }
        } else {
            Logger::security('Bad Username', Logger::SEVERITY_MED);
        }
        // Could not log in.
        return false;
    }

    public function destroy() {
        // TODO: Remove the current user's session.
        $this->data = array();
        Session::reset();
    }

    public function registerToSession($remember = false, $state = Session::STATE_PASSWORD) {
        // We need to create a new session if:
        //  There is no session
        //  The session is blank
        //  The session user is not set to this user
        $session = Session::getInstance(false);
        if ((!is_object($session)) || ($session->id == 0) || ($session->user_id != $this->id && $session->user_id != 0)) {
            if (is_object($session)) {
                // If there is some other session here, we can destroy it.
                $session->destroy();
            }
            $session = Session::create($this->id, $remember);
            Session::setInstance($session);
        }
        if ($session->user_id == 0) {
            $session->setUser($this->id);
        }
        if ($state) {
            $session->setState($state);
        }
        ClientUser::setInstance($this);
    }

    /**
     * Destroy a user object and end the session.
     */
    public function logOut() {
        $session = Session::getInstance();
        if ($this->id > 0 && is_object($session)) {
            $session::destroyInstance();
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
        return Encryption::aesEncrypt($this->email, Configuration::get('user.key'));
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
        return static::loadByEmail($email);
    }

    /**
     * Redirects the user if they are not logged in.
     *
     * @param int $auth
     *   A required authority level if they are logged in.
     */
    public function login_required($auth = 0) {
        if ($this->id == 0) {
            Navigation::redirect($this->login_url . urlencode($_SERVER['REQUEST_URI']));
        }
        if ($this->authority < $auth) {
            Navigation::redirect($this->unauthorized_url . urlencode($_SERVER['REQUEST_URI']));
        }
    }

    /**
     * Check if a user has been confirmed.
     *
     * @return boolean
     *   Whether the user is confirmed.
     */
    public function isConfirmed() {
        return $this->confirmed == static::CONFIRMED || !static::requiresConfirmation();
    }

    /**
     * Check if a user confirmation is required either for opt-ins or logins.
     *
     * @return boolean
     *   Whether the user requires confirmation in general.
     */
    public static function requiresConfirmation() {
        return Configuration::get('mailer.confirm_message') || Configuration::get('user.requires_confirmation');
    }

    /**
     * Send a confirmation email for the user to validate their email address.
     */
    public function sendConfirmationEmail() {
        if (static::requiresConfirmation() && $confirmation_message = Configuration::get('mailer.confirm_message')) {
            $mailer = new Mailer();
            $url = Configuration::get('web_root') . '/user?action=confirm&u=' . $this->getEncryptedUserReference();
            $mailer->setCustomVariable('SUBSCRIPTION_CONFIRMATION_LINK', $url);
            $mailer->sendOne($confirmation_message, $this);
        }
    }

    public function setConfirmed() {
        $this->confirmed = static::CONFIRMED;
        $this->save();
    }

    /**
     * When a user logs in to an existing account from a temporary anonymous session, this
     * moves the data over to the user's account.
     *
     * @param $anon_user
     */
    public function merge_users($anon_user) {
        // FIRST MAKE SURE THIS USER IS ANONYMOUS
        if (Database::getInstance()->check('user', array('user_id' => $anon_user, 'email' => ''))) {
            // TODO: Basic information should be moved here, but this function should be overriden.
            Database::getInstance()->delete('user', array('user_id' => $anon_user));
        }
    }

    /**
     * Registers user
     *
     * @param string $email email
     * @param string $pass password
     * @return Array
     *   When successful:
     *      [Status, new user id]
     *   When not:
     *      [Status, error short code]
     *
     * @todo This should return the user object, with other data contained inside.
     */
    public static function register($email, $pass) {
        // Save current user for further anonymous check
        $user = ClientUser::getInstance();
        $previous_user = $user->id;

        // Try to create a user or abort with error message
        $res = self::create($email, $pass);
        if ($res['success']) {

            self::login($email, $pass);
            $user = ClientUser::getInstance();
            $user->subscribe(Configuration::get('mailer.default_list'));

            // Merge with a previous anon user if necessary.
            if ($previous_user != 0) {
                // TODO: This should only happen if the user is a placeholder.
                $user->merge_users($previous_user);
            }

            // Success
            return [
                'success'   => true,
                'data'      => ['user_id' => ClientUser::getInstance()->id]
            ];
        } else {
            // Error
            return [
                'success'   => false,
                'error'     => $res['error']
            ];
        }
    }

    public function addRole($role_id) {
        Database::getInstance()->insert('user_role', ['user_id' => $this->id, 'role_id' => $role_id], true);
    }

    public function removeRole($role_id) {
        Database::getInstance()->delete('user_role', ['user_id' => $this->id, 'role_id' => $role_id]);
    }

    /**
     * check if user has permission on this page
     * @param integer $permissionID
     *   id of permission
     *
     * @return boolean
     */
    public function hasPermission($permissionID) {
        $permissions = Database::getInstance()->selectAll(
            array(
                'from' => 'user',
                'join' => array(
                    array(
                        'LEFT JOIN',
                        'user_role',
                        'ON user_role.user_id = user.user_id'
                    ),
                    array(
                        'LEFT JOIN',
                        'role_permission',
                        'ON role_permission.role_id=user_role.role_id',
                    ),
                    array(
                        'LEFT JOIN',
                        'permission',
                        'ON role_permission.permission_id=permission.permission_id',
                    ),
                    array(
                        'JOIN',
                        'role',
                        'ON  user_role.role_id=role.role_id',
                    )
                )
            ),
            array(
                array('user.user_id' => $this->id),
                '#OR' => array(
                    array('permission.permission_id' => $permissionID),
                    array('permission.permission_id' => Permissions::ALL)
                )
            ),
            array('user.user_id', 'role.name', 'permission.name')
        );

        return (count($permissions) > 0 ) ?  TRUE :  FALSE;
    }

    public function initSocialMediaApi() {
        if (!Request::isCLI()) {

            if (strpos($this->email, '@@')) {
                $social_suffix = preg_replace('/.*@@/', '', $this->email);
                SocialMediaApi::initJS($social_suffix);
            }
        }
    }
}
