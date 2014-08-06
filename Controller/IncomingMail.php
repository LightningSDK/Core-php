<?php

namespace Lightning\Controller;

abstract class IncomingMail {
    /**
     * An email parser.
     *
     * @var \PlancakeEmailParser
     */
    protected $email;

    public function __construct() {
        // Parse the incoming email.
        require_once HOME_PATH . '/Lightning/Vendor/plancakeEmailParser/PlancakeEmailParser.php';
        $this->email = new \PlancakeEmailParser(file_get_contents('php://stdin'));
    }

    public function getTo() {
        return $this->email->getTo();
    }

    public function getFrom() {
        return $this->email->getTo();
    }

    public function getSubject() {
        return $this->email->getSubject();
    }

    public function getHTMLBody() {
        return $this->email->getHTMLBody();
    }

    public function getPlainBody() {
        return $this->email->getPlainBody();
    }
}
