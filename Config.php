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
            'conform-schema' => 'Lightning\\Tools\\DatabaseSchemaManager',
        ),
    ),
    'language' => 'en_us',
);
