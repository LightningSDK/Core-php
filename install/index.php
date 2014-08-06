<?php

use Lightning\Tools\Messenger;
use Lightning\Tools\Request;
use Lightning\Tools\Router;

// Set required global parameters.
if (!defined('HOME_PATH')) {
    define('HOME_PATH', empty($home_path) ? __DIR__ : $home_path);
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', HOME_PATH . '/Source/config');
}

require 'Lightning/bootstrap.php';

if (!empty($_SERVER['TERM']) || !empty($_SERVER['SHELL'])) {
    // Handle a command line request.
    $handler = Router::getInstance()->getRoute($argv[1]);
} else {
    // Handle a web page request.
    $handler = Router::getInstance()->getRoute($_GET['request']);
}

if (empty($handler)) {
    // TODO: show 404;
    echo "No handler found.\n";
    exit;
}

try {
    $page = new $handler();
    $page->execute();
} catch (Exception $e) {
    $errors = Messenger::getErrors();
    array_unshift($errors, $e->getMessage());
    echo implode("\n", $errors);
}
