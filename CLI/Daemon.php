<?php

namespace Lightning\CLI;

use DateTime;
use Lightning\Tools\Configuration;
use Lightning\Tools\Logger;

// This is required for the signal handler.
declare(ticks = 1);

class Daemon extends CLI {

    protected $debug = false;

    /**
     * The maximum number of child threads.
     *
     * @var integer
     */
    protected $maxThreads = 5;

    /**
     * Whether to keep the daemon running.
     *
     * @var boolean
     */
    protected $keepAlive = true;

    /**
     * A list of jobs to run.
     *
     * @var array
     */
    protected $jobs = array();

    /**
     * A list of current running threads.
     *
     * @var array
     */
    protected $threads = array();

    /**
     * A queue for items that have died but not been tracked yet.
     *
     * @var array
     */
    protected $signalQueue = array();

    /**
     * The last time we checked for jobs.
     *
     * @var array
     */
    protected $lastCheck;

    /**
     * The timezone offset in seconds.
     *
     * @var integer
     */
    protected $timezoneOffset;

    protected function disableSTDIO($logfile) {
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        $STDIN = fopen(HOME_PATH . '/' . $logfile, 'r');
        $STDOUT = fopen(HOME_PATH . '/' . $logfile, 'wb');
        $STDERR = fopen(HOME_PATH . '/' . $logfile, 'wb');
    }

    /**
     * Initial start command from the terminal.
     */
    public function executeStart() {

        $logfile = Configuration::get('daemon.log');
        Logger::setLog($logfile);

        if (!empty($mypid) && $mypid != posix_getpid()) {
            $this->out('Already running.', true);
            return;
        }

        $this->out('Starting Daemon', true);

        // Get the timezone offset.
        $date = new DateTime();
        $this->timezoneOffset = $date->getOffset();

        $this->maxThreads = Configuration::get('daemon.max_threads');
        $this->jobs = Configuration::get('jobs');

        // If this is not in debug mode, fork to a daemon process.
        $this->disableSTDIO($logfile);
        if (!$this->debug) {
            // Create initial fork.
            $pid = pcntl_fork();
            if ($pid == -1) {
                $this->out('Could not fork.', true);
                return;
            } else if ($pid) {
                // This is the parent thread.
                $status = null;
                pcntl_waitpid($pid, $status, WNOHANG);
                return;
            }

            // This is the child thread.
            pcntl_signal(SIGCHLD, array($this, 'handlerSIGCHLD'));
            pcntl_signal(SIGTERM, array($this, 'handlerSIGTERM'));
        }

        // Loop infinitely, checking for jobs.
        $this->lastCheck = time();
        do {
            $this->checkForJobs();
            sleep(10);

            // TODO: add sigint and memory check.
        } while ($this->keepAlive);
    }

    /**
     * Command to send SIGTERM to the running daemon.
     */
    public function executeStop() {
        if ($pid = $this->getMyPid()) {
            $this->out('Stopping process: ' . $pid, true);
            posix_kill($pid, SIGTERM);
            do {
                sleep(1);
            } while ($this->getMyPid());
            $this->out('Stopped', true);
        } else {
            $this->out('Not running.', true);
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
        $job = $jobs[$job];
        $object = new $job['class']();
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
            return;
        }

        foreach ($this->jobs as &$job) {
            $time = time();
            if (empty($this->lastCheck)) {
                $this->lastCheck = $time;
            }
            $interval_diff = ($time - $job['offset'] + $this->timezoneOffset) % $job['interval'];
            $time_since_last_check = $time - $this->lastCheck;
            if (empty($job['last_start'])) {
                $job['last_start'] = $time;
            }
            $time_since_last_start = $time - $job['last_start'];
            if (
                // Either the time it was supposed to run fell between the last two checks.
                $time_since_last_check > $interval_diff
                // Or the interval has lapsed since the last time it was run.
                || $time_since_last_start > $job['interval']
            ) {
                $this->startJob($job);
            }
        }
        $this->lastCheck = $time;
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
            $job['threads'] = array();
        }

        // Make sure there are no threads
        foreach ($job['threads'] as $pid) {
            if(!file_exists('/proc/' . $pid)) {
                unset($job['threads'][$pid]);
            }
        }

        $remainingThreads = $max_threads - count($job['threads']);
        $remainingThreads = min($remainingThreads, $this->maxThreads - count($this->threads));

        // Check if there are threads available.
        if ($remainingThreads < 1) {
            $this->out('No threads available for: ' . $job['class'], true);
            return;
        } else {
            $job['last_start'] = time();
        }

        // For each remaining thread we have, start one.
        for ($i = 0; $i < $remainingThreads; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                $this->out('Could not fork.', true);
                return;
            } else if ($pid) {
                // This is the parent thread.
                $job['threads'][$pid] = $pid;
                $this->threads[$pid] = $pid;
                if (!empty($this->signalQueue[$pid])) {
                    // This will happen if the item died instantly.
                    $this->handlerSIGCHLD(SIGCHLD, $pid, $this->signalQueue[$pid]);
                    unset($this->signalQueue[$pid]);
                }
                // Continue looping.
            } else {
                // Execute the job.
                $object = new $job['class']();
                $this->out('Starting thread for job: ' . $job['class'], true);
                $object->execute($job);
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
    protected function handlerSIGCHLD($signo, $pid=null, $status=null) {
        // If no pid is provided, Let's wait to figure out which child process ended
        if(!$pid){
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }

        // Get all exited children
        while($pid > 0){
            if($pid && isset($this->threads[$pid])){
                unset($this->threads[$pid]);
            }
            else if($pid){
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
    protected function handlerSIGTERM() {
        $this->keepAlive = false;
    }
}
