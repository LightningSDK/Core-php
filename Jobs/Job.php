<?php

namespace Lightning\Jobs;

use Lightning\Tools\Logger;

class Job {

    public $name;

    /**
     * In debug mode, this will output to the stdout.
     *
     * @var boolean
     */
    public $debug = false;

    public function execute($job) {
    }

    public function out($string) {
        Logger::message($string);
    }
}
