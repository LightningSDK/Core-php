<?php

use Lightning\Tools\Messenger;
use Lightning\Tools\Request;
use Lightning\Tools\Router;

require 'Lightning/bootstrap.php';

$handler = Router::getInstance()->getRoute($_GET['request']);

if (empty($handler)) {
    // TODO: show 404;
    echo 'No handler found.';
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
