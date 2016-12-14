<?php

namespace Lightning\CLI;

use Lightning\Tools\Configuration;

class Gulp extends CLI {
    public function execute() {
        echo json_encode([
            'js' => Configuration::get('js', [])
        ]);
    }
}
