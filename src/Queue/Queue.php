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
     * @link https://gabrielsilva.dev.br/glowie
     */
    class Queue{

        /**
         * Queue table name.
         * @var string
         */
        private static $table;

        /**
         * Stores if the queue table was created.
         * @var bool
         */
        private static $tableCreated = false;

        /**
         * Adds a job to the queue.
         * @param Job|string $job A job instance or job full classname with `Glowie\Jobs` namespace.
         * @param mixed $data (Optional) Data to pass to the job.
         * @param string $queue (Optional) Queue name to add this job to.
         * @param int $delay (Optional) Delay in seconds to run this job.
         */
        public static function add($job, $data = null, string $queue = 'default', int $delay = 0){
            // Stores the table name and checks its existence
            self::$table = Config::get('queue.table', 'queue');
            self::createTable();

            // Gets the job classname
            $job = is_object($job) ? get_class($job) : $job;

            // Add to queue
            $db = new Kraken(self::$table);
            $db->insert([
                'job' => $job,
                'queue' => $queue,
                'data' => is_null($data) ? null : serialize($data),
                'added_at' => date('Y-m-d H:i:s'),
                'delayed_to' => $delay !== 0 ? date('Y-m-d H:i:s', time() + $delay) : null
            ]);
        }

        /**
         * Processes the next jobs in the queue.
         * @param string $queue (Optional) Queue name.
         * @param bool $bail (Optional) Stop queue processing on job fail.
         * @param bool $verbose (Optional) Print status messages during execution.
         */
        public static function process(string $queue = 'default', bool $bail = false, bool $verbose = false){
            // Stores the table name and checks its existence
            self::$table = Config::get('queue.table', 'queue');
            self::createTable();

            // Get pending jobs from the queue
            $db = new Kraken(self::$table);
            $jobs = $db->where('queue', $queue)->whereNull('ran_at')->orderBy('id')->fetchAll();
            if(count($jobs) == 0){
                if($verbose) Firefly::print('<color="yellow">There are no pending jobs in this queue.</color>');
                return;
            }

            // Runs each job
            foreach($jobs as $jobRow){
                try {
                    // Checks if job is delayed
                    if($jobRow->delayed_to && time() < strtotime($jobRow->delayed_to)) continue;

                    // Stores start time
                    $time = microtime(true);
                    if($verbose) Firefly::print('<color="blue">Running ' . $jobRow->job . ' job...</color>');

                    // Create job instance and runs it
                    if(!class_exists($jobRow->job)) throw new QueueException('"' . $jobRow->job . '" was not found');
                    $job = new $jobRow->job(is_null($jobRow->data) ? null : unserialize($jobRow->data));
                    $job->run();

                    // Saves the state to the database on success
                    $db->where('id', $jobRow->id)->update(['ran_at' => date('Y-m-d H:i:s'), 'status' => 1]);

                    // Prints result if in verbose mode
                    $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
                    if($verbose) Firefly::print('<color="green">' . $jobRow->job . ' job ran successfully in ' . $time . '!</color>');
                } catch (\Throwable $th) {
                    // Checks to stop execution of queue on fail
                    if($bail) throw new QueueException($th->getMessage(), $th->getCode(), $th);

                    // Log error
                    if($verbose) Firefly::print('<color="red">' . $jobRow->job . ' failed! Skipping...</color>');
                    $date = date('Y-m-d H:i:s');
                    Handler::log("[{$date}] {$th->getMessage()} at file {$th->getFile()}:{$th->getLine()}\n{$th->getTraceAsString()}\n\n");
                }
            }

            // Finish message
            if($verbose){
                Firefly::print('');
                Firefly::print('<color="yellow">Queue finished.</color>');
            }
        }

        /**
         * Checks if the queue table already exists, and if not, creates it.
         */
        private static function createTable(){
            if(!self::$tableCreated){
                $db = new Skeleton();
                if(!$db->tableExists(self::$table)){
                    $db->table(self::$table)
                        ->id()
                        ->createColumn('job')->type(Skeleton::TYPE_STRING)->size(500)
                        ->createColumn('queue')->type(Skeleton::TYPE_STRING)->size(255)
                        ->createNullableColumn('data')->type(Skeleton::TYPE_BLOB)
                        ->createColumn('added_at')->type(Skeleton::TYPE_DATETIME)->default(Skeleton::raw('NOW()'))
                        ->createNullableColumn('delayed_to')->type(Skeleton::TYPE_DATETIME)
                        ->createNullableColumn('ran_at')->type(Skeleton::TYPE_DATETIME)
                        ->createColumn('status')->type(Skeleton::TYPE_TINY_INTEGER_UNSIGNED)->default('0')
                        ->create();
                }
                self::$tableCreated = true;
            }
        }

    }

?>