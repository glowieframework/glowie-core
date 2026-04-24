<?php

namespace Glowie\Core\Queue;

use Config;
use DateTime;
use Glowie\Core\Database\Kraken;
use Glowie\Core\Exception\QueueException;
use Glowie\Core\CLI\Firefly;
use Glowie\Core\Error\Handler;

/**
 * Queue runner for Glowie application.
 * @category Queue
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class Queue
{

    /**
     * Delay time for **1 minute**.
     * @var int
     */
    public const DELAY_MINUTE = 60;

    /**
     * Delay time for **1 hour**.
     * @var int
     */
    public const DELAY_HOUR = 3600;

    /**
     * Delay time for **1 day**.
     * @var int
     */
    public const DELAY_DAY = 86400;

    /**
     * Current database instance.
     * @var Kraken
     */
    private static $db;

    /**
     * Last dispatched job ID.
     * @var int|null
     */
    private static $lastJobId = null;

    /**
     * Adds a job to the queue.
     * @param string $job A job classname with namespace. You can use `JobName::class` to get this property correctly.
     * @param mixed $data (Optional) Data to pass to the job.
     * @param string $queue (Optional) Queue name to add this job to.
     * @param mixed $delay Delay in seconds to run this job. You can also use a DateTime instance.
     * @return Queue Returns the current instance for nested Calls.
     */
    public static function add(string $job, $data = null, string $queue = 'default', $delay = null)
    {
        // Parses the delay time
        if ($delay instanceof DateTime) {
            $delay = $delay->format('Y-m-d H:i:s');
        } else if (!is_null($delay)) {
            $delay = date('Y-m-d H:i:s', time() + $delay);
        }

        // Adds the job to to queue
        $db = self::getConnection();
        $result = $db->insert([
            'job' => $job,
            'queue' => $queue,
            'data' => is_null($data) ? null : serialize($data),
            'added_at' => date('Y-m-d H:i:s'),
            'delayed_to' => $delay
        ]);

        // Gets the last inserted job id
        if (!$result) throw new QueueException('Failed to add job "' . $job . '" to the queue "' . $queue . '"');
        self::$lastJobId = $db->lastInsertId();
        return new static;
    }

    /**
     * Sets the queue of the last added job.
     * @param string $name Queue name to set the job to.
     * @return Queue Returns the current instance for nested Calls.
     */
    public function on(string $name)
    {
        if (!self::$lastJobId) throw new QueueException('There is no job to be modified');
        $db = self::getConnection();
        $db->where('id', self::$lastJobId)->update([
            'queue' => $name
        ]);
        return $this;
    }

    /**
     * Sets the delay of the last added job.
     * @param mixed $delay Delay in seconds to run this job. You can also use a DateTime instance.
     * @return Queue Returns the current instance for nested Calls.
     */
    public function delay($delay)
    {
        // Parses the delay time
        if (!self::$lastJobId) throw new QueueException('There is no job to be modified');

        if ($delay instanceof DateTime) {
            $delay = $delay->format('Y-m-d H:i:s');
        } else if (!is_null($delay)) {
            $delay = date('Y-m-d H:i:s', time() + $delay);
        }

        // Updates the job
        $db = self::getConnection();
        $db->where('id', self::$lastJobId)->update([
            'delayed_to' => $delay
        ]);

        return $this;
    }

    /**
     * Processes the pending jobs in the queue.
     * @param string $queue (Optional) Queue name.
     * @param bool $bail (Optional) Stop queue processing on job fail.
     * @param bool $verbose (Optional) Print status messages during execution.
     * @param bool $watcher (Optional) Run queue in watcher mode (CLI only).
     */
    public static function process(string $queue = 'all', bool $bail = false, bool $verbose = false, bool $watcher = false)
    {
        // Delete expired jobs
        self::prune();

        // Get pending jobs from the queue
        $db = self::getConnection();
        $jobs = $db->when($queue !== 'all', fn(Kraken $q) => $q->where('queue', $queue))
            ->whereNull('ran_at')
            ->where('attempts', '<', Config::get('queue.max_attempts', 3))
            ->where(function (Kraken $query) {
                $query->whereNull('delayed_to');
                $query->orWhere('delayed_to', '<=', date('Y-m-d H:i:s'));
            })
            ->orderBy('id')
            ->fetchAll();

        if (count($jobs) === 0) {
            if ($verbose && !$watcher) Firefly::print(Firefly::color('There are no pending jobs in this queue.', 'yellow'));
            return;
        }

        // Runs each job
        $success = 0;
        $failed = 0;

        foreach ($jobs as $jobRow) {
            try {
                // Stores start time
                $time = microtime(true);
                if ($verbose) Firefly::print(Firefly::color('[' . date('Y-m-d H:i:s') . '] Running ' . $jobRow->job . ' job from "' . $jobRow->queue . '" queue...', 'blue'));

                // Create job instance and runs it
                $job = $jobRow->job;
                if (!class_exists($job)) throw new QueueException('"' . $job . '" was not found');
                $job = new $job(is_null($jobRow->data) ? null : unserialize($jobRow->data));
                $job->run();

                // Saves the state to the database on success
                $date = date('Y-m-d H:i:s');
                $db->where('id', $jobRow->id)->update(['ran_at' => $date, 'attempts' => $jobRow->attempts + 1]);

                // Prints result if in verbose mode
                $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
                if ($verbose) Firefly::print(Firefly::color('[' . $date . ']' . $jobRow->job . ' job ran successfully in ' . $time . '!', 'green'));
                $success++;
            } catch (\Throwable $th) {
                // Gets the previous errors, if any
                $errors = [];
                if (!empty($jobRow->errors)) $errors = explode("\n\n", $jobRow->errors);

                // Get the error as string
                $attempt = $jobRow->attempts + 1;
                $date = date('Y-m-d H:i:s');

                $errorString = "#{$attempt} [{$date}] {$th->getMessage()} at file {$th->getFile()}:{$th->getLine()}\n{$th->getTraceAsString()}";
                $errors[] = $errorString;

                // Sets the attempts and errors
                $db->where('id', $jobRow->id)->update([
                    'attempts' => $attempt,
                    'errors' => !empty($errors) ? implode("\n\n", $errors) : null
                ]);

                // Calls the job fail method if exists
                if (is_callable([$job, 'fail'])) $job->fail($th);

                // Checks to stop execution of queue on fail
                if ($bail) throw new QueueException($th->getMessage(), $th->getCode(), $th);

                // Log error
                if ($verbose) Firefly::print(Firefly::color('[' . $date . ']' . $jobRow->job . ' failed! Skipping...', 'red'));
                Handler::log($errorString . "\n\n");
                $failed++;
            }
        }

        // Finish message
        if ($verbose && !$watcher) {
            Firefly::print('');
            Firefly::print(Firefly::color('Queue finished! ' . $success . ' jobs success, ' . $failed . ' failed.', 'yellow'));
        }
    }

    /**
     * Clears the queue.
     * @param string $queue (Optional) Queue name.
     * @param bool $success (Optional) Clear successful jobs.
     * @param bool $pending (Optional) Clear pending jobs.
     * @param bool $failed (Optional) Clear failed jobs.
     * @return bool Returns true on success, false on fail.
     */
    public static function clear(string $queue = 'default', bool $success = true, bool $pending = true, bool $failed = false)
    {
        // Connects to the database
        $db = self::getConnection();

        // Sets the queue name
        $db->where('queue', $queue);

        // Clear the whole queue
        if ($success && $failed && $pending) return $db->whereNotNull('id')->delete();

        // Clear successful jobs
        if ($success) $db->whereNotNull('ran_at')->delete();

        // Clear failed jobs
        if ($failed) $db->whereNull('ran_at')->where('attempts', '>=', Config::get('queue.max_attempts', 3))->delete();

        // Clear pending jobs
        if ($pending) $db->whereNull('ran_at')->delete();

        return true;
    }

    /**
     * Gets the last dispatched job ID in the current process.
     * @return int|null Returns the last dispatched job ID, or null if there is none.
     */
    public static function getLastId()
    {
        return self::$lastJobId;
    }

    /**
     * Moves an existing job to another queue.
     * @param int $job Job ID from the database.
     * @param string $queue Target queue name to move the job to.
     * @return bool Returns true on success, false on fail.
     */
    public static function move(int $job, string $queue)
    {
        $db = self::getConnection();
        $job = $db->where('id', $job)->fetchRow();
        if (empty($job)) throw new QueueException('Job ID ' . $job . ' does not exist');
        return $db->where('id', $job)->update(['queue' => $queue]);
    }

    /**
     * Deletes an existing job from the queue.
     * @param int $job Job ID from the database.
     * @return bool Returns true on success, false on fail.
     */
    public static function delete(int $job)
    {
        $db = self::getConnection();
        $job = $db->where('id', $job)->fetchRow();
        if (empty($job)) throw new QueueException('Job ID ' . $job . ' does not exist');
        return $db->where('id', $job)->delete();
    }

    /**
     * Gets the database connection.
     * @return Kraken Current database connection.
     */
    private static function getConnection()
    {
        if (!self::$db) self::$db = new Kraken(Config::get('queue.table', 'queue'), Config::get('queue.connection', 'default'));
        return self::$db;
    }

    /**
     * Deletes from the queue expired successful jobs.
     */
    private static function prune()
    {
        $db = self::getConnection();
        $db->whereNotNull('ran_at')
            ->where('ran_at', '<=', date('Y-m-d H:i:s', time() - Config::get('queue.keep_log', self::DELAY_DAY)))
            ->delete();
    }
}
