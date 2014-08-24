<?php

namespace Lightning\Tools;

use Lightning\View\Field\Time;

class Tracker extends Singleton {

    /**
     * A list of trackers.
     *
     * @var array
     */
    protected static $trackers;

    /**
     * Load the trackers from the DB.
     */
    protected static function loadTrackers(){
        if(!isset(self::$trackers)){
            self::$trackers = Database::getInstance()->selectColumn('tracker', 'tracker_id', array(), 'tracker_name');
        }
    }

    /**
     * @param string $tracker_name
     *   The name of the tracker.
     *
     * @return integer
     *   The numeric tracker ID.
     */
    public static function getTrackerId($tracker_name = ''){
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
     * Insert a tracker item.
     *
     * @param string $tracker_name
     *   The name of the tracker.
     * @param integer $sub_id
     *   A secondary value for the tracker.
     * @param integer $user_id
     *   The user committing the action.
     */
    public static function trackEvent($tracker_name = '', $sub_id = 0, $user_id = -1){
        if($user_id == -1){
            $user_id = ClientUser::getInstance()->id;
        }
        $tracker_id = self::getTrackerId($tracker_name);
        $today = Time::today(); gregoriantojd(date('m'),date('d'),date('Y'));

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
        $criteria = array('tracker_id' => self::getTrackerId($tracker));

        // Filter by date range.
        $start = Time::today() + $start;
        $end = Time::today() + $end;
        $criteria['date'] = array('BETWEEN', $start, $end);

        // Add the sub_id if required.
        if ($sub_id != -1) {
            $criteria['sub_id'] = $sub_id;
        }

        // Add the user ID if required.
        if ($sub_id != -1) {
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
}
