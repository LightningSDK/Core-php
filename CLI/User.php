<?php
/**
 * @file
 * Contains lightningsdk\core\CLI\User
 */

namespace lightningsdk\core\CLI;

use Exception;
use lightningsdk\core\Model\Role;
use lightningsdk\core\Tools\Scrub;
use lightningsdk\core\Model\User as UserModel;

/**
 * A CLI interface for user functions.
 *
 * @package lightningsdk\core\CLI
 */
class User extends CLI {
    /**
     * Create an admin account. Will prompt for email address and password.
     *
     * @throws Exception
     */
    public function executeCreateAdmin() {

        if (empty($this->parameters['user']) || !$email = Scrub::email($this->parameters['user'])) {
            do {
                if (!empty($email_input)) {
                    $this->out('That is not a valid email.');
                }
                $email_input = $this->readline('Email: ');
            } while (!$email = Scrub::email($email_input));
        }

        if (empty($this->parameters['password'])) {
            do {
                $password = $this->readline('Password: ', true);
            } while (strlen($password) < 6);
        } else {
            $password = $this->parameters['password'];
        }

        $user = UserModel::register($email, $password);
        if ($user) {
            $user->addRole(Role::ADMIN);
        } else {
            $this->out('Failed to create user.');
        }
    }

    /**
     * Set a user's password
     *
     * example usage:
     *
     * lighting user set-password --user={user-email}
     *
     * @throws Exception
     */
    public function executeSetPassword() {
        if (empty($this->parameters['user']) || !Scrub::email($this->parameters['user'])) {
            throw new Exception('Invalid email');
        }
        $user = UserModel::loadByEmail($this->parameters['user']);
        if (empty($user)) {
            throw new Exception('Invalid user');
        }

        if (empty($this->parameters['password'])) {
            do {
                $password = $this->readline('Password: ', true);
            } while (strlen($password) < 6);
        } else {
            $password = $this->parameters['password'];
        }

        $user->setPass($password);
        $user->save();
    }
}
