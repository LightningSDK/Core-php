<?php

use Lightning\Tools\Messenger;
use Lightning\Tools\Request;
use Lightning\Tools\Router;

define('HOME_PATH', __DIR__);

require_once 'Lightning/bootstrap.php';

$handler = Router::getRoute();

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
