<?php

namespace lightningsdk\core\Model;

use DateTime;
use lightningsdk\core\Tools\Database;

class CalendarCore {

    protected $month;
    protected $year;
    /**
     * @var DateTime
     */
    protected $monthStart;

    protected $events;

    public function __construct($month = null, $year = null) {
        $this->month = $month ?: date('m');
        $this->year = $year ?: date('Y');
        $this->monthStart = DateTime::createFromFormat('m-d-Y', $this->month . '-1-' . $this->year);
        // Load all events for this month.
        $this->loadEvents();
    }

    protected function loadEvents($force = false) {
        if (!isset($this->events) || $force) {
            $month_start = gregoriantojd($this->month, 1, $this->year);
            $month_end = gregoriantojd($this->month, $this->monthStart->format('t'), $this->year);
            $events = $this->queryEvents($month_start, $month_end);

            // Expand the events out into each day.
            $this->events = [];
            foreach ($events as $e) {
                $event_start = max($e['start_date'] - $month_start + 1, 1);
                $event_end = min(max($e['start_date'], $e['end_date']) - $month_start + 1, $this->monthStart->format('t'));
                for ($i = $event_start; $i <= $event_end; $i++) {
                    $this->events[$i][] = $e;
                }
            }
        }
    }

    protected function queryEvents($month_start, $month_end) {
        return Database::getInstance()->selectAllQuery([
            'from' => 'calendar',
            'where' => [
                'start_date' => ['BETWEEN', $month_start, $month_end],
                'end_date' => ['BETWEEN', $month_start, $month_end],
            ],
            'order_by' => [
                'start_date' => 'ASC',
                'start_time' => 'ASC',
                'end_date' => 'ASC',
                'end_time' => 'ASC',
            ]
        ]);
    }

    public function render() {
        return $this->renderTable() . $this->renderList();
    }

    public function renderTable() {
        // Render Header
        $output = '<div class="weekday-row clearfix">
            <div>Sunday</div>
            <div>Monday</div>
            <div>Tuesday</div>
            <div>Wednesday</div>
            <div>Thursday</div>
            <div>Friday</div>
            <div>Saturday</div>
        </div>';
        $days_rendered = 0;
        $days_in_month = $this->monthStart->format('t');
        $starting_day_of_week = $this->monthStart->format('w');
        while ($days_rendered < $days_in_month) {
            // Render a row.
            $output .= '<div class="date-row clearfix" data-equalizer>';
            $max_in_row = 7;
            if ($days_rendered == 0) {
                // Render blank boxes in first row.
                $output .= str_repeat('<div class="box" data-equalizer-watch></div>', $starting_day_of_week);
                $max_in_row = 7 - $starting_day_of_week;
            }

            $last_day = min($days_rendered + 7, $days_in_month, $days_rendered + $max_in_row);
            for ($d = $days_rendered + 1; $d <= $last_day; $d++) {
                $output .= '<div class="box" data-equalizer-watch><span class="date">' . $d . '</span>';
                // Add events.
                if (!empty($this->events[$d])) {
                    foreach ($this->events[$d] as $e) {
                        $output .= '<div class="event">' . $e['title'] . '</div>';
                    }
                }
                $output .= '</div>';
            }
            $days_rendered = $last_day;

            if ($last_day == $days_in_month) {
                // Render blank boxes in last row.
                $blank_days = 7 - ($starting_day_of_week + $days_in_month) % 7;
                $output .= str_repeat('<div class="box" data-equalizer-watch></div>', $blank_days);
            }

            $output .= '</div>';
        }

        return $output;
    }

    public function renderList() {
        $output = '';
        $event_date = clone $this->monthStart;
        for ($d = 1; $d <= $this->monthStart->format('t'); $d++) {
            $output .= '<div class="box clearfix"><span class="date">' . $d . '<i>' . $event_date->format('D') . '</i></span>';
            if (!empty($this->events[$d])) {
                $output .= '<div class="events">';
                foreach ($this->events[$d] as $e) {
                    $output .= '<div>' . $e['title'] . '</div>';
                }
                $output .= '</div>';
            }
            $event_date->modify('+1 day');
            $output .= '</div>';
        }

        return $output;
    }
}
