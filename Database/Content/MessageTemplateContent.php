<?php

namespace lightningsdk\core\Database\Content;

use lightningsdk\core\Database\Content;

class MessageTemplateContent extends Content {
    protected $table = 'message_template';

    public function getContent() {
        return [
            [
                'title' => 'Default',
                'subject' => '{subject}',
                'body' => '<html><head><title></title><style type="text/css">body {background-color: #f6f6f6;font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;}.body {background-color: white; border: 1px solid #f0f0f0; padding: 20px; margin: 0 20px;}.footer {text-align:center;margin: 0 20px;font-size:12px;color:#666666;}.footer a{color:#999999;}.column {max-width:650px;margin:auto;}</style></head><body><div class="column"><div class="body"><p>{CONTENT_BODY}</p></div><div class="footer"><p>{UNSUBSCRIBE}{TRACKING_IMAGE}</p></div></div></body></html>',
            ]
        ];
    }
}
