<?php

namespace Lightning\Jobs;

use Lightning\Tools\Logger;

abstract class Job {

    const NAME = 'Job';

    /**
     * In debug mode, this will output to the stdout.
     *
     * @var boolean
     */
    public $debug = false;

    public abstract function execute($job);

    public function out($string) {

        $string = '(JOB: ' . static::NAME . ') ' . $string;

        if ($this->debug) {
            Logger::print($string);
        }
        Logger::message($string);
    }
}
