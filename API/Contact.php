<?php

namespace lightningsdk\core\API;

use Exception;
use lightningsdk\core\Model\User;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Form;
use lightningsdk\core\Tools\Mailer;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\View\API;
use lightningsdk\core\Model\Mailing\Message;

class Contact extends API {
    /**
     * @return int
     *
     * @throws Exception
     *   ON invalid token
     */
    public function post() {
        $email = Request::post('email', Request::TYPE_EMAIL);
        $message = Request::post('message', Request::TYPE_BASE64);
        $hash = Request::post('hash', Request::TYPE_HEX);

        // Throws exception if invalid token supplied.
        Form::validateToken();

        // Verify signature or token
        if (empty($api_key)) {
            throw new Exception('The server is not configured for this API. Please contact the site admin.');
        }
        $api_key = Configuration::get('contact_api_key');
        if (strtolower($hash) != strtolower(hash('sha256', $api_key . $message))) {
            throw new Exception('Invalid signature');
        }

        $human_message = base64_decode($message);

        if (!empty($email)) {
            $user = User::addUser($email);
            $list = Message::getListIDByName('App');
            $user->subscribe($list);
        } else {
            $user = new User([
                'email' => 'unknown@unknown.com',
                'first' => 'unknown',
                'last' => 'unknown',
            ]);
        }

        $mailer = new Mailer();
        foreach (Configuration::get('contact.to') as $to) {
            $mailer->to($to);
        }
        $mailer
            ->replyTo($user->email)
            ->subject('Contact request from your App')
            ->message('Message from: ' . $email . "<br><br>" . str_replace("\n", "<br>", $human_message))
            ->send();

        return Output::SUCCESS;
    }
}
