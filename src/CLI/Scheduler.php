<?php

namespace Glowie\Core\CLI;

use Glowie\Core\Exception\FileException;
use Glowie\Core\Queue\Job;
use Config;
use DateTime;
use DateTimeZone;
use Exception;
use Util;

/**
 * Task scheduler tool for Glowie application.
 * @category CLI
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class Scheduler
{
    /**
     * Array of tasks.
     * @var array
     */
    private static $tasks = [];

    /**
     * Schedules a new task.
     * @param callable $callback Task callback to be executed.
     * @param string $expression (Optional) Cron expression to schedule the task.
     * @param array $conditions (Optional) Associative array of execution conditions.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public static function schedule(callable $callback, string $expression = '* * * * *', array $conditions = [])
    {
        self::$tasks[] = [
            'callback' => $callback,
            'expression' => $expression,
            'conditions' => $conditions
        ];

        return new static;
    }

    /**
     * Schedules a Firefly command.
     * @param string $command Firefly command to call.
     * @param array $args (Optional) Associative array of arguments to pass with the command.
     * @param string $expression (Optional) Cron expression to schedule the task.
     * @param array $conditions (Optional) Associative array of execution conditions.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public static function command(string $command, array $args = [], string $expression = '* * * * *', array $conditions = [])
    {
        return self::schedule(function () use ($command, $args) {
            Firefly::call($command, $args);
        }, $expression, $conditions);
    }

    /**
     * Schedules a dispatch of a job to the queue.
     * @param Job $job Job instance to be dispatched.
     * @param string $queue (Optional) Queue name to add this job to.
     * @param string $expression (Optional) Cron expression to schedule the task.
     * @param array $conditions (Optional) Associative array of execution conditions.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public static function job(Job $job, string $queue = 'default', string $expression = '* * * * *', array $conditions = [])
    {
        return self::schedule(function () use ($job, $queue) {
            $job->dispatch($queue);
        }, $expression, $conditions);
    }

    /**
     * Runs the scheduled tasks.
     * @return bool Returns true on success, false if no task exists.
     */
    public static function run()
    {
        // Checks if the tasks are empty
        if (empty(self::$tasks)) return false;

        // Loops through each task
        foreach (self::$tasks as $i => $task) {
            $lockFile = null;
            $shouldRun = true;

            // Checks the environments condition
            if (!empty($task['conditions']['env'])) {
                if (!in_array(Config::get('env', 'development'), $task['conditions']['env'])) continue;
            }

            // Sets the task timezone
            $now = new DateTime('now', new DateTimeZone($task['conditions']['timezone'] ?? date_default_timezone_get()));

            // Checks for between conditions
            if (!empty($task['conditions']['between'])) {
                [$start, $end] = $task['conditions']['between'];
                $fmt = $now->format('H:i');
                if (!self::isBetween($fmt, $start, $end)) continue;
            }

            // Checks for unless between conditions
            if (!empty($task['conditions']['unlessBetween'])) {
                [$start, $end] = $task['conditions']['unlessBetween'];
                $fmt = $now->format('H:i');
                if (self::isBetween($fmt, $start, $end)) continue;
            }

            // Checks for when conditions
            if (!empty($task['conditions']['when'])) {
                foreach ($task['conditions']['when'] as $cond) {
                    if (!call_user_func_array($cond, [$task])) {
                        $shouldRun = false;
                        break;
                    }
                }
            }

            // Skips the task if one of the conditions failed
            if (!$shouldRun) continue;

            // Matches the cron expression
            if (!self::matchExpression($now, $task['expression'])) continue;

            // Checks for without overlapping setting
            if (!empty($task['conditions']['withoutOverlapping'])) {
                // Checks if the tmp path exists
                $tmpPath = Util::location('storage/tmp');
                if (!is_dir($tmpPath)) mkdir($tmpPath, 0755);
                if (!is_writable($tmpPath)) throw new FileException('Directory "app/storage/tmp" is not writable, please check your chmod settings');

                // Checks if the lock file exists or creates it
                $lockFile = $tmpPath . '/' . md5('scheduler_task_' . $i) . '.lock';
                if (is_file($lockFile)) {
                    $shouldRun = false;
                } else {
                    file_put_contents($lockFile, time());
                }
            }

            //Checks if the task is able to run
            if ($shouldRun) {
                try {
                    // Calls the task callback
                    call_user_func($task['callback']);
                } finally {
                    // Deletes the lock file if exists
                    if ($lockFile !== null && is_file($lockFile)) unlink($lockFile);
                }
            }
        }

        // Returns the result
        return true;
    }

    /**
     * Sets the last added task to be executed in a cron expression.
     * @param string $expression Cron expression to schedule the task.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function cron(string $expression)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['expression'] = $expression;
        return $this;
    }

    /**
     * Sets the last added task to be executed every second.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everySecond()
    {
        return $this->cron('* * * * * *');
    }

    /**
     * Sets the last added task to be executed every 2 seconds.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyTwoSeconds()
    {
        return $this->cron('*/2 * * * * *');
    }

    /**
     * Sets the last added task to be executed every 5 seconds.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyFiveSeconds()
    {
        return $this->cron('*/5 * * * * *');
    }

    /**
     * Sets the last added task to be executed every 10 seconds.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyTenSeconds()
    {
        return $this->cron('*/10 * * * * *');
    }

    /**
     * Sets the last added task to be executed every 15 seconds.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyFifteenSeconds()
    {
        return $this->cron('*/15 * * * * *');
    }

    /**
     * Sets the last added task to be executed every 20 seconds.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyTwentySeconds()
    {
        return $this->cron('*/20 * * * * *');
    }

    /**
     * Sets the last added task to be executed every 30 seconds.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyThirtySeconds()
    {
        return $this->cron('*/30 * * * * *');
    }

    /**
     * Sets the last added task to be executed every minute.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyMinute()
    {
        return $this->cron('* * * * *');
    }

    /**
     * Sets the last added task to be executed every 2 minutes.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyTwoMinutes()
    {
        return $this->cron('*/2 * * * *');
    }

    /**
     * Sets the last added task to be executed every 3 minutes.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyThreeMinutes()
    {
        return $this->cron('*/3 * * * *');
    }

    /**
     * Sets the last added task to be executed every 4 minutes.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyFourMinutes()
    {
        return $this->cron('*/4 * * * *');
    }

    /**
     * Sets the last added task to be executed every 5 minutes.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyFiveMinutes()
    {
        return $this->cron('*/5 * * * *');
    }

    /**
     * Sets the last added task to be executed every 10 minutes.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyTenMinutes()
    {
        return $this->cron('*/10 * * * *');
    }

    /**
     * Sets the last added task to be executed every 15 minutes.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyFifteenMinutes()
    {
        return $this->cron('*/15 * * * *');
    }

    /**
     * Sets the last added task to be executed every 30 minutes.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyThirtyMinutes()
    {
        return $this->cron('*/30 * * * *');
    }

    /**
     * Sets the last added task to be executed every hour.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function hourly()
    {
        return $this->cron('0 * * * *');
    }

    /**
     * Sets the last added task to be executed every hour at a specified minute.
     * @param int $minute Minute in the hour to run the task.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function hourlyAt(int $minute)
    {
        return $this->cron($minute . ' * * * *');
    }

    /**
     * Sets the last added task to be executed every odd hour.
     * @param int $minute (Optional) Minute in the hour to run the task.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyOddHour(int $minute = 0)
    {
        return $this->cron($minute . ' 1-23/2 * * *');
    }

    /**
     * Sets the last added task to be executed every 2 hours.
     * @param int $minute (Optional) Minute in the hour to run the task.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyTwoHours(int $minute = 0)
    {
        return $this->cron($minute . ' */2 * * *');
    }

    /**
     * Sets the last added task to be executed every 3 hours.
     * @param int $minute (Optional) Minute in the hour to run the task.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyThreeHours(int $minute = 0)
    {
        return $this->cron($minute . ' */3 * * *');
    }

    /**
     * Sets the last added task to be executed every 4 hours.
     * @param int $minute (Optional) Minute in the hour to run the task.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everyFourHours(int $minute = 0)
    {
        return $this->cron($minute . ' */4 * * *');
    }

    /**
     * Sets the last added task to be executed every 6 hours.
     * @param int $minute (Optional) Minute in the hour to run the task.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function everySixHours(int $minute = 0)
    {
        return $this->cron($minute . ' */6 * * *');
    }

    /**
     * Sets the last added task to be executed every day.
     * @param int $minute (Optional) Minute in the hour to run the task.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function daily()
    {
        return $this->cron('0 0 * * *');
    }

    /**
     * Sets the last added task to be executed every day at a specified time.
     * @param string $time Time of the day when to execute the task in the format `H:i`.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function dailyAt(string $time)
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' * * *');
    }

    /**
     * Sets the last added task to be executed twice a day.
     * @param int $firstHour (Optional) First hour of the day when to run the task.
     * @param int $secondHour (Optional) Second hour of the day when to run the task.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function twiceDaily(int $firstHour = 0, int $secondHour = 12)
    {
        return $this->cron("0 " . $firstHour . ',' . $secondHour . " * * *");
    }

    /**
     * Sets the last added task to be executed twice a day at a specified minute.
     * @param int $firstHour First hour of the day when to run the task.
     * @param int $secondHour Second hour of the day when to run the task.
     * @param int $minute Minute of each hour when to run the task.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function twiceDailyAt(int $firstHour, int $secondHour, int $minute)
    {
        return $this->cron($minute . ' ' . $firstHour . ',' . $secondHour . ' * * *');
    }

    /**
     * Sets the last added task to be executed once per week.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function weekly()
    {
        return $this->cron('0 0 * * 0');
    }

    /**
     * Sets the last added task to be executed once per week at a specified day and time.
     * @param int $dayOfWeek Day of week when to run the task. **From 0 (sunday) to 6 (saturday)**.
     * @param string $time (Optional) Time of the day when to execute the task in the format `H:i`.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function weeklyOn(int $dayOfWeek, string $time = '00:00')
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' * * ' . $dayOfWeek);
    }

    /**
     * Sets the last added task to be executed once per month.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function monthly()
    {
        return $this->cron('0 0 1 * *');
    }

    /**
     * Sets the last added task to be executed once per month at a specified day and time.
     * @param int $day Day of the month when to run the task.
     * @param string $time (Optional) Time of the day when to execute the task in the format `H:i`.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function monthlyOn(int $day, string $time = '00:00')
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' ' . $day . ' * *');
    }

    /**
     * Sets the last added task to be executed twice per month at a specified day and time.
     * @param int $firstDay (Optional) First day of the month when to run the task.
     * @param int $secondDay (Optional) Second day of the month when to run the task.
     * @param string $time (Optional) Time of the day when to execute the task in the format `H:i`.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function twiceMonthly(int $firstDay = 1, int $secondDay = 15, string $time = '00:00')
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' ' . $firstDay . ',' . $secondDay . ' * *');
    }

    /**
     * Sets the last added task to be executed in the last day of every month, at a specified time.
     * @param string $time (Optional) Time of the day when to execute the task in the format `H:i`.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function lastDayOfMonth(string $time = '00:00')
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' L * *');
    }

    /**
     * Sets the last added task to be executed on the first day of every quarter.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function quarterly()
    {
        return $this->cron('0 0 1 */3 *');
    }

    /**
     * Sets the last added task to be executed in a specified day and time of every quarter.
     * @param int $day Day of the month in the quarter to run the task.
     * @param string $time (Optional) Time of the day when to execute the task in the format `H:i`.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function quarterlyOn(int $day, string $time = '00:00')
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' ' . $day . ' */3 *');
    }

    /**
     * Sets the last added task to be executed on the first day of every year.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function yearly()
    {
        return $this->cron('0 0 1 1 *');
    }

    /**
     * Sets the last added task to be executed every year at a specified day and time.
     * @param int $month Month of the year when to run the task.
     * @param int $day Day of the month when to run the task.
     * @param string $time (Optional) Time of the day when to execute the task in the format `H:i`.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function yearlyOn(int $month, int $day, string $time = '00:00')
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' ' . $day . ' ' . $month . ' *');
    }

    /**
     * Sets the timezone of the last added task.
     * @param string $timezone Timezone to set. Must be a valid PHP timezone.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function timezone(string $timezone)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['timezone'] = $timezone;
        return $this;
    }

    /**
     * Executes the last added task when a truth test passes.
     * @param callable $callback Truth test to be evaluated.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function when(callable $callback)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['when'][] = $callback;
        return $this;
    }

    /**
     * Sets the last added task to run only when the last execution of the same task is finished.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function withoutOverlapping()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['withoutOverlapping'] = true;
        return $this;
    }

    /**
     * Sets the last added task to run only on weekdays.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function weekdays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '1-5');
        return $this->cron($new);
    }

    /**
     * Sets the last added task to run only on weekends.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function weekends()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '0,6');
        return $this->cron($new);
    }

    /**
     * Sets the last added task to run only on sundays.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function sundays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '0');
        return $this->cron($new);
    }

    /**
     * Sets the last added task to run only on mondays.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function mondays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '1');
        return $this->cron($new);
    }

    /**
     * Sets the last added task to run only on tuesdays.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function tuesdays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '2');
        return $this->cron($new);
    }

    /**
     * Sets the last added task to run only on wednesdays.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function wednesdays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '3');
        return $this->cron($new);
    }

    /**
     * Sets the last added task to run only on thursdays.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function thursdays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '4');
        return $this->cron($new);
    }

    /**
     * Sets the last added task to run only on fridays.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function fridays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '5');
        return $this->cron($new);
    }

    /**
     * Sets the last added task to run only on saturdays.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function saturdays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '6');
        return $this->cron($new);
    }

    /**
     * Sets the last added task to run only on specified days of the week.
     * @param string|array $days Days when to run the task. Can be a single or an array of days. **From 0 (sunday) to 6 (saturday)**.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function days($days)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        if (is_array($days)) $days = implode(',', $days);
        $i = array_key_last(self::$tasks);
        $expr = self::$tasks[$i]['expression'];
        $new = self::replaceWeekField($expr, $days);
        return $this->cron($new);
    }

    /**
     * Sets the last added task to run only between a specified time range.
     * @param string $startTime Start time of the range on format `H:i`.
     * @param string $endTime End time of the range on format `H:i`.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function between(string $startTime, string $endTime)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['between'] = [$startTime, $endTime];
        return $this;
    }

    /**
     * Sets the last added task to run only if NOT between a specified time range.
     * @param string $startTime Start time of the range on format `H:i`.
     * @param string $endTime End time of the range on format `H:i`.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function unlessBetween(string $startTime, string $endTime)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['unlessBetween'] = [$startTime, $endTime];
        return $this;
    }

    /**
     * Sets the last added task to run only in a specified app environment.
     * @param string|array $env Environment name or an array of environments.
     * @return Scheduler Returns the current Scheduler instance for nested calls.
     */
    public function environments($env)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['env'] = (array)$env;
        return $this;
    }

    /**
     * Replaces the week field part of a cron expression.
     * @param string $expression Expression to be replaced.
     * @param string $weekField Week field value to set.
     * @return string Returns the expression with the week field replaced.
     */
    private static function replaceWeekField(string $expression, string $weekField)
    {
        $parts = preg_split('/\s+/', trim($expression));
        $count = count($parts);

        if ($count === 5) {
            $parts[4] = $weekField;
        } else if ($count === 6) {
            $parts[5] = $weekField;
        } else {
            throw new Exception("Scheduler: Invalid cron expression \"$expression\"");
        }

        return implode(' ', $parts);
    }

    /**
     * Checks if the time is between a range.
     * @param string $now Current time on format `H:i`.
     * @param string $start Start time on format `H:i`.
     * @param string $end End time on format on format `H:i`.
     * @return bool Returns true if the time is between the range.
     */
    private static function isBetween(string $now, string $start, string $end)
    {
        // Converts to DateTime instances
        $now = DateTime::createFromFormat('H:i', $now);
        $start = DateTime::createFromFormat('H:i', $start);
        $end = DateTime::createFromFormat('H:i', $end);

        // Checks if the time is between
        if (!$now || !$start || !$end) return false;
        if ($start <= $end) return $now >= $start && $now <= $end;
        return $now >= $start || $now <= $end;
    }

    /**
     * Matches a cron expression with the current date time.
     * @param DateTime $now Current DateTime to be checked against.
     * @param string $expression The cron expression to match.
     * @return bool Returns true if the expression matches the current time.
     * @throws Exception Throws an exception if the cron expression is invalid.
     */
    private static function matchExpression(DateTime $now, string $expression)
    {
        // Splits the expression into parts
        $parts = preg_split('/\s+/', trim($expression));
        $count = count($parts);

        // Checks if the expression has 6 parts
        if ($count === 6) {
            [$sec, $min, $hour, $day, $month, $week] = $parts;
            return self::matchCronPart($sec, $now->format('s'), 0, 59, $now)
                && self::matchCronPart($min, $now->format('i'), 0, 59, $now)
                && self::matchCronPart($hour, $now->format('H'), 0, 23, $now)
                && self::matchCronPart($day, $now->format('d'), 1, 31, $now)
                && self::matchCronPart($month, $now->format('m'), 1, 12, $now)
                && self::matchCronPart($week, $now->format('w'), 0, 6, $now);
        }

        // Checks if the expression has 5 parts
        if ($count === 5) {
            [$min, $hour, $day, $month, $week] = $parts;
            return self::matchCronPart($min, $now->format('i'), 0, 59, $now)
                && self::matchCronPart($hour, $now->format('H'), 0, 23, $now)
                && self::matchCronPart($day, $now->format('d'), 1, 31, $now)
                && self::matchCronPart($month, $now->format('m'), 1, 12, $now)
                && self::matchCronPart($week, $now->format('w'), 0, 6, $now);
        }

        // Returns an error if the expression is invalid
        throw new Exception("Scheduler: Invalid cron expression \"$expression\"");
    }

    /**
     * Matches a cron segment part with the current date time.
     * @param string $expr Cron segment to be matched.
     * @param string $value Value to be matched against.
     * @param int $minRange Minimum range of the value.
     * @param int $maxRange Maximum range of the value.
     * @param DateTime|null $now (Optional) Current DateTime to match some expressions.
     * @return bool Returns true if the part matches.
     */
    private static function matchCronPart(string $expr, string $value, int $minRange, int $maxRange, ?DateTime $now = null)
    {
        // Checks if the value is between ranges
        $val = (int)$value;
        if ($val < $minRange || $val > $maxRange) return false;

        // Checks for wildcard
        if ($expr === '*') return true;

        // Checks for day of month field
        if ($expr === 'L' && $minRange === 1 && $maxRange === 31) {
            $lastDay = $now ? (int)$now->format('t') : (int)date('t');
            return $val === $lastDay;
        }

        // Splits list of values
        foreach (explode(',', $expr) as $part) {
            $part = trim($part);
            if ($part === '') continue;

            // Step
            if (preg_match('/^\*\/(\d+)$/', $part, $m)) {
                $step = (int)$m[1];
                if ($step > 0 && ($val % $step) === 0) return true;
                continue;
            }

            // Interval with step
            if (preg_match('/^(\d+)-(\d+)\/(\d+)$/', $part, $m)) {
                $start = max((int)$m[1], $minRange);
                $end = min((int)$m[2], $maxRange);
                $step = (int)$m[3];
                if ($step > 0 && $val >= $start && $val <= $end && (($val - $start) % $step) === 0) return true;
                continue;
            }

            // Simple interval
            if (preg_match('/^(\d+)-(\d+)$/', $part, $m)) {
                $start = max((int)$m[1], $minRange);
                $end = min((int)$m[2], $maxRange);
                if ($val >= $start && $val <= $end) return true;
                continue;
            }

            // Fixed value
            if (preg_match('/^\d+$/', $part)) {
                $num = (int)$part;
                if ($num >= $minRange && $num <= $maxRange && $val === $num) return true;
            }
        }

        // Nothing matches
        return false;
    }
}
