<?php

namespace lightningsdk\core\Tools\SocialDrivers;

use Exception;
use lightningsdk\core\Model\User;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Mongo;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Session\DBSession;
use lightningsdk\core\Tools\Singleton;
use lightningsdk\core\View\JS;

abstract class SocialMediaApiCore extends Singleton implements SocialMediaApiInterface {

    const COUNT_NONE = 0;
    const COUNT_HORIZONTAL = 1;
    const COUNT_VERTICAL = 2;

    /**
     * @var User
     */
    protected $user;
    protected $social_id;

    /**
     * The token for social media access.
     *
     * @var mixed
     */
    protected $token;

    protected $profile;
    protected $id_profile;
    protected $authorize = false;

    /**
     * Create a connection based on a row from the social_auth table.
     *
     * @param array $social_auth
     *   The network connection data.
     *
     * @return SocialMediaApi
     */
    public static function connect($social_auth) {
        switch ($social_auth['network']) {
            case 'facebook':
                return Facebook::createInstance(json_decode($social_auth['token'], true), true);
            case 'twitter':
                return Twitter::createInstance(json_decode($social_auth['token'], true), true);
            case 'google':
                return Google::createInstance($social_auth['token'], true);
        }
    }

    /**
     * An overridable function of what to do after a user signs in to the network.
     */
    public function afterLogin() {
    }

    public function setupUser() {
        // Load the user profile.
        if (!$this->authorize || $userData = $this->getProfile()) {
            // Create a user.
            $user_settings = $this->getLightningUserData();
            if ($ref = ClientUser::getReferrer()) {
                // Set the referrer.
                $user_settings['referrer'] = $ref;
            }
            $this->user = User::addUser($this->getLightningEmail(), $user_settings, $user_settings);

            // This requires mongodb.
            if (Configuration::get('social.store_images', false)) {
                $this->setUserImage();
            }
        } else {
            throw new Exception('Failed to load user data.');
        }
    }

    public function isLoggedIn() {
        try {
            if ($this->getSocialId()) {
                return true;
            }
        } catch (Exception $e) {

        }

        return false;
    }

    protected function getLightningEmail() {
        return $this->getSocialId() . '@@' . static::EMAIL_SUFFIX;
    }

    public function setUserImage() {
        // Download and save the user's icon.
        $image_data = $this->myImageData();

        // Add the image to the database.
        $mongo = Mongo::getConnection('concurrency', 'user_images');
        $mongo->update(
            ['user_id' => $this->user->id],
            [
                'user_id' => $this->user->id,
                'image' => base64_encode($image_data),
                'format' => 'jpg',
            ],
            [
                'upsert' => true,
            ]
        );
    }

    public function activateUser() {
        // Log the user in and create a session.
        $session = DBSession::create($this->user->id, true);
        DBSession::setInstance($session);
        $this->storeSessionData();
    }

    public function getProfile() {
        $this->loadProfile();
        return $this->profile;
    }

    public static function getRequestToken() {
        if ($token = Request::post('id-token')) {
            return [
                'auth' => false,
                'token' => $token,
            ];
        } elseif ($token = Request::post('auth-token')) {
            return [
                'auth' => true,
                'type' => 'short',
                'token' => $token,
            ];
        }
        return null;
    }

    public function getNetwork() {
        return $this->network;
    }

    public static function initJS($suffix) {
        $suffix = str_replace('.com', '', $suffix);
        if ($suffix == 'google') {
            JS::set('social.google.client_id', Configuration::get('social.google.client_id'));
        }
        JS::startup('lightning.social.initLogout("' . $suffix . '")');
    }
}
