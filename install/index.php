<?php

use Lightning\Tools\Messenger;
use Lightning\Tools\Request;
use Lightning\Tools\Router;

include 'Lightning/bootstrap.php';

$handler = Router::getInstance()->getRoute($_GET['request']);

if (empty($handler)) {
    // TODO: show 404;
    echo 'No handler found.';
    exit;
}

try {
    $page = new $handler();
    $request_type = strtolower(Request::type());
    if (in_array(strtolower($request_type), get_class_methods($page))) {
        $page->$request_type();
        $page->output();
    } else {
        // TODO: show 302
        echo 'Method not available';
        exit;
    }
} catch (Exception $e) {
    $errors = Messenger::getErrors();
    array_unshift($errors, $e->getMessage());
    echo implode("\n", $errors);
}
