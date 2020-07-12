<?php

namespace lightningsdk\core\CLI;

use lightningsdk\core\Tools\Configuration;

class Gulp extends CLI {
    public function execute() {
        echo json_encode([
            'js' => Configuration::get('compiler.js', []),
            'css' => Configuration::get('compiler.css', []),
            'sass' => Configuration::get('compiler.sass', []),
            'copy' => Configuration::get('compiler.copy', []),
            'npm' => Configuration::get('compiler.npm', []),
        ]);
    }
}
