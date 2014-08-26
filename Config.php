<?php

$conf = array(
    'classes' => array(
        'Lightning\\View\\Page' => 'Overridable\\Lightning\\View\\Page',
    ),
    'overridable' => array(
        'Overridable\\Lightning\\View\\Page' => 'Overridable\\Lightning\\View\\Page',
    ),
    'routes' => array(
        'cli_only' => array(
            'database' => 'Lightning\\CLI\\Database',
            'user' => 'Lightning\\CLI\\User',
        ),
    ),
    'language' => 'en_us',
);
