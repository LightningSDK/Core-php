<?php
/**
 * @file
 * Contains Lightning\Controller\IncomingMail
 */

namespace Lightning\Controller;

use Lightning\CLI\CLI;

/**
 * A base controller for handling incoming mail.
 *
 * @package Lightning\Controller
 */
abstract class IncomingMail extends CLI {

    /**
     * An email parser.
     *
     * @var \PlancakeEmailParser
     */
    protected $email;

    /**
     * Loads email parser and parses from stdin.
     */
    public function __construct() {
        // Parse the incoming email.
        require_once HOME_PATH . '/Lightning/Vendor/plancakeEmailParser/PlancakeEmailParser.php';
        $this->email = new \PlancakeEmailParser(file_get_contents('php://stdin'));
    }

    /**
     * Gets the to addresses.
     *
     * @return array
     */
    public function getTo() {
        return $this->email->getTo();
    }

    /**
     * Gets the from addresses.
     *
     * @return array
     */
    public function getFrom() {
        return $this->email->getFrom();
    }

    /**
     * Gets the message subject.
     *
     * @return string
     */
    public function getSubject() {
        return $this->email->getSubject();
    }

    /**
     * Gets the HTML body.
     *
     * @return mixed|string
     */
    public function getHTMLBody() {
        return $this->email->getHTMLBody();
    }

    /**
     * Gets the plaintext body.
     *
     * @return string
     */
    public function getPlainBody() {
        return $this->email->getPlainBody();
    }
}
