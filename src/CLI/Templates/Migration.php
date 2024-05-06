<?php
    namespace Glowie\Migrations;

    use Glowie\Core\Database\Migration;
    use Glowie\Core\Database\Skeleton;
    use Glowie\Core\Database\Kraken;

    /**
     * __FIREFLY_TEMPLATE_NAME__ migration for Glowie application.
     * @category Migration
     * @package glowieframework/glowie
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     * @see https://gabrielsilva.dev.br/glowie/docs/latest/extra/migrations
     */
    class __FIREFLY_TEMPLATE_NAME__ extends Migration{

        /**
         * Migration database connection name (from your app configuration).
         * @var string
         */
        protected $database = 'default';

        /**
         * This method will be called before any other methods from this migration.
         */
        public function init(){
            //
        }

        /**
         * Runs the migration.
         * @return bool Returns true on success or false on errors.
         */
        public function run(){
            // Create something awesome
        }

        /**
         * Rolls back the migration.
         * @return bool Returns true on success or false on errors.
         */
        public function rollback(){
            // Create something awesome
        }

    }

?>