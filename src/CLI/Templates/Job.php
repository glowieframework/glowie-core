<?php
    namespace Glowie\Jobs;

    use Glowie\Core\Queue\Job;
    use Throwable;

    /**
     * __FIREFLY_TEMPLATE_NAME__ job for Glowie application.
     * @category Queue
     * @package glowieframework/glowie
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     */
    class __FIREFLY_TEMPLATE_NAME__ extends Job{

        /**
         * Runs the job.
         */
        public function run(){
            // Create something awesome
        }

        /**
         * Called if the job fails.
         * @param Throwable $th Receives the thrown Exception.
         */
        public function fail(Throwable $th){
            //
        }

    }

?>