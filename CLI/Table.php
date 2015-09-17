<?php

namespace Lightning\CLI;

class Table extends CLI {
    public function executeUpdateImages($table_class) {
        $table = new $table_class();
        $table->updateImages();
    }
}
