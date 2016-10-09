<?php

namespace Lightning\Model;

class PermissionsOverridable extends Object {
    /**
     * Default permission for admins.
     */
    const ALL = 1;
    const EDIT_PAGES = 2;
    const EDIT_BLOG = 3;
    const EDIT_MAIL_MESSAGES = 4;
    const SEND_MAIL_MESSAGES = 5;
    const EDIT_USERS = 6;
    const EDIT_CMS = 7;

    const TABLE = 'permission';
    const PRIMARY_KEY = 'permission_id';
}
