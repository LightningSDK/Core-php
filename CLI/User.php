<?php
/**
 * @file
 * Contains Lightning\CLI\User
 */

namespace Lightning\CLI;

use Lightning\Tools\Scrub;
use Lightning\Tools\ClientUser;
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
                echo "That is not a valid email.\n";
            }
            $email_input = $this->readline('Email: ');
        } while (!$email = Scrub::email($email_input));

        do {
            $password = $this->readline('Password: ');
        } while (strlen($password) < 6);

        $res = UserModel::create($email, $password);
        if ($res['success']) {
            $user = ClientUser::getInstance(); 
            $user->setType(UserModel::TYPE_ADMIN);
        } else {
            echo "Failed to create user.\n";
        }
    }
}
