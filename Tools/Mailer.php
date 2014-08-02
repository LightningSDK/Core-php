<?php

namespace Lightning\Tools;

require_once HOME_PATH . '/Lightning/vendor/class.phpmailer.php';

class Mailer {
    protected $mailer;

    public function __construct() {
        $this->mail = new \PHPMailer();
    }

    public function from($email, $name = null) {
        $this->mail->AddReplyTo($email, $name);
        $this->mail->SetFrom($email, $name);
        $this->mail->AddReplyTo($email, $name);
        return $this;
    }

    public function to($email, $name = null) {
        $this->mail->AddAddress($email, $name);
        return $this;
    }

    public function subject($subject) {
        $this->mail->Subject = $subject;
        return $this;
    }

    public function message($message) {
        $this->mail->HTMLBody = $message;
        $this->mail->Body = $message;
        return $this;
    }

    public function send() {
        return $this->mail->send();
    }
}
