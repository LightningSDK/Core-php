<?php
/**
 * @file
 * Contains Lightning\CLI\User
 */

namespace Lightning\CLI;

use Lightning\Model\Role;
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

        $res = UserModel::create($email, $password);
        if ($res['success']) {
            $user = UserModel::loadById($res['data']);
            $user->addRole(Role::ADMIN);
        } else {
            $this->out('Failed to create user.');
        }
    }

    public function executeSetPassword() {
        if (empty($this->parameters['user']) || !Scrub::email($this->parameters['user'])) {
            throw new \Exception('Invalid email');
        }
        $user = UserModel::loadByEmail($this->parameters['user']);
        if (empty($user)) {
            throw new \Exception('Invalid user');
        }

        if (empty($this->parameters['password'])) {
            do {
                $password = $this->readline('Password: ', true);
            } while (strlen($password) < 6);
        } else {
            $password = $this->parameters['password'];
        }

        $user->setPass($password);
    }
}
