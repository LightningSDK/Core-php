<?php

namespace Lightning;

// Set required global parameters.
if (!defined('HOME_PATH')) {
    define('HOME_PATH', __DIR__ . '/..');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', HOME_PATH . '/Source/Config');
}

use Lightning\Tools\Logger;
use Lightning\Tools\ClassLoader;

// Set the autoloader to the Lightning autoloader.
require_once HOME_PATH . '/Lightning/Tools/ClassLoader.php';
spl_autoload_register(array('Lightning\\Tools\\ClassLoader', 'classAutoloader'));

if (!defined('LIGHTNING_BOOTSTRAP_NO_LOGGER')) {
    // Set the error handler.
    Logger::init();
}
