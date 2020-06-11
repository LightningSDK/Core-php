<?php

namespace lightningsdk\core\Pages\SocialSharing;

use lightningsdk\core\Model\Permissions;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Database;
use lightningsdk\core\Tools\SocialDrivers\Facebook;
use lightningsdk\core\Tools\SocialDrivers\SocialMediaApi;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\JS;
use lightningsdk\core\View\Page;
use lightningsdk\core\Model\BlogPost;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Model\SocialAuth;

class Share extends Page {

    protected $page = ['admin/social/share', 'lightningsdk/core'];

    protected $type;
    protected $id;

    public function __construct() {
        // Custom construct and app ID override
        parent::__construct();
        $overlay = Database::getInstance()->selectRow('overlay', ['site_id' => 16]);
        Configuration::set('social.facebook', [
            'appid' => $overlay['app_id'],
            'secret' => $overlay['secret'],
            // This line can stay if this module is converted publicly.
            'scope' => 'pages_show_list,manage_pages,publish_pages,public_profile,publish_actions'
        ]);

        $this->type = Request::get('type');
        $this->id = Request::get('id', 'int');
    }

    public function hasAccess() {
        return ClientUser::requirePermission(Permissions::ALL);
    }

    public function get() {
        $authorizations = SocialAuth::getAuthorizations();

        // Load facebook pages
        $facebook_pages = [];
        foreach ($authorizations as $auth) {
            switch ($auth['network']) {
                case 'facebook':
                    $facebook = SocialMediaApi::connect($auth);
                    $pages = $facebook->getPages();
                    if (!empty($pages)) {
                        $facebook_pages = array_merge($facebook_pages, $pages);
                    }
                    break;
                case 'google':
                    $google = SocialMediaApi::connect($auth);
                    $pages = $google->getPages();
                    break;
            }
        }

        $template = Template::getInstance();
        $template->set('authorizations', $authorizations);
        $template->set('facebook_pages', $facebook_pages);
        $template->set('type', $this->type);
        $template->set('id', $this->id);
        JS::startup('lightning.forms.init();');
    }

    public function postShare() {
        $networks = Request::post('network', 'array', 'int', []);
        $facebook_pages = Request::post('facebook', 'array', 'int');
        $social_auths = SocialAuth::loadAll(['user_id' => ClientUser::getInstance()->id]);
        $content = $this->loadContent();
        $text = Request::post('text');
        $short_text = Request::post('short_text');
        foreach ($social_auths as $auth) {
            if (in_array($auth->social_auth_id, $networks)) {
                $connection = SocialMediaApi::connect($auth->getData());
                $connection->share($auth->network == 'twitter' ? $short_text : $text, $content);
            }
            if ($auth->network == 'facebook') {
                // Get list of pages, see if any of them are selected.
                $facebook = Facebook::createInstance(json_decode($auth->token, true), true);
                $pages = $facebook->getPages();
                foreach ($pages as $page) {
                    if (in_array($page->id, $facebook_pages)) {
                        $facebook_page = Facebook::createInstance([
                            'token' => $page->access_token,
                            'type' => 'bearer',
                        ], true);
                        $facebook_page->share($text, $content);
                    }
                }
            }
        }
    }

    protected function loadContent() {
        $content = [];
        switch($this->type) {
            case 'blog':
                $blog = BlogPost::loadByID($this->id);
                if ($image = $blog->getHeaderImage()) {
                    $content['images'][] = [
                        'location' => HOME_PATH . $image,
                        'url' => Configuration::get('web_root') . $image
                    ];
                }
                $content['url'] = $blog->getURL();
                break;
        }
        return $content;
    }
}
