<?php

namespace lightningsdk\core\Pages\Admin;

use lightningsdk\core\Model\SplitTest;
use lightningsdk\core\Model\Tracker;
use lightningsdk\core\Tools\ChartData;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Database;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\Chart\Line;
use lightningsdk\core\View\Field\BasicHTML;
use lightningsdk\core\View\Field\Time;
use lightningsdk\core\View\JS;

class SplitTests extends Line {

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }

    public function get() {
        // Get the list of split tests
        $this->tests = Database::getInstance()->selectColumn('split_test', 'locator', [], 'split_test_id');
        $this->trackers = Database::getInstance()->selectColumn('tracker', 'tracker_name', ['tracker_name' => ['NOT IN', $this->tests]], 'tracker_id');
        JS::set('chart.chart.params.split_test_id', ['source' => 'split_test_id']);
        JS::set('chart.chart.params.tracker_id', ['source' => 'tracker_id']);
        Template::getInstance()->set('chart', $this);
    }

    public function getGetData() {
        // Get the list of split tests
        $start = Request::get('start', Request::TYPE_INT, null, -30);
        $end = Request::get('end', Request::TYPE_INT, null, 0);
        $split_test_id = Request::get('split_test_id', Request::TYPE_INT);
        $tracker_id = Request::get('tracker_id', Request::TYPE_INT);

        $data = new ChartData(Time::today() + $start, Time::today() + $end);

        // Add the tracker as the main data set.
        $result_tracker = Tracker::loadByID($tracker_id);
        $criteria = [
            'start' => $start,
            'end' => $end,
            'unique' => true,
        ];
        $data->addDataSet($result_tracker->getHistory($criteria));

        $split_test = SplitTest::loadByID($split_test_id);
        $split_tracker = Tracker::loadOrCreateByName($split_test->locator, 'Split Test');
        foreach ($split_tracker->getUniqueSubIDs() as $alt_value) {
            $data->addDataSet($split_tracker->getHistory([
                'start' => $start,
                'end' => $end,
                'sub_id' => $alt_value,
                'user_id' => $result_tracker->getUsers($criteria),
                'session_id' => $result_tracker->getSessions($criteria),
                'unique' => true,
            ]));
        }

        $data->setXLabels(array_map('jdtogregorian', range(Time::today() + $start, Time::today() + $end)));

        $data->output();
    }

    public function renderControls() {
        return  BasicHTML::select('split_test_id', $this->tests)
            . BasicHTML::select('tracker_id', $this->trackers)
                 . parent::renderControls();
    }
}
