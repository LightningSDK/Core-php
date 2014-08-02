<?php

namespace Lightning\Tools;

require_once HOME_PATH . '/Lightning/Vendor/PHPMailer/class.phpmailer.php';

class Mailer {
    protected $mailer;
    protected $fromSet = false;

    public function __construct() {
        $this->mail = new \PHPMailer(true);
    }

    public function from($email, $name = null) {
        $this->fromSet = true;
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
        // Set the default from name if it wasn't set.
        if (!$this->fromSet) {
            $this->from(
                Configuration::get('site.mail_from'),
                Configuration::get('site.mail_from_name')
            );
        }

        // Send the message.
        try {
            return $this->mail->send();
        } catch (\Exception $e) {
            Messenger::error($e->getMessage());
            return false;
        }
    }
}
