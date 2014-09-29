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
    protected static function loadTrackers(){
        if(!isset(self::$trackers)){
            self::$trackers = Database::getInstance()->selectColumn('tracker', 'tracker_id', array(), 'tracker_name');
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
    public static function options($name, $default = '', $other = ''){
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
    public static function getTrackerId($tracker_name){
        // Make sure the trackers are loaded.
        self::loadTrackers();

        // The tracker does not exist.
        if (empty(self::$trackers[$tracker_name])) {
            // Create a new tracker.
            self::$trackers[$tracker_name] = Database::getInstance()->insert('tracker', array('tracker_name' => $tracker_name));
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
    public static function trackEvent($tracker_name, $sub_id = 0, $user_id = -1){
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
        if($user_id == -1){
            $user_id = ClientUser::getInstance()->id;
        }

        $today = Time::today();

        // Insert the event.
        Database::getInstance()->insert(
            'tracker_event',
            array(
                'tracker_id' => $tracker_id,
                'user_id' => $user_id,
                'sub_id' => $sub_id,
                'date' => $today
            )
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
     */
    public static function trackLink($tracker_string) {
        // Decrypt and decode the string with the private key.
        $string = Encryption::aesDecrypt($tracker_string, Configuration::get('tracker.key'));
        $data = json_decode($string, true);

        // Track the data.
        self::trackEventID($data['tracker'], $data['sub'], $data['user']);
    }

    /**
     * Get an array of data sets.
     *
     * @param string $tracker
     *   The name of the tracker.
     * @param integer $start
     *   How many days back to start.
     * @param integer $end
     *   How many days ago to end.
     * @param integer $sub_id
     *   The tracker sub_id. -1 to include all.
     * @param integer $user_id
     *   The tracker user. -1 to include all.
     *
     * @return array
     *   The result set.
     */
    public static function getHistory($tracker, $start = -30, $end = 0, $sub_id = -1, $user_id = -1) {
        // Start the criteria with tracker id.
        $criteria = array('tracker_id' => $tracker);

        // Filter by date range.
        $start = Time::today() + $start;
        $end = Time::today() + $end;
        $criteria['date'] = array('BETWEEN', $start, $end);

        // Add the sub_id if required.
        if ($sub_id != -1) {
            $criteria['sub_id'] = $sub_id;
        }

        // Add the user ID if required.
        if ($user_id != -1) {
            $criteria['user_id'] = $user_id;
        }

        // Run the query.
        $results = Database::getInstance()->selectColumn(
            'tracker_event',
            array('count' => 'COUNT(*)'),
            $criteria,
            'date',
            'GROUP BY date'
        );

        // Make sure all entries are present.
        $return = array();
        for ($i = $start; $i <= $end; $i++) {
            $return[$i] = isset($results[$i]) ? $results[$i] : 0;
        }

        return $return;
    }

    public static function getHistoryAllSubIDs($tracker, $start = -30, $end = 0, $user_id = -1) {
        // Start the criteria with tracker id.
        $criteria = array('tracker_id' => $tracker);

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
                'count' => array('expression' => 'COUNT(*)'),
                'date', 'sub_id'
            ),
            'GROUP BY date, sub_id'
        );

        $output = array();
        foreach ($results as $row) {
            if (!isset($output[$row['sub_id']])) {
                $output[$row['sub_id']] = array_fill($start, $end - $start + 1, 0);
            }
            $output[$row['sub_id']][$row['date']] = $row['count'];
        }

        return $output;
    }
}
