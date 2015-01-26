<?php

namespace Lightning\Jobs;

use DateTime;
use Lightning\Tools\Database;
use Lightning\Tools\Mailer as MailerTool;
use Lightning\View\Field\Time;

class Mailer extends Job {
    public function execute($job) {
        $mailer = new MailerTool();

        $date = new DateTime();
        $timezone_offset = $date->getOffset();

        $time = time() + $timezone_offset;

        // Load all messages that should be sent on a specific date.
        $messages = Database::getInstance()->selectColumn(
            'message', 'message_id',
            ['send_date' => ['BETWEEN', $time - $job['interval'], $time + $job['interval']]]
        );
        foreach ($messages as $message_id) {
            $start_time = time();
            $this->out("Sending message {$message_id}");
            $count = $mailer->sendBulk($message_id, false, true);
            $time = Time::formatLength(time() - $start_time);
            $this->out("Message {$message_id} sent to {$count} users in {$time}");
        }
    }
}
