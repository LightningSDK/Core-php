<?php

namespace Overridable\Lightning\Tools\SocialDrivers;

use Exception;
use Lightning\Model\User;
use Lightning\Tools\Configuration;
use Lightning\Tools\Mongo;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\Tools\Singleton;
use Lightning\Tools\SocialDrivers\SocialMediaApiInterface;
use Lightning\View\JS;

abstract class SocialMediaApi extends Singleton implements SocialMediaApiInterface {

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

    public function afterLogin() {
    }

    public function setupUser() {
        // Load the user profile.
        if (!$this->authorize || $userData = $this->getProfile()) {
            // Create a user.
            $user_settings = $this->getLightningUserData();
            $this->user = User::addUser($this->getLightningEmail(), $user_settings, $user_settings);

            $this->setUserImage();
        } else {
            throw new Exception('Failed to load user data.');
        }
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
        $session = Session::create($this->user->id, true);
        Session::setInstance($session);
        $this->storeSessionData();
    }

    public function getProfile() {
        $this->loadProfile();
        return $this->profile;
    }

    public static function getToken() {
        if ($token = Request::post('id-token')) {
            return [
                'auth' => false,
                'token' => $token,
            ];
        } elseif ($token = Request::post('auth-token')) {
            return [
                'auth' => true,
                'token' => $token,
            ];
        }
        return null;
    }

    public static function initJS($suffix) {
        $suffix = str_replace('.com', '', $suffix);
        if ($suffix == 'google') {
            JS::set('social.google.client_id', Configuration::get('social.google.client_id'));
        }
        JS::startup('lightning.social.initLogout("' . $suffix . '")');
    }
}
