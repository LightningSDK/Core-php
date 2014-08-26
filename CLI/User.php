<?php

namespace Lightning\CLI;

use Lightning\Tools\Scrub;
use Lightning\Model\User as UserObj;

class User extends CLI {
    public function executeCreateAdmin() {
        do {
            if (!empty($email_input)) {
                echo "That is not a valid email.\n";
            }
            $email_input = readline('Email: ');
        } while (!$email = Scrub::email($email_input));

        do {
            $password = readline('Password: ');
        } while (strlen($password) < 6);

        $user = UserObj::create($email, $password);
        if ($user) {
            $user->setType(UserObj::TYPE_ADMIN);
        } else {
            echo "Failed to create user.\n";
        }
    }
}
