<?php
/**
 * @file
 * Contains Lightning\CLI\User
 */

namespace Lightning\CLI;

use Exception;
use Lightning\Model\Role;
use Lightning\Tools\Configuration;
use Lightning\Tools\Scrub;
use Lightning\Model\User as UserModel;

/**
 * A CLI interface for user functions.
 *
 * @package Lightning\CLI
 */
class User extends CLI {
    /**
     * Create an admin account. Will prompt for email address and password.
     */
    public function executeCreateAdmin() {
        do {
            if (!empty($email_input)) {
                $this->out('That is not a valid email.');
            }
            $email_input = $this->readline('Email: ');
        } while (!$email = Scrub::email($email_input));

        $min_pass_length = Configuration::get('user.min_password_length');
        do {
            $password = $this->readline('Password: ', true);
        } while (strlen($password) < $min_pass_length);

        try {
            $user = UserModel::create($email, $password);
            $user->addRole(Role::ADMIN);
        } catch (Exception $e) {
            $this->out('Failed to create user.');
        }
    }
}
