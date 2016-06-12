<?php

namespace Lightning\Tools;

use Lightning\Tools\Security\Encryption;
use Lightning\View\Field\Time;

class Tracker extends Singleton {

    /**
     * A list of trackers.
     *
     * @var array
     */
    protected static $trackers;

    /**
     * A reverse index to convert IDs to names.
     *
     * @var array
     */
    protected static $reverse;

    /**
     * Load the trackers from the DB.
     */
    protected static function loadTrackers() {
        if (!isset(self::$trackers)) {
            self::$trackers = Database::getInstance()->selectColumn('tracker', 'tracker_id', [], 'tracker_name');
        }
    }

    /**
     * Create a select field with tracker options.
     *
     * @param string $name
     *   The name and ID of the field.
     * @param string $default
     *   The default selected value.
     * @param string $other
     *   Additional HTML to add to the outer select tag.
     *
     * @return string
     *   The rendered HTML.
     */
    public static function options($name, $default = '', $other = '') {
        self::loadTrackers();
        $output = '<select name="' . $name . '" id="' . $name . '" ' . $other . '>';
        foreach (self::$trackers as $name => $id) {
            $output .= '<option value="' . $id . '">' . $name . '</option>';
        }
        $output .= '</select>';
        return $output;
    }

    /**
     * @param string $tracker_name
     *   The name of the tracker.
     *
     * @return integer
     *   The numeric tracker ID.
     */
    public static function getTrackerId($tracker_name) {
        // Make sure the trackers are loaded.
        self::loadTrackers();

        // The tracker does not exist.
        if (empty(self::$trackers[$tracker_name])) {
            // Create a new tracker.
            self::$trackers[$tracker_name] = Database::getInstance()->insert('tracker', ['tracker_name' => $tracker_name]);
        }

        return self::$trackers[$tracker_name];
    }

    /**
     * Convert a tracker ID into it's name.
     *
     * @param integer $tracker_id
     *   The tracker ID.
     *
     * @return string
     *   The tracker name.
     */
    public static function getName($tracker_id) {
        // Make sure the trackers are loaded.
        self::loadTrackers();

        if (empty(self::$reverse)) {
            self::$reverse = array_flip(self::$trackers);
        }

        return self::$reverse[$tracker_id];
    }

    /**
     * Insert a tracker item.
     *
     * @param string $tracker_name
     *   The name of the tracker.
     * @param integer $sub_id
     *   A secondary value for the tracker.
     * @param integer $user_id
     *   The user committing the action.
     */
    public static function trackEvent($tracker_name, $sub_id = 0, $user_id = -1) {
        $tracker_id = self::getTrackerId($tracker_name);
        self::trackEventID($tracker_id, $sub_id, $user_id);
    }

    /**
     * Track an event with a known tracker ID.
     *
     * @param integer $tracker_id
     *   The ID of the tracker.
     * @param integer $sub_id
     *   The tracker sub id.
     * @param $user_id
     *   The user id.
     */
    public static function trackEventID($tracker_id, $sub_id = 0, $user_id = -1) {
        if ($user_id == -1 || $user_id === false) {
            $user_id = ClientUser::getInstance()->id;
        }

        $today = Time::today();

        // Insert the event.
        Database::getInstance()->insert(
            'tracker_event',
            [
                'tracker_id' => $tracker_id,
                'user_id' => $user_id ?: 0,
                'sub_id' => $sub_id ?: 0,
                'date' => $today,
                'time' => time(),
                'session_id' => Session::getInstance()->id,
            ]
        );
    }

    /**
     * Generate an encrypted tracker string.
     *
     * @param string $tracker_name
     *   The tracker name.
     * @param integer|string $sub_id
     *   The tracker sub id or * if any is permitted.
     * @param $user_id
     *   The user id.
     *
     * @return string
     *   Then encrypted data.
     */
    public static function getTrackerLink($tracker_name, $sub_id = 0, $user_id = -1) {
        // Generate a json encoded string with the tracking data.
        $string = json_encode(array(
            'tracker' => self::getTrackerId($tracker_name),
            'sub' => $sub_id,
            'user' => $user_id > -1 ? $user_id : ClientUser::getInstance()->id,
        ));

        // Encrypt the string with the public key.
        return urlencode(Encryption::aesEncrypt($string, Configuration::get('tracker.key')));
    }

