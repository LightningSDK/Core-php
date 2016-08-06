<?php

namespace Lightning\Model;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Data;
use Lightning\Tools\Database;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\View\Field\Time;
use Lightning\View\JS;

/**
 * Class TrackerOverridable
 * @package Lightning\Model
 *
 * @property integer tracker_id
 *   The primary key
 * @property string tracker_name
 * @property string category
 * @property string type
 */
class TrackerOverridable extends Object {

    const TABLE = 'tracker';
    const PRIMARY_KEY = 'tracker_id';

    const SUBSCRIBE = 'Subscribe';
    const REGISTER = 'Register';
    const REGISTER_ERROR = 'Registration Error';

    /**
     * A list of events to be dispatched to the UI for google and/or facebook tracking.
     *
     * @var array
     */
    protected static $events = [];

    /**
     * Load a single element by the PK ID.
     *
     * @param string $name
     *   The name of the tracker.
     *
     * @return Tracker
     *   The new object
     */
    public static function loadByName($name) {
        if ($data = Database::getInstance()->selectRow(static::TABLE, ['tracker_name' => $name])) {
            return new static($data);
        } else {
            Database::getInstance()->insert(static::TABLE, ['tracker_name' => $name]);
            $data = Database::getInstance()->selectRow(static::TABLE, ['tracker_name' => $name]);
            return new static($data);
        }
    }

    public function track($sub_id, $user_id = null) {
        if ($user_id === null) {
            $user_id = ClientUser::getInstance()->id;
        }

        // Insert the event.
        Database::getInstance()->insert(
            'tracker_event',
            [
                'tracker_id' => $this->id,
                'user_id' => $user_id ?: 0,
                'sub_id' => $sub_id ?: 0,
                'date' => Time::today(),
                'time' => time(),
                'session_id' => Session::getInstance()->id,
            ]
        );

        if (!Request::isCLI()) {
            $data = [
                'type' => $this->type,
                'category' => $this->category,
                'label' => $this->tracker_name,
            ];

            // Add the events to the JS var
            self::$events[] = $data;
            JS::push('trackerEvents', $data);
        }
    }

    /**
     * Save the current messages and errors to the session.
     */
    public static function storeInSession() {
        // If there is nothing to save, return to prevent session creation.
        if (empty(self::$events)) {
            return;
        }

        $session = Session::getInstance();
        $session->content->trackerEvents = self::$events;
        $session->save();
    }

    /**
     * Load messages and errors from the session.
     */
    public static function loadFromSession() {
        if ($session = Session::getInstance(true, false)) {
            if (!empty($session->content->trackerEvents)) {
                // Load the events.
                self::$events = array_merge(self::$events, json_decode(json_encode($session->content->trackerEvents), true));

                // Add the events to the JS var
                JS::set('trackerEvents', self::$events);

                // Delete the events so they aren't triggered twice.
                unset($session->content->trackerEvents);
                $session->save();
            }
        }
    }
}
