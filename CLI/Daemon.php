<?php

namespace Lightning\CLI;

use DateTime;
use Lightning\Tools\Configuration;
use Lightning\Tools\Logger;
use Lightning\Tools\Database as DatabaseTool;

// This is required for the signal handler.
declare(ticks = 1);

/**
 * Class Daemon
 * @package Lightning\CLI
 *
 * To start
 *   lightning daemon start
 *
 * To test a command:
 *   lightning daemon test <job name>
 *
 * To test in debug mode:
 *   lightning debug daemon test <job name>
 */
class Daemon extends CLI {

    protected $debug = false;

    /**
     * A list of jobs to run.
     *
     * @var array
     */
    protected $jobs = [];

    /**
     * Whether to keep the daemon running.
     *
     * @var boolean
     */
    protected $keepAlive = true;

    /**
     * The last time we checked for jobs.
     *
     * @var double
     */
    protected $lastCheck;

    /**
     * The maximum number of child threads.
     *
     * @var integer
     */
    protected $maxThreads = 5;

    /**
     * A queue for items that have died but not been tracked yet.
     *
     * @var array
     */
    protected $signalQueue = [];

    /**
     * The time the daemon was started.
     *
     * @var double
     */
    protected $startTime;

    /**
     * Whether to output to the STDout.
     *
     * @var boolean
     */
    protected $stdOUT = true;

    /**
     * A list of current running threads.
     *
     * @var array
     */
    protected $threads = [];

    /**
     * The timezone offset in seconds.
     *
     * @var integer
     */
    protected $timezoneOffset;

    /**
     * Initial start command from the terminal.
     */
    public function executeStart() {

        $logfile = Configuration::get('daemon.log');
        Logger::setLog($logfile);

        $mypid = $this->getMyPid();
        if (!empty($mypid) && $mypid != posix_getpid()) {
            $this->out('Already running.', true);
            return;
        }

        $this->out('Starting Daemon');
        $this->startTime = time();

        // Get the timezone offset.
        $date = new DateTime();
        $this->timezoneOffset = $date->getOffset();

        $this->maxThreads = Configuration::get('daemon.max_threads');
        $this->jobs = Configuration::get('jobs');
        foreach ($this->jobs as $name => &$job) {
            if (isset($job['enabled']) && empty($job['enabled'])) {
                // Jobs are enabled by default but can be disabled by setting enabled=false
                unset($this->jobs[$name]);
                continue;
            }
            $job['next_start'] = $this->getNextStartTime($job);
        }

        // If this is not in debug mode, fork to a daemon process.
        if (!$this->debug) {
            $this->stdOUT = false;
            // Create initial fork.
            $pid = pcntl_fork();
            if ($pid == -1) {
                $this->out('Could not fork.');
                return;
            } else if ($pid) {
                // This is the parent thread.
                $status = null;
                pcntl_waitpid($pid, $status, WNOHANG);
                return;
            }

            // This is the child thread.
            pcntl_signal(SIGCHLD, [$this, 'handlerSIGCHLD']);
            pcntl_signal(SIGTERM, [$this, 'handlerSIGTERM']);
        }

        // Before getting into the final loop, disconnect from the database so that
        // new processes don't try to inherit the DB connection.
        // TODO: This should be a caught exception in the DB class itself
        DatabaseTool::getInstance()->disconnect();

        // Loop infinitely, checking for jobs.
        $this->lastCheck = time();
        do {
            $this->checkForJobs();
            sleep(10);
            gc_collect_cycles();
        } while ($this->keepAlive);
    }

    /**
     * Command to send SIGTERM to the running daemon.
     */
    public function executeStop() {
        if ($pid = $this->getMyPid()) {
            $this->out('Stopping process: ' . $pid);
            posix_kill($pid, SIGTERM);
            do {
                sleep(1);
            } while ($this->getMyPid());
            $this->out('Stopped');
        } else {
            $this->out('Not running.');
        }
    }

