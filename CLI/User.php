<?php
/**
 * @file
 * Contains Lightning\CLI\User
 */

namespace Lightning\CLI;

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

        do {
            $password = $this->readline('Password: ', true);
        } while (strlen($password) < 6);

        $res = UserModel::create($email, $password);
        if ($res['success']) {
            $user = UserModel::loadById($res['data']);
            $user->setType(UserModel::TYPE_ADMIN);
        } else {
            $this->out('Failed to create user.');
        }
    }
}
