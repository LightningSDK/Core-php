<?php

namespace lightningsdk\core\CLI;

use Exception;
use lightningsdk\core\Tools\Configuration;

class Job extends CLI {
    public function execute() {
        global $argv;
        if (!isset($argv[2])) {
            throw new Exception('No command specified.');
        }

        $job = Configuration::get('jobs.' . $argv[2]);
        if (empty($job)) {
            $this->out('No job found');
            return;
        }

        $job['last_start'] = 0;
        $obj = new $job['class']();
        $obj->execute($job);
    }
}
