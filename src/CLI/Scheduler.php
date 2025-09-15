<?php

namespace Glowie\Core\CLI;

use Glowie\Core\Exception\FileException;
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

    public function everySecond()
    {
        return $this->cron('* * * * * *');
    }

    public function everyTwoSeconds()
    {
        return $this->cron('*/2 * * * * *');
    }

    public function everyFiveSeconds()
    {
        return $this->cron('*/5 * * * * *');
    }

    public function everyTenSeconds()
    {
        return $this->cron('*/10 * * * * *');
    }

    public function everyFifteenSeconds()
    {
        return $this->cron('*/15 * * * * *');
    }

    public function everyTwentySeconds()
    {
        return $this->cron('*/20 * * * * *');
    }

    public function everyThirtySeconds()
    {
        return $this->cron('*/30 * * * * *');
    }

    public function everyMinute()
    {
        return $this->cron('* * * * *');
    }

    public function everyTwoMinutes()
    {
        return $this->cron('*/2 * * * *');
    }

    public function everyThreeMinutes()
    {
        return $this->cron('*/3 * * * *');
    }

    public function everyFourMinutes()
    {
        return $this->cron('*/4 * * * *');
    }

    public function everyFiveMinutes()
    {
        return $this->cron('*/5 * * * *');
    }

    public function everyTenMinutes()
    {
        return $this->cron('*/10 * * * *');
    }

    public function everyFifteenMinutes()
    {
        return $this->cron('*/15 * * * *');
    }

    public function everyThirtyMinutes()
    {
        return $this->cron('*/30 * * * *');
    }

    public function hourly()
    {
        return $this->cron('0 * * * *');
    }

    public function hourlyAt(int $minute)
    {
        return $this->cron($minute . ' * * * *');
    }

    public function everyOddHour(int $minute = 0)
    {
        return $this->cron($minute . ' 1-23/2 * * *');
    }

    public function everyTwoHours(int $minute = 0)
    {
        return $this->cron($minute . ' */2 * * *');
    }

    public function everyThreeHours(int $minute = 0)
    {
        return $this->cron($minute . ' */3 * * *');
    }

    public function everyFourHours(int $minute = 0)
    {
        return $this->cron($minute . ' */4 * * *');
    }

    public function everySixHours(int $minute = 0)
    {
        return $this->cron($minute . ' */6 * * *');
    }

    public function daily()
    {
        return $this->cron('0 0 * * *');
    }

    public function dailyAt(string $time)
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' * * *');
    }

    public function twiceDaily(int $firstHour, int $secondHour)
    {
        return $this->cron("0 " . $firstHour . ',' . $secondHour . " * * *");
    }

    public function twiceDailyAt(int $firstHour, int $secondHour, int $minute)
    {
        return $this->cron($minute . ' ' . $firstHour . ',' . $secondHour . ' * * *');
    }

    public function weekly()
    {
        return $this->cron('0 0 * * 0');
    }

    public function weeklyOn(int $dayOfWeek, string $time)
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' * * ' . $dayOfWeek);
    }

    public function monthly()
    {
        return $this->cron('0 0 1 * *');
    }

    public function monthlyOn(int $day, string $time)
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' ' . $day . ' * *');
    }

    public function twiceMonthly(int $firstDay, int $secondDay, string $time)
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' ' . $firstDay . ',' . $secondDay . ' * *');
    }

    public function lastDayOfMonth(string $time)
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' L * *');
    }

    public function quarterly()
    {
        return $this->cron('0 0 1 */3 *');
    }

    public function quarterlyOn(int $day, string $time)
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' ' . $day . ' */3 *');
    }

    public function yearly()
    {
        return $this->cron('0 0 1 1 *');
    }

    public function yearlyOn(int $month, int $day, string $time)
    {
        [$hour, $minute] = explode(':', $time) + [null, null];
        return $this->cron((int)$minute . ' ' . (int)$hour . ' ' . $day . ' ' . $month . ' *');
    }

    public function timezone(string $tz)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['timezone'] = $tz;
        return $this;
    }

    public function when(callable $callback)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['when'][] = $callback;
        return $this;
    }

    public function withoutOverlapping()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['withoutOverlapping'] = true;
        return $this;
    }

    public function weekdays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '1-5');
        return $this->cron($new);
    }

    public function weekends()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '0,6');
        return $this->cron($new);
    }

    public function sundays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '0');
        return $this->cron($new);
    }

    public function mondays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '1');
        return $this->cron($new);
    }

    public function tuesdays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '2');
        return $this->cron($new);
    }

    public function wednesdays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '3');
        return $this->cron($new);
    }

    public function thursdays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '4');
        return $this->cron($new);
    }

    public function fridays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '5');
        return $this->cron($new);
    }

    public function saturdays()
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        $new = self::replaceWeekField(self::$tasks[$i]['expression'], '6');
        return $this->cron($new);
    }

    public function days($days)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        if (is_array($days)) $days = implode(',', $days);
        $i = array_key_last(self::$tasks);
        $expr = self::$tasks[$i]['expression'];
        $new = self::replaceWeekField($expr, $days);
        return $this->cron($new);
    }

    public function between(string $startTime, string $endTime)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['between'] = [$startTime, $endTime];
        return $this;
    }

    public function unlessBetween(string $startTime, string $endTime)
    {
        if (empty(self::$tasks)) throw new Exception('Scheduler: No task was added to be modified');
        $i = array_key_last(self::$tasks);
        self::$tasks[$i]['conditions']['unlessBetween'] = [$startTime, $endTime];
        return $this;
    }

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
     * @param string $now Current time in format `H:i`.
     * @param string $start Start time in format `H:i`.
     * @param string $end End time in format in format `H:i`.
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
