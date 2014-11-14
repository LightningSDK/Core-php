<?php

namespace Lightning\Pages;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Tracker;
use Lightning\View\API;
use Lightning\View\Field\Time;

class AdminTracker extends API {
    public function __construct() {
        ClientUser::requireAdmin();
    }

    public function getTrackerStats() {
        $data = array(
            'datasets' => array(),
        );
        $start = Request::get('start', 'int') ?: -30;
        $end = Request::get('end', 'int') ?: 0;
        $sub_id = -1;
        $user_id = -1;
        $tracker = NULL;
        foreach ($_GET['sets'] as $set) {
            $tracker = isset($set['tracker']) ? intval($set['tracker']) : $tracker;
            $sub_id = isset($set['sub_id']) ? intval($set['sub_id']) : $sub_id;
            $user_id = isset($set['user_id']) ? intval($set['user_id']) : $user_id;
            if (empty($tracker)) {
                throw new \Exception('Invalid tracker');
            }
            $data['datasets'][] = array(
                'data' => array_values(Tracker::getHistory($tracker, $start, $end, $sub_id, $user_id)),
                'label' => Tracker::getName($tracker),
            );
        }
        $data['labels'] = array();
        $start += Time::today();
        $end += Time::today();
        for ($i = $start; $i <= $end; $i++) {
            $data['labels'][] = jdtogregorian($i);
        }

        Output::json($data);
    }
}
