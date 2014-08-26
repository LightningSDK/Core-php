<?php

$conf = array(
    'overridable' => array(
        'Lightning\\View\\Page' => 'Overridable\\Lightning\\View\\Page',
    ),
    'routes' => array(
        'cli_only' => array(
            'database' => 'Lightning\\CLI\\Database',
            'user' => 'Lightning\\CLI\\User',
        ),
    ),
    'language' => 'en_us',
);
