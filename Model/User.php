<?php

namespace Lightning\Model;

use Exception;
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
use Lightning\Tools\Session\DBSession;
use Lightning\Tools\SocialDrivers\SocialMediaApi;

/**
 * Class User
 * @package Overridable\Lightning\Model
 *
 * @property integer $id
 * @property string $email
 * @property string $first
 * @property string $last
 * @property string $timezone
 * @property integer $created
 * @property integer $registered
 * @property integer $last_login
 * @property integer $last_active
 * @property string $password
 * @property string $salt
 * @property boolean $new
 * @property integer $referrer
 */
class UserOverridable extends BaseObject {

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

    protected $permissions;

    /**
     * Load a user by their email.
     *
     * @param $email
     *
     * @return static
     *
     * @throws Exception
     */
    public static function loadByEmail($email) {
        if ($details = Database::getInstance()->selectRow(self::TABLE, ['email' => ['LIKE', $email]])) {
            return new static($details);
        }
        return null;
    }

    /**
     * Load a user by their temporary access key, from a password reset link.
     *
     * @param string $key
     *   A temporary access key.
     *
     * @return static
     *
     * @throws Exception
     */
    public static function loadByTempKey($key) {
        if ($details = Database::getInstance()->selectRow(
            [
                'from' => 'user_temp_key',
                'join' => [
                    'LEFT JOIN',
                    self::TABLE,
                    'using (`user_id`)',
                ]
            ],
            [
                'temp_key' => $key,
                // The key is only good for 24 hours.
                'time' => ['>=', time() - static::TEMP_KEY_TTL],
            ]
        )) {
            return new static ($details);
        }
        return null;
    }

    /**
     * @deprecated
     * @param $values
     */
    public function update($values) {
        $this->__data = $values + $this->__data;
        Database::getInstance()->update(self::TABLE, $values, ['user_id' => $this->id]);
    }

    /**
     * Create a new anonymous user.
     *
     * @return static
     */
    public static function anonymous() {
        return new static(['user_id' => 0]);
    }

    /**
     * Check if the current user is being impersonated by an admin.
     *
     * @return boolean
     *   Whether the current user is impersonated.
     */
    public function isImpersonating() {
        $session = DBSession::getInstance(true, false);
        return $session && !empty($session->content->impersonate);
    }

