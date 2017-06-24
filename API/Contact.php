<?php

namespace Lightning\API;

use Lightning\Model\User;
use Lightning\Tools\Configuration;
use Lightning\Tools\Mailer;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\View\API;
use Source\Model\Message;

class Contact extends API {
    public function post() {
        $email = Request::post('email', Request::TYPE_EMAIL);
        $message = Request::post('message', Request::TYPE_BASE64);
        $hash = Request::post('hash', Request::TYPE_HEX);
        if ($token = Request::post('token', Request::TYPE_BASE64)) {
            if ($token != Session::getInstance()->getToken()) {
                throw new \Exception('Invalid Token');
            }
            $human_message = $message;
        } else {
            // Verify signature or token
            if (empty($api_key)) {
                throw new \Exception('The server is not configured for this API. Please contact the site admin.');
            }
            $api_key = Configuration::get('contact_api_key');
            if (strtolower($hash) != strtolower(hash('sha256', $api_key . $message))) {
                throw new \Exception('Invalid signature');
            }

            $human_message = base64_decode($message);
        }

        if (!empty($email)) {
            $user = User::addUser($email);
            $list = Message::getListId('App');
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