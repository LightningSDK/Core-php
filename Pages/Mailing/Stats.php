<?php

namespace Lightning\Pages\Mailing;

use Lightning\Tools\ChartData;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Model\Tracker;
use Lightning\View\Chart\Line;
use Lightning\View\Field\Time;
use Lightning\View\JS;

class Stats extends Line {

    protected $ajax = true;

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
        $start = Request::get('start', Request::TYPE_INT, null, -30);
        $end = Request::get('end', Request::TYPE_INT, null, 0);
        $message_id = Request::get('message_id', Request::TYPE_INT);

        $email_sent = Tracker::loadOrCreateByName('Email Sent')->getHistory(['start' => $start, 'end' => $end, 'sub_id' => $message_id]);
        $email_bounced = Tracker::loadOrCreateByName('Email Bounced')->getHistory(['start' => $start, 'end' => $end, 'sub_id' => $message_id]);
        $email_opened = Tracker::loadOrCreateByName('Email Opened')->getHistory(['start' => $start, 'end' => $end, 'sub_id' => $message_id]);

        $data = new ChartData(Time::today() + $start, Time::today() + $end);
        $data->addDataSet($email_sent, 'Sent');
        $data->addDataSet($email_bounced, 'Bounced');
        $data->addDataSet($email_opened, 'Opened');

        $data->setXLabels(array_map('jdtogregorian', range(Time::today() + $start, Time::today() + $end)));

        $data->output();
    }
}