    /**
     * If the current user is impersonating another user, this will return the
     * impersonating admin user's id.
     */
    public function impersonatingParentUser() {
        if ($this->isImpersonating()) {
            return DBSession::getInstance()->user_id;
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
        return !$this->isAnonymous() && $this->hasPermission(Permissions::ALL);
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
        if ($hashed_pass == $this->passHash($pass, $salt)) {
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
        return hash('sha256', $pass . pack('H*', $salt));
    }

    /**
     * Get a new salt string.
     *
     * @return string
     *   A binary string of salt.
     */
    public static function getSalt() {
        return Random::getInstance()->get(32, Random::HEX);
    }

    public static function urlKey($user_id = -1, $salt = null) {
        if ($user_id == -1) {
            $user_id = ClientUser::getInstance()->id;
            $salt = ClientUser::getInstance()->salt;
        } elseif (!$salt) {
            $user = Database::getInstance()->selectRow(self::TABLE, ['user_id' => $user_id]);
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
        Database::getInstance()->update(self::TABLE, ['last_active' => time()], ['user_id' => $this->id]);
    }

    /**
     * Reload the user's info from the database.
     *
     * @param boolean $force
     *   Whether to force the data to load and overwrite current data.
     */
    public function load_info($force = false) {
        if (!isset($this->__data) || $force) {
            $this->__data = Database::getInstance()->selectRow(self::TABLE, ['user_id' => $this->id]);
        }
    }

    /**
     * Registers user, create the user, subscribe them and sign in.
     *
     * @param string $email email
     * @param string $pass password
     *
     * @return User
     *
     * @throws Exception
     */
    public static function registerAndSignIn($email, $pass, $data = []) {
        // Save current user for further anonymous check
        $user = ClientUser::getInstance();
        $previous_user = $user->id;

        // Try to create a user or throw exception.
        $user = self::register($email, $pass, $data);

        // Register the user to the session
        self::login($email, $pass);
        $user = ClientUser::getInstance();
        Tracker::loadOrCreateByName(Tracker::REGISTER, Tracker::USER)->track(0, $user->id);
        $user->subscribe(Configuration::get('mailer.default_list'));

        // Merge with a previous anon user if necessary.
        if ($previous_user != 0) {
            // TODO: This should only happen if the user is a placeholder.
            $user->merge_users($previous_user);
        }

        return $user;
    }

    /**
     * Registers a new user or sets a password if a user is only in the mailing list.
     *
     * @param string $email
     *   The user's email address.
     * @param string $pass
     *   The new password.
     * @param array $data
     *   Other fields
     *
     * @return User
     *   The user object if it was successfully registered
     *
     * @throws Exception
     *   If the user is already registered
     */
    public static function register($email, $pass, $data = []) {
        $user = User::loadByEmail($email);
        if ($user && $user->password) {
            // An account already exists with that email.
            throw new Exception('A user with that email already exists');
        } elseif ($user) {
            // The user is only in a mailing list.
            $user->setPass($pass);
            $user->registered = time();
            $user->save();
        } else {
            // The user is not in the DB at all.
            $user = static::create([
                'email' => $email,
                'pass' => $pass,
            ] + $data);
        }

        $user->sendConfirmationEmail();
        return $user;
    }

    /**
     * Ensure a user is in the database, creates the user if it does not exist.
     *
     * @param string $email
     *   The user's email.
     * @param array $options
     *   Additional values to insert.
     * @param array $update
     *   Which values to update the user if the email already exists.
     *
     * @return User
     *
     * @throws Exception
     *   If the user could not be created
     */
    public static function addUser($email, $options = [], $update = []) {
        $user_data = [];
        $user_data['email'] = strtolower($email);
        if ($user = User::loadByEmail($email)) {
            // If the user already exists, return it. This does not log in, but should
            // be treated as sensitive data.
            if (!empty($update)) {
                static::parseNames($update);
                foreach ($update as $key => $val) {
                    $user->$key = $val;
                }
            }
            return $user;
        } else {
            static::parseNames($options);
            $user = static::create($options + $user_data);
            return $user;
        }
    }

    /**
     * Creates a new user. Throws an exception if the user exists at all.
     *
     * @param array $data
     *   The new user data
     *
     * @return User
     *   The new user.
     *
     * @throws Exception
     *   On invalid email.
     */
    public static function create($data) {
        $email = Scrub::email(strtolower($data['email']));
        if (empty($email)) {
            throw new Exception('Invalid email!');
        }

        $time = time();
        $data = [
                'email' => $email,
                'created' => $time,
                'confirmed' => static::requiresConfirmation() ? static::UNCONFIRMED : static::CONFIRMED,
                'referrer' => ClientUser::getReferrer(),
            ] + $data;
        if (isset($data['pass'])) {
            $rawPass = $data['pass'];
            unset($data['pass']);
        }
        $user = new User($data);
        if (!empty($rawPass)) {
            $user->setPass($rawPass);
            $user->registered = $time;
        }
        $user->save();

        if ($user->id) {
            $user->new = true;
        } else {
            // Track the error
            Tracker::loadOrCreateByName(Tracker::REGISTER_ERROR, Tracker::ERROR)->track(0);
            throw new Exception('Could not create user');
        }

        return $user;
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
            [
                'message_list_id' => $list_id,
                'user_id' => $this->id,
                'time' => time(),
            ],
            true
        )) {
            // If a result was returned, they were added to the list.
            Tracker::loadOrCreateByName(Tracker::SUBSCRIBE, Tracker::USER)->track($list_id, $this->id);
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
        Database::getInstance()->delete('message_list_user', ['user_id' => $this->id]);
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
        $pass = [];
        for ($i = 0; $i < strlen($arrangement); $i++) {
            switch ($arrangement[$i]) {
                case 'a':
                    $pass[] = $alphabet[rand(0,25)];
                    break;
                case 'A':
                    $pass[] = strtoupper($alphabet[rand(0,(strlen($alphabet)-1))]);
                    break;
                case 'n':
                default:
                    $pass[] = rand(0,9);
            }
        }
        shuffle($pass);
        return implode($pass);
    }

    public function __set($var, $value) {
        if ($var == 'email') {
            $value = strtolower($value);
        }
        parent::__set($var, $value);
    }

    /**
     * Update a user's password.
     *
     * @param string $pass
     *   The new password.
     */
    public function setPass($pass) {
        $this->salt = $this->getSalt();
        $this->password = $this->passHash($pass, $this->salt);
    }

    /**
     * Has to be redone. Not currently in use.
     *
     * @param $email
     * @param string $first_name
     * @param string $last_name
     *
     * @return int|User
     *
     * @throws Exception
     */
    public function admin_create($email, $first_name='', $last_name='') {
        $today = gregoriantojd(date('m'), date('d'), date('Y'));
        $user = User::loadByEmail($email);
        if ($user->password) {
            // user exists with password
            // return user_id
            return $user->id;
        } elseif ($user->id) {
            // user exists without password
            // set password, send email
            $randomPass = $this->randomPass();
            $user->setPass($randomPass);
            $user->save();
            $mailer = new Mailer();
            $mailer
                ->to($email)
                ->subject('New Account')
                ->message("Your account has been created with a temporary password. Your temporary password is: {$randomPass}\n\nTo reset your password, log in with your temporary password and click 'my profile'. Follow the instructions to reset your new password.")
                ->send();
            $user->registered = time();
            $user->confirmed = static::requiresConfirmation() ? static::UNCONFIRMED : static::CONFIRMED;
            $user->save();
            return $user->id;
        } else {
            // user does not exist
            // create user with random password, send email to activate
            $randomPass =
            $data = [
                'email' => $email,
                'pass' => $this->randomPass(),
                'first' => $first_name,
                'last' => $last_name,
            ];
            $user_id = static::create($data);
            $mailer = new Mailer();
            $mailer
                ->to($email)
                ->subject('New Account')
                ->message("Your account has been created with a temporary password. Your temporary password is: {$randomPass}\n\nTo reset your password, log in with your temporary password and click 'my profile'. Follow the instructions to reset your new password.")
                ->send();
            Database::getInstance()->update(
                self::TABLE,
                [
                    'registered' => $today,
                    'confirmed' => static::requiresConfirmation() ? static::UNCONFIRMED : static::CONFIRMED,
                ],
                [
                    'user_id' => $user_id,
                ]
            );
            return $user_id;
        }

    }

    /**
     * Return the combined first and last names.
     *
     * @return string
     */
    public function fullName() {
        return $this->first . ' ' . $this->last;
    }

    /**
     * Replace input data 'full_name' field with 'first' and 'last' fields.
     * If the full_name field is not present, the array will not be modified.
     * If the full_name field is present, it will be removed after inserting first and last names.
     *
     * @param array $data
     *   The user input data.
     */
    public static function parseNames(&$data) {
        if (!empty($data['full_name'])) {
            $name = explode(' ', $data['full_name'], 2);
            $data['first'] = $name[0];
            if (!empty($name[1])) {
                $data['last'] = $name[1];
            }
        }
        unset($data['full_name']);
    }

    /**
     * Send a new random password via email.
     */
    public function sendResetLink() {
        // Create a temporary key.
        $reset_key = base64_encode($this->getSalt());
        Database::getInstance()->insert(
            'user_temp_key',
            [
                'user_id' => $this->id,
                'temp_key' => $reset_key,
                'time' => time(),
            ],
            [
                'temp_key' => $reset_key,
                'time' => time(),
            ]
        );

        // Send a message.
        $mailer = new Mailer();
        $mailer->to($this->email, $this->fullName())
            ->subject('Password reset')
            ->message('A request was made to reset your password. If you did not make this request, please <a href="' . Configuration::get('web_root') . '/contact' . '">notify us</a>. To reset your password, <a href="' . Configuration::get('web_root') . '/user?action=set-password&key=' . $reset_key . '">click here</a>.');
        return $mailer->send();
    }

    /**
     * Delete the temporary password reset key.
     */
    public function removeTempKey() {
        Database::getInstance()->delete(
            'user_temp_key',
            [
                'user_id' => $this->id,
            ]
        );
    }

    public static function removeExpiredTempKeys() {
        return Database::getInstance()->delete(
            'user_temp_key',
            [
                'time' => ['<', time() - static::TEMP_KEY_TTL]
            ]
        );
    }

    public static function find_by_email($email) {
        return Database::getInstance()->selectRow(self::TABLE, ['email' => strtolower($email)]);
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
                $temp_user->registerToSession($remember, $auth_only ?: DBSession::STATE_PASSWORD);
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
        $this->__data = [];
        DBSession::reset();
    }

    /**
     * @param bool $remember
     * @param int $state
     *
     * TODO: Make private, require the user of login() instead.
     */
    public function registerToSession($remember = false, $state = DBSession::STATE_PASSWORD) {
        // We need to create a new session if:
        //  There is no session
        //  The session is blank
        //  The session user is not set to this user
        $session = DBSession::getInstance(true, false);
        // If there is a session, there is cleanup work to do.
        if (is_object($session) && $session->user_id == 0) {
            // If this is an anonymous session, we want to update any tables with session reference to user reference.
            if ($session->user_id == 0) {
                $convert_tables = Configuration::get('session.user_convert');
                if (is_array($convert_tables)) {
                    foreach ($convert_tables as $table) {
                        Database::getInstance()->update($table, [
                            'user_id' => $this->id,
                        ], [
                            'session_id' => $session->id,
                        ]);
                    }
                }
            }
        }
        // If it is not a session or is an anonymous session or other user, we need to create a new one.
        if ((!is_object($session)) || ($session->id == 0) || ($session->user_id != $this->id && $session->user_id != 0)) {
            // If there is some other session here, we can destroy it.
            if (is_object($session) && !empty($session->id)) {
                $session->destroy();
            }
            $session = DBSession::create($this->id, $remember);
            DBSession::setInstance($session);
        }

        // Set the user id and state.
        if ($session->user_id == 0) {
            $session->setUser($this->id);
        }
        if ($state) {
            $session->setState($state);
        }

        // Load this session into the static instance.
        ClientUser::setInstance($this);
    }

    /**
     * Destroy a user object and end the session.
     */
    public function logOut() {
        $session = DBSession::getInstance();
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
     * @return static
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
        if (Database::getInstance()->check(self::TABLE, ['user_id' => $anon_user, 'email' => ''])) {
            // TODO: Basic information should be moved here, but this function should be overriden.
            Database::getInstance()->delete(self::TABLE, ['user_id' => $anon_user]);
        }
    }

    public function addRole($role_id) {
        Database::getInstance()->insert('user_role', ['user_id' => $this->id, 'role_id' => $role_id], true);
    }

    public function removeRole($role_id) {
        Database::getInstance()->delete('user_role', ['user_id' => $this->id, 'role_id' => $role_id]);
    }

    public function loadPermissions($force = false) {
        if (!$force && isset($this->permissions)) {
            return;
        }
        $this->permissions = Database::getInstance()->selectColumnQuery([
            'from' => self::TABLE,
            'join' => [
                [
                    'LEFT JOIN',
                    'user_role',
                    'ON user_role.user_id = user.user_id'
                ],
                [
                    'LEFT JOIN',
                    'role_permission',
                    'ON role_permission.role_id=user_role.role_id',
                ],
                [
                    'LEFT JOIN',
                    'permission',
                    'ON role_permission.permission_id=permission.permission_id',
                ],
                [
                    'JOIN',
                    'role',
                    'ON  user_role.role_id=role.role_id',
                ]
            ],
            'where' => [
                ['user.user_id' => $this->id],
            ],
            'select' => ['permission.permission_id', 'permission.permission_id'],
        ]);
    }

    /**
     * check if user has permission on this page
     * @param integer $permissionID
     *   id of permission
     *
     * @return boolean
     */
    public function hasPermission($permissionID) {
        $this->loadPermissions();
        return !empty($this->permissions[$permissionID]) || !empty($this->permissions[Permissions::ALL]);
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
