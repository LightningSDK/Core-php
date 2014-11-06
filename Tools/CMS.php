<?php

namespace Lightning\Tools;

use Lightning\View\JS;

class CMS {
    public static function embed($name, $default_value = '') {
        JS::set('token', Session::getInstance()->getToken());
        $content = Database::getInstance()->selectRow('cms', array('name' => $name));
        $content = (!empty($content) ? $content['content'] : $default_value);
        if (ClientUser::getInstance()->isAdmin()) {
            return
                '<a href="javascript:lightning.cms.edit(\'cms_' . $name . '\')" class="button" id="cms_edit_' . $name . '">Edit</a>'
                . '<a href="javascript:lightning.cms.save(\'cms_' . $name . '\')" class="button hide" id="cms_save_' . $name . '">Save</a>'
                . CKEditor::editableDiv('cms_' . $name,
                    array('spellcheck' => true, 'content' => $content, 'finder' => true)
                );
        } else {
            return '<div>' . $content . '</div>';
        }
    }
}