    /**
     * Test a job without starting the daemon.
     */
    public function executeTest() {
        global $argv;
        foreach ($argv as $i => $arg) {
            if ($arg == 'test') {
                $job = $argv[$i + 1];
            }
        }
        $jobs = Configuration::get('jobs');
        if (empty($job) || empty($jobs[$job])) {
            $this->out('Job not found');
            $this->out('Try: ');
            foreach ($jobs as $key => $config) {
                $this->out($key);
            }
            return;
        }
        $job = $jobs[$job];
        // This would normally be set to the last job run time.
        $job['last_start'] = 0;
        $object = new $job['class']();
        $object->debug = true;
        $object->execute($job);
    }

    /**
     * Restart the daemon.
     */
    public function executeRestart() {
        $this->executeStop();
        $this->executeStart();
    }

    /**
     * Get the PID of the current running daemon process.
     *
     * @return integer
     *   The PID.
     */
    protected function getMyPid() {
        exec('ps -ef | grep ' . realpath(HOME_PATH . '/index.php'), $output);
        $this_pid = posix_getpid();
        foreach ($output as $command) {
            if (preg_match('/daemon (re)?start/', $command)) {
                preg_match('/[0-9]+/', $command, $matches);
                if ($matches[0] != $this_pid) {
                    return $matches[0];
                }
            }
        }
        return null;
    }

    /**
     * Check to see if there are any jobs to fork.
     */
    protected function checkForJobs() {
        if (!$this->hasFreeThreads()) {
            // There are too many threads running already.
            $this->out('No free threads to check for jobs');
            return;
        }

        foreach ($this->jobs as &$job) {
            if ($job['next_start']->getTimeStamp() < time()) {
                $this->startJob($job);
                $job['next_start'] = $this->getNextStartTime($job);
            }
        }
    }

    protected function getNextStartTime($job) {
        $schedule = explode(' ', $job['schedule']);

        $date = new DateTime();
        $date->setTimezone(new \DateTimeZone(Configuration::get('timezone')));

        // Year [5]
        if (!empty($schedule[5]) && $schedule[5] != '*') {
            if (is_numeric($schedule[5])) {
                $this->advanceDate($date, 'year', $schedule[5]);
            }
        }

        // TODO: Day of week [4]

        // Month [3]
        if (!empty($schedule[3]) && $schedule[3] != '*') {
            if (is_numeric($schedule[3])) {
                $this->advanceDate($date, 'month', $schedule[3]);
            }
        }

        // Day of Month [2]
        if (!empty($schedule[2]) && $schedule[2] != '*') {
            if (is_numeric($schedule[2])) {
                $this->advanceDate($date, 'day', $schedule[2]);
            }
        }

        // Hour [1]
        if (!empty($schedule[1]) && $schedule[1] != '*') {
            if (is_numeric($schedule[1])) {
                $this->advanceDate($date, 'hour', $schedule[1]);
            }
        }

        // Minute [0]
        if (!empty($schedule[0]) && $schedule[0] != '*') {
            if (is_numeric($schedule[0])) {
                $this->advanceDate($date, 'minute', $schedule[0]);
            } elseif (preg_match('|[\*0-9]/[0-9]+$|', $schedule[0])) {
                $parts = explode('/', $schedule[0]);
                if ($parts[0] == '*') {
                    do {
                        $this->advanceDate($date, 'minute', $date->format('i') +1);
                    } while ($date->format('i') % $parts[1] > 0);
                }
            }
        }

        $this->out('Job ' . $job['class']::NAME . ' will start next at ' . $date->format('r'));
        return $date;
    }

