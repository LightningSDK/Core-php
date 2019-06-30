<?php

namespace Lightning\Jobs;

use DateTime;
use Lightning\Model\Message;
use Lightning\Tools\Mailer as MailerTool;
use Lightning\View\Field\Time;

/**
 * Class Mailer
 * @package Lightning\Jobs
 *
 * Run with lightning daemon test auto-mailer count
 */
class Mailer extends Job {

    const NAME = 'Mailer';

    /**
     * The number of days to pick up messages queued for a specific time
     * if the queue was not delivering. It is set to 5 days.
     */
    const MIN_PICKUP = 5 * 24 * 3600;

    /**
     * @var MailerTool
     */
    protected $mailer;
    protected $count = false;

    public function execute($job) {
        $this->out('Sending auto mailers');

        $this->job = $job;
        $this->mailer = new MailerTool();

        global $argv;
        if (in_array('count', $argv)) {
            $this->count = true;
        }

        $this->sendTimeSpecific();
        $this->sendCriteriaBased();

        $this->out('Auto mailers complete');
    }

    /**
     * Send messages set to go at a specific date.
     */
    protected function sendTimeSpecific() {
        $date = new DateTime();
        $time = $date->getTimestamp();
        $start = !empty($this->job['last_start']) ? $this->job['last_start'] + $date->getOffset() : $time - self::MIN_PICKUP;
        $end = $time + $date->getOffset();

        // Load all messages that should be sent on a specific date.
        $messages = Message::loadAll([
            'send_date' => ['BETWEEN', $start, $end]
        ]);

        foreach ($messages as $message) {
            $this->sendMessage($message);
        }
    }

    protected function sendCriteriaBased() {
        // Load all messages with criteria attached.
        $messages = Message::loadByQuery([
            'select' => ['message.*'],
            'from' => 'message_message_criteria',
            'join' => [
                [
                    'join' => 'message',
                    'using' => 'message_id',
                ]
            ],
            'group_by' => 'message_id'
        ]);

        foreach ($messages as $message) {
            $this->sendMessage($message);
        }
    }

    protected function sendMessage($message) {
        $start_time = time();
        $this->out("Sending message {$message->id}");
        try {
            if ($this->count) {
                $this->out("Message {$message->id} will be sent to {$message->getUsersCount()} users.");
            } else {
                $count = $this->mailer->sendBulk($message->id, false, true);
                $time = Time::formatLength(time() - $start_time);
                $this->out("Message {$message->id} sent to {$count} users in {$time}");
            }
        } catch (\Exception $e) {
            $this->out('FAILED: ' . $e->getMessage());
        }
    }
}
