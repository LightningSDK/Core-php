<?php

namespace Lightning;

// Set required global parameters.
if (!defined('HOME_PATH')) {
    define('HOME_PATH', __DIR__ . '/..');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', HOME_PATH . '/Source/Config');
}

use lightningsdk\core\Tools\Logger;
use lightningsdk\core\Tools\ClassLoader;
use lightningsdk\core\Tools\Performance;

// Set the autoloader to the Lightning autoloader.
require_once __DIR__ . '/Tools/ClassLoader.php';
spl_autoload_register(['lightningsdk\\core\\Tools\\ClassLoader', 'classAutoloader']);

Performance::startTimer();

if (!defined('LIGHTNING_BOOTSTRAP_NO_LOGGER')) {
    // Set the error handler.
    Logger::init();
}
