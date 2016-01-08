<?php

namespace Lightning\Pages;

use Overridable\Lightning\Tools\ClientUser;

class BlogCategories extends Table {
    protected $table = 'blog_category';

    public function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }
}
