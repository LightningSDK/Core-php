<?php

namespace lightningsdk\core\Pages;

use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Model\Tracker;
use lightningsdk\core\View\API;
use lightningsdk\core\View\Field\Time;

class AdminTracker extends API {
    public function __construct() {
        ClientUser::requireAdmin();
    }

    public function getTrackerStats() {
        $data = [
            'datasets' => [],
        ];
        $start = Request::get('start', Request::TYPE_INT) ?: -30;
        $end = Request::get('end', Request::TYPE_INT) ?: 0;
        $sub_id = -1;
        $user_id = -1;
        $tracker_id = NULL;
        foreach ($_GET['sets'] as $set) {
            $tracker_id = isset($set['tracker']) ? intval($set['tracker']) : $tracker_id;
            $sub_id = isset($set['sub_id']) ? intval($set['sub_id']) : $sub_id;
            $user_id = isset($set['user_id']) ? intval($set['user_id']) : $user_id;
            if (empty($tracker)) {
                throw new \Exception('Invalid tracker');
            }
            $tracker = Tracker::loadByID($tracker_id);
            $data['datasets'][] = [
                'data' => array_values($tracker->getHistory(['start' => $start, 'end' => $end, 'sub_id' => $sub_id, 'user_id' => $user_id])),
                'label' => $tracker->tracker_name,
            ];
        }
        $data['labels'] = [];
        $start += Time::today();
        $end += Time::today();
        for ($i = $start; $i <= $end; $i++) {
            $data['labels'][] = jdtogregorian($i);
        }

        Output::json($data);
    }
}