    /**
     * Create the HTML for an image that will hit a tracker.
     *
     * @param string $tracker_name
     *   The name of a tracker.
     * @param int $sub_id
     *   The subtracker id.
     * @param $user_id
     *   The user id.
     *
     * @return string
     *   The rendered HTML.
     */
    public static function getTrackerImage($tracker_name, $sub_id = 0, $user_id = -1) {
        $url = Configuration::get('web_root') . '/track?t=' . self::getTrackerLink($tracker_name, $sub_id, $user_id);
        return '<img src="' . $url . '" border="0" height="0" width="0" />';
    }

    /**
     * Add a tracker hit from an encrypted link.
     *
     * @param string $tracker_string
     *   Encrypted data.
     *
     * @return boolean
     *   Whether the link was tracked.
     */
    public static function trackLink($tracker_string) {
        // Decrypt and decode the string with the private key.
        $string = Encryption::aesDecrypt($tracker_string, Configuration::get('tracker.key'));
        if ($data = json_decode($string, true)) {
            // Track the data.
            self::trackEventID($data['tracker'], $data['sub'], $data['user']);
            return true;
        }

        return false;
    }

    /**
     * Get an array of data sets.
     *
     * @param integer $tracker_id
     *   The tracker id.
     * @param integer $start
     *   How many days back to start.
     * @param integer $end
     *   How many days ago to end.
     * @param integer|array $sub_id
     *   The tracker sub_id. -1 to include all.
     * @param integer $user_id
     *   The tracker user. -1 to include all.
     * @param boolean $unique_users
     *   Whether to conly count the number of unique users.
     *
     * @return array
     *   The result set.
     */
    public static function getHistory($tracker_id, $start = -30, $end = 0, $sub_id = -1, $user_id = -1, $unique_users = false) {
        // Start the criteria with tracker id.
        $criteria = array('tracker_id' => $tracker_id);

        // Filter by date range.
        $start = Time::today() + $start;
        $end = Time::today() + $end;
        $criteria['date'] = array('BETWEEN', $start, $end);

        // Add the sub_id if required.
        if ($sub_id != -1) {
            if (is_array($sub_id)) {
                if (empty($sub_id)) {
                    $criteria['sub_id'] = null;
                } elseif ($sub_id[0] == 'NOT IN') {
                    $criteria['sub_id'] = $sub_id;
                } else {
                    $criteria['sub_id'] = array('IN', $sub_id);
                }
            } else {
                $criteria['sub_id'] = $sub_id;
            }
        }

        // Add the user ID if required.
        if ($user_id != -1) {
            $criteria['user_id'] = $user_id;
        }

        if ($unique_users) {
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

    public static function getHistoryAllSubIDs($tracker, $start = -30, $end = 0, $user_id = -1) {
        // Start the criteria with tracker id.
        if (is_array($tracker)) {
            $criteria = array('tracker_id' => array('IN', $tracker));
        } else {
            $criteria = array('tracker_id' => $tracker);
        }

        // Filter by date range.
        $start = Time::today() + $start;
        $end = Time::today() + $end;
        $criteria['date'] = array('BETWEEN', $start, $end);

        // Add the user ID if required.
        if ($user_id != -1) {
            $criteria['user_id'] = $user_id;
        }

        // Run the query.
        $results = Database::getInstance()->select(
            'tracker_event',
            $criteria,
            array(
                'y' => array('expression' => 'COUNT(*)'),
                'x' => 'date',
                'set' => 'sub_id',
            ),
            'GROUP BY date, sub_id'
        );

        $data = new ChartData($start, $end);
        $data->createDataSets($results);
        return $data->getData();
    }
}
