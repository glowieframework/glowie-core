<?php

namespace Glowie\Core\Queue;

use Config;
use Glowie\Core\Database\Kraken;
use Glowie\Core\Database\Skeleton;
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
     * Queue table name.
     * @var string
     */
    private static $table;

    /**
     * Adds a job to the queue.
     * @param string $job A job classname with namespace. You can use `JobName::class` to get this property correctly.
     * @param mixed $data (Optional) Data to pass to the job.
     * @param string $queue (Optional) Queue name to add this job to.
     * @param int $delay (Optional) Delay in seconds to run this job.
     */
    public static function add(string $job, $data = null, string $queue = 'default', int $delay = 0)
    {
        // Stores the table name
        self::$table = Config::get('queue.table', 'queue');

        // Add to queue
        $db = new Kraken(self::$table, Config::get('queue.connection', 'default'));
        $db->insert([
            'job' => $job,
            'queue' => $queue,
            'data' => is_null($data) ? null : serialize($data),
            'added_at' => date('Y-m-d H:i:s'),
            'delayed_to' => $delay !== 0 ? date('Y-m-d H:i:s', time() + $delay) : null
        ]);
    }

    /**
     * Processes the pending jobs in the queue.
     * @param string $queue (Optional) Queue name.
     * @param bool $bail (Optional) Stop queue processing on job fail.
     * @param bool $verbose (Optional) Print status messages during execution.
     * @param bool $watcher (Optional) Run queue in watcher mode (CLI only).
     */
    public static function process(string $queue = 'default', bool $bail = false, bool $verbose = false, bool $watcher = false)
    {
        // Stores the table name and delete expired jobs
        self::$table = Config::get('queue.table', 'queue');
        self::prune();

        // Get pending jobs from the queue
        $db = new Kraken(self::$table, Config::get('queue.connection', 'default'));
        $jobs = $db->where('queue', $queue)
            ->whereNull('ran_at')
            ->where('attempts', '<', Config::get('queue.max_attempts', 3))
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
                // Checks if job is delayed
                if ($jobRow->delayed_to && time() < strtotime($jobRow->delayed_to)) continue;

                // Stores start time
                $time = microtime(true);
                if ($verbose) Firefly::print(Firefly::color('[' . date('Y-m-d H:i:s') . '] Running ' . $jobRow->job . ' job...', 'blue'));

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
     * @param bool $success (Optional) Clear successful jobs.
     * @param bool $failed (Optional) Clear failed jobs.
     * @param bool $pending (Optional) Clear pending jobs.
     * @return bool Returns true on success, false on fail.
     */
    public static function clear(bool $success = false, bool $failed = false, bool $pending = false)
    {
        // Stores the table name
        self::$table = Config::get('queue.table', 'queue');

        // Connects to the database
        $db = new Kraken(self::$table, Config::get('queue.connection', 'default'));

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
     * Deletes from the queue expired successful jobs.
     */
    private static function prune()
    {
        $db = new Kraken(self::$table, Config::get('queue.connection', 'default'));
        $db->whereNotNull('ran_at')
            ->where('ran_at', '<=', date('Y-m-d H:i:s', time() - Config::get('queue.keep_log', self::DELAY_DAY)))
            ->delete();
    }
}
