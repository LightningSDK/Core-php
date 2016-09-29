<?php

namespace Lightning\Model;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Data;
use Lightning\Tools\Database;
use Lightning\Tools\Request;
use Lightning\Tools\Security\Encryption;
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

    /**
     * Events
     */
    const SUBSCRIBE = 'Subscribe';
    const REGISTER = 'Register';
    const REGISTER_ERROR = 'Registration Error';

    /**
     * Categories
     */
    const USER = 'User';
    const EMAIL = 'Email';
    const ERROR = 'Error';

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
    public static function loadOrCreateByName($name, $category = '') {
        if ($data = Database::getInstance()->selectRow(static::TABLE, ['category' => $category, 'tracker_name' => $name])) {
            return new static($data);
        } else {
            Database::getInstance()->insert(static::TABLE, ['category' => $category, 'tracker_name' => $name]);
            $data = Database::getInstance()->selectRow(static::TABLE, ['category' => $category, 'tracker_name' => $name]);
            return new static($data);
        }
    }

    /**
     * Register a tracker hit from an encrypted link.
     *
     * @param string $tracker_string
     *   Encrypted data.
     *
     * @return boolean
     *   Whether the link was tracked.
     */
    public static function trackByEncryptedLink($tracker_string) {
        // Decrypt and decode the string with the private key.
        $string = Encryption::aesDecrypt($tracker_string, Configuration::get('tracker.key'));
        if ($data = json_decode($string, true)) {
            // Track the data.
            $tracker = static::loadByID($data['tracker']);
            if (!empty($data['track_once'])) {
                $tracker->trackOnce($data['sub'], $data['user']);
            } else {
                $tracker->track($data['sub'], $data['user']);
            }
            return true;
        }

        return false;
    }

    public static function getAllTrackers() {
        return Database::getInstance()->selectColumn('tracker', 'tracker_name', [], 'tracker_id');
    }

    public function getUniqueSubIDs() {
        return Database::getInstance()->selectColumnQuery([
            'select' => ['sub_id' => ['expression' => 'DISTINCT(sub_id)']],
            'from' => 'tracker_event',
            'where' => ['tracker_id' => $this->id]
        ]);
    }

    /**
     * Save a hit to the tracker into the database.
     *
     * @param $sub_id
     * @param null $user_id
     */
    public function track($sub_id, $user_id = null) {
        if ($user_id === null) {
            $user_id = ClientUser::getInstance()->id;
        }

        $session = Session::getInstance(true, false);
        $session_id = ($session && $session->id > 0) ? $session->id : 0;

        // Insert the event.
        Database::getInstance()->insert(
            'tracker_event',
            [
                'tracker_id' => $this->id,
                'user_id' => $user_id ?: 0,
                'sub_id' => $sub_id ?: 0,
                'date' => Time::today(),
                'time' => time(),
                'session_id' => $session_id,
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
            JS::startup('lightning.tracker.trackOnStartup(' . json_encode($data) . ')');
        }
    }

    /**
     * Track this event, only if the user or session hasn't already tracked it.
     *
     * @param integer $sub_id
     * @param integer $user_id
     */
    public function trackOnce($sub_id, $user_id = null) {
        if ($user_id === null) {
            $user_id = ClientUser::getInstance()->id;
        }

        $criteria = [
            'tracker_id' => $this->id,
            'user_id' => $user_id ?: 0,
            'sub_id' => $sub_id ?: 0,
        ];

        if (empty($user_id)) {
            $session = Session::getInstance(true, false);
            $criteria['session_id'] = ($session && $session->id > 0) ? $session->id : 0;
        }

        if (!Database::getInstance()->check('tracker_event', $criteria)) {
            $this->track($sub_id, $user_id);
        }
    }

    /**
     * Create the HTML for an image that will hit a tracker.
     *
     * @param int $sub_id
     *   The subtracker id.
     * @param $user_id
     *   The user id.
     * @param boolean $track_once
     *   Whether to ignore duplicate requests.
     *
     * @return string
     *   The rendered HTML.
     */
    public function getTrackerImage($sub_id = 0, $user_id = -1, $track_once = true) {
        $url = Configuration::get('web_root') . '/track?t=' . $this->getTrackerLink($sub_id, $user_id, $track_once);
        return '<img src="' . $url . '" border="0" height="0" width="0" />';
    }

    /**
     * Generate an encrypted tracker string.
     *
     * @param integer|string $sub_id
     *   The tracker sub id or * if any is permitted.
     * @param $user_id
     *   The user id.
     * @param boolean $track_once
     *   Whether to ignore duplicate requests.
     *
     * @return string
     *   Then encrypted data.
     */
    public function getTrackerLink($sub_id = 0, $user_id = -1, $track_once = false) {
        // Generate a json encoded string with the tracking data.
        $string = json_encode([
            'tracker' => $this->id,
            'sub' => $sub_id,
            'user' => $user_id > -1 ? $user_id : ClientUser::getInstance()->id,
            'track_once' => $track_once
        ]);

        // Encrypt the string with the public key.
        return urlencode(Encryption::aesEncrypt($string, Configuration::get('tracker.key')));
    }

    public function getUsers($options) {
        return $this->getIDs('user_id', $options);
    }

    public function getSessions($options) {
        return $this->getIDs('session_id', $options);
    }

    protected function getIDs($type, $options) {
        // Set defaults.
        $options += [
            'start' => -30,
            'end' => 0,
        ];

        // Start the criteria with tracker id.
        $criteria = ['tracker_id' => $this->id];

        // Filter by date range.
        $start = Time::today() + $options['start'];
        $end = Time::today() + $options['end'];
        $criteria['date'] = ['BETWEEN', $start, $end];

        // Add the sub_id if required.
        if (!empty($options['sub_id'])) {
            if (is_array($options['sub_id']) && $options['sub_id'][0] != 'NOT IN') {
                $criteria['sub_id'] = ['IN', $options['sub_id']];
            } else {
                $criteria['sub_id'] = $options['sub_id'];
            }
        }

        // Run the query.
        $results = Database::getInstance()->selectColumnQuery([
            'select' => ['type' => ['expression' => 'DISTINCT(' . $type . ')']],
            'from' => 'tracker_event',
            'where' => $criteria,
        ]);

        return $results;
    }

    /**
     * Get an array of data sets.
     *
     * @param array $options
     *   An array containing the history search settings.
     *   - start integer Number of days for the starting range relative to today. (Default -30)
     *   - end integer Number of days realtive to today for ending day. (Default 0)
     *   - sub_id
     *   - user_id integer|array
     *   - session_id integer|array
     *   - unique boolean (Default false)
     *
     * @return array
     *   The result set.
     */
    public function getHistory($options) {
        // Set defaults.
        $options += [
            'start' => -30,
            'end' => 0,
        ];

        // Start the criteria with tracker id.
        $criteria = ['tracker_id' => $this->id];

        // Filter by date range.
        $start = Time::today() + $options['start'];
        $end = Time::today() + $options['end'];
        $criteria['date'] = ['BETWEEN', $start, $end];

        // Add the sub_id if required.
        if (!empty($options['sub_id'])) {
            if (is_array($options['sub_id']) && $options['sub_id'][0] != 'NOT IN') {
                $criteria['sub_id'] = ['IN', $options['sub_id']];
            } else {
                $criteria['sub_id'] = $options['sub_id'];
            }
        }

        $user_criteria = $session_criteria = [];

        // Add the user ID if required.
        if (!empty($options['user_id'])) {
            if (is_array($options['user_id'])) {
                $user_criteria = ['user_id' => ['IN', $options['user_id']]];
            } else {
                $user_criteria = ['user_id' => $options['user_id']];
            }
        }

        // Add the session ID if required.
        if (!empty($options['session_id'])) {
            if (is_array($options['session_id'])) {
                $session_criteria = ['session_id' => ['IN', $options['session_id']]];
            } else {
                $session_criteria = ['session_id' => $options['session_id']];
            }
        }

        if (!empty($user_criteria) && !empty($session_criteria)) {
            $criteria['#OR'] = $user_criteria + $session_criteria;
        } else {
            $criteria += $user_criteria + $session_criteria;
        }

        if (!empty($options['unique'])) {
            $table = ['te' => [
                'from' => 'tracker_event',
                'where' => $criteria,
                'fields' => [
                    'date' => ['expression' => 'MIN(date)'],
                    'user_id',
                    'tracker_id',
                    'sub_id',
                    'session_id',
                ],
                // Group by user id, then by the session id if the user_id is not set.
                'group_by' => ['user_id', ['expression' => 'IF(user_id > 0, 0, session_id)']],
            ]];
            // We don't need to filter the criteria twice, so remove it from the main query condition.
            $criteria = [];
        } else {
            $table = 'tracker_event';
        }

        // Run the query.
        $results = Database::getInstance()->countKeyed(
            $table,
            'date',
            $criteria
        );

        // Make sure all entries are present.
        $return = [];
        for ($i = $start; $i <= $end; $i++) {
            $return[$i] = isset($results[$i]) ? $results[$i] : 0;
        }

        return $return;
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
                foreach (self::$events as $data) {
                    JS::startup('lightning.tracker.trackOnStartup(' . json_encode($data) . ')');
                }

                // Delete the events so they aren't triggered twice.
                unset($session->content->trackerEvents);
                $session->save();
            }
        }
    }
}
