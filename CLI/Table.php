<?php

namespace Lightning\CLI;

use Exception;

class Table extends CLI {
    public function executeUpdateImages($table_class) {
        $missing_only = false;
        $id = null;

        $args = func_get_args();
        array_shift($args);
        foreach ($args as $arg) {
            if ($arg == 'missing-only') {
                $missing_only = true;
                continue;
            }
            if (preg_match('/id=([0-9]+)/', $arg, $matches)) {
                $id = $matches[1];
                continue;
            }

            throw new Exception('Unrecognized parameter: ' . $arg);
        }

        $table = new $table_class();
        $table->updateImages($missing_only, $id);
    }
}
