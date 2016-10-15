<?php

use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Router;

define('HOME_PATH', __DIR__);

require_once 'Lightning/bootstrap.php';

$handler = Router::getRoute();

if (empty($handler)) {
    if (Request::isCLI()) {
        echo "No handler found.\n";
    } else {
        Output::http(404);
    }
    exit;
}

try {
    $page = new $handler();
    $page->execute();
} catch (Exception $e) {
    $errors = Messenger::getErrors();
    array_unshift($errors, $e->getMessage());
    echo implode("\n", $errors) . "\n";
    \Lightning\Tools\Logger::exception($e);
}