    /**
     * @param DateTime $date
     * @param $position
     * @param $value
     */
    protected function advanceDate($date, $position, $value) {
        switch ($position) {
            case 'year':
                $date->setTime(0,0,0);
                $date->setDate($value, 1, 1);
                break;
            case 'dow':
                $date->setTime(0,0,0);
                $dayOffset = $value - $date->format('w');
                if ($dayOffset < 0) {
                    $dayOffset += 7;
                }
                $date->setDate($date->format('Y'), $date->format('m'), $date->format('d') + $dayOffset);
                break;
            case 'month':
                $date->setTime(0,0,0);
                $yearOffset = $value < $date->format('m') ? 1 : 0;
                $date->setDate($date->format('Y') + $yearOffset, $value, 1);
                break;
            case 'day':
                $monthOffset = $value < $date->format('d') ? 1 : 0;
                $date->setDate($date->format('Y'), $date->format('m') + $monthOffset, $value);
                break;
            case 'hour':
                if ($value < $date->format('G')) {
                    $date->setDate($date->format('Y'), $date->format('m'), $date->format('d') + 1);
                }
                $date->setTime($value, 0, 0);
                break;
            Case 'minute':
                $hourOffset = $value < $date->format('i') ? 1 : 0;
                $date->setTime($date->format('G') + $hourOffset, $value, 0);
                break;
        }
    }

    /**
     * Attempt to start child processes for a job.
     *
     * @param array $job
     *   The job that is timed to start.
     */
    protected function startJob(&$job) {
        // Make sure there are free threads.
        $max_threads = !empty($job['max_threads']) ? $job['max_threads'] : 1;

        // Make sure 'threads' is an array.
        if (empty($job['threads']) || !is_array($job['threads'])) {
            $job['threads'] = [];
        }

        // Make sure there are no threads
        foreach ($job['threads'] as $pid) {
            if (!file_exists('/proc/' . $pid)) {
                unset($job['threads'][$pid]);
            }
        }

        $remainingThreads = $max_threads - count($job['threads']);
        $remainingThreads = min($remainingThreads, $this->maxThreads - count($this->threads));

        // Check if there are threads available.
        if ($remainingThreads < 1) {
            $this->out('No threads available for: ' . $job['class']);
            return;
        } else {
            $executable_job = $job;
            $job['last_start'] = time();
        }

        // For each remaining thread we have, start one.
        for ($i = 0; $i < $remainingThreads; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                $this->out('Could not fork.');
                return;
            } else if ($pid) {
                // This is the parent thread.
                $executable_job['threads'][$pid] = $pid;
                $this->threads[$pid] = $pid;
                if (!empty($this->signalQueue[$pid])) {
                    // This will happen if the item died instantly.
                    $this->handlerSIGCHLD(SIGCHLD, $pid, $this->signalQueue[$pid]);
                    unset($this->signalQueue[$pid]);
                }
                // Continue looping.
            } else {
                // Execute the job.
                $object = new $executable_job['class']();
                $this->out('Starting thread for job: ' . $executable_job['class']);
                $object->execute($executable_job);
                // Stop the daemon.
                exit;
            }
        }
    }

    /**
     * Check if the daemon has free child threads.
     *
     * @return boolean
     *   Whether the daemon has child threads available.
     */
    protected function hasFreeThreads() {
        foreach ($this->threads as $thread) {
            if (!file_exists('/proc/' . $thread)) {
                // Remove threads that are no longer running.
                unset($this->threads[$thread]);
            }
        }

        return count($this->threads) < $this->maxThreads;
    }

    /**
     * The handler for when a child process dies.
     *
     * @param $signo
     * @param null $pid
     * @param null $status
     * @return bool
     */
    public function handlerSIGCHLD($signo, $pid=null, $status=null) {
        // If no pid is provided, Let's wait to figure out which child process ended
        if (!$pid) {
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }

        // Get all exited children
        while($pid > 0) {
            if ($pid && isset($this->threads[$pid])) {
                unset($this->threads[$pid]);
            }
            else if ($pid) {
                // Job finished before the parent process could record it as launched.
                // Store it to handle when the parent process is ready
                $this->signalQueue[$pid] = $status;
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
        return true;
    }

    /**
     * The handler for receiving a SIGTERM signal.
     */
    public function handlerSIGTERM() {
        $this->keepAlive = false;
    }

    public function out($string, $log = false) {
        if ($this->stdOUT) {
            echo $string . "\n";
        }
        Logger::message($string);
    }
}
