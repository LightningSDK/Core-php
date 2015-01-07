<?php

namespace Lightning\Pages\Mailing;

use Lightning\Tools\ChartData;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Tracker;
use Lightning\View\Chart\Line;
use Lightning\View\Field\Time;
use Lightning\View\JS;

class Stats extends Line {

    protected function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    public function get() {
        $message_id = Request::get('message_id', 'int');
        if (empty($message_id)) {
            Output::error('Message Not Found');
        }
        JS::set('chart.' . $this->id . '.params.message_id', ['value' => $message_id]);
        parent::get();
    }

    public function getGetData() {
        $start = Request::get('start', 'int', null, -30);
        $end = Request::get('end', 'int', null, 0);
        $message_id = Request::get('message_id', 'int');

        $tracker = new Tracker();
        $email_sent = $tracker->getHistory(Tracker::getTrackerId('Email Sent'), $start, $end, $message_id);
        $email_bounced = $tracker->getHistory(Tracker::getTrackerId('Email Bounced'), $start, $end, $message_id);
        $email_opened = $tracker->getHistory(Tracker::getTrackerId('Email Opened'), $start, $end, $message_id);

        $data = new ChartData(Time::today() + $start, Time::today() + $end);
        $data->addDataSet($email_sent, 'Sent');
        $data->addDataSet($email_bounced, 'Bounced');
        $data->addDataSet($email_opened, 'Opened');

        $data->setXLabels(array_map('jdtogregorian', range(Time::today() + $start, Time::today() + $end)));

        $data->output();
    }
}