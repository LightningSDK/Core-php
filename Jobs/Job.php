<?php

namespace Lightning\Jobs;

use Lightning\Tools\Logger;

class Job {

    public $name;

    public function execute($job) {
    }

    public function out($string) {
        Logger::message($string, true);
    }
}
