<?php

use Lightning\Tools\Messenger;
use Lightning\Tools\Request;
use Lightning\Tools\Router;

define('HOME_PATH', __DIR__);

require_once 'Lightning/bootstrap.php';

if (PHP_SAPI == 'cli') {
    // Handle a command line request.
    $handler = Router::getInstance()->getRoute($argv[1], true);
} else {
    // Handle a web page request.
    $handler = Router::getInstance()->getRoute($_GET['request'], false);
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
