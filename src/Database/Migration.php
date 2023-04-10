<?php
    namespace Glowie\Core\Database;

    use Util;
    use Config;

    /**
     * Migration core for Glowie application.
     * @category Migrations
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    abstract class Migration{

        /**
         * Database connection handler.
         * @var Kraken
         */
        protected $db;

        /**
         * Database schema handler.
         * @var Skeleton
         */
        protected $forge;

        /**
         * Migration database connection name (from your app configuration).
         * @var string
         */
        protected $database = 'default';

        /**
         * Migration name.
         * @var string
         */
        private $name;

        /**
         * Migrations history table name.
         * @var string
         */
        private $table;

        /**
         * Stores if the migrations history table was created (in the current database).
         * @var array
         */
        private static $tableCreated = [];

        /**
         * Instantiates a new migration.
         */
        final public function __construct(){
            // Creates the connection and stores the migration name
            $this->name = Util::classname($this);
            $this->table = Config::get('migrations.table', 'migrations');
            $this->db = new Kraken($this->table, $this->database);
            $this->forge = new Skeleton($this->table, $this->database);

            // Creates the migrations history table if not exists
            if(!isset(self::$tableCreated[$this->database])){
                if(!$this->forge->tableExists($this->table)){
                    $this->forge->table($this->table)
                                ->createColumn('name', Skeleton::TYPE_STRING, 255)
                                ->createColumn('applied_at', Skeleton::TYPE_DATETIME, null, Skeleton::raw('NOW()'))
                                ->create();
                };
                self::$tableCreated[$this->database] = true;
            }
        }

        /**
         * Checks if the migration was already applied.
         * @return bool Returns true if applied or false if not.
         */
        final public function isApplied(){
            $this->db->clearQuery();
            return $this->db->database($this->database)->table($this->table)->where('name', $this->name)->exists();
        }

        /**
         * Saves the migration to the migrations history table.
         * @return bool Returns true on success or false on errors.
         */
        final public function saveMigration(){
            $this->db->clearQuery();
            return $this->db->database($this->database)->table($this->table)->insert(['name' => $this->name]);
        }

        /**
         * Deletes the migration from the migrations history table.
         * @return bool Returns true on success or false on errors.
         */
        final public function deleteMigration(){
            $this->db->clearQuery();
            return $this->db->database($this->database)->table($this->table)->where('name', $this->name)->delete();
        }

        /**
         * Runs the migration.
         * @return bool Returns true on success or false on errors.
         */
        public abstract function run();

        /**
         * Rolls back the migration.
         * @return bool Returns true on success or false on errors.
         */
        public abstract function rollback();

    }

?>