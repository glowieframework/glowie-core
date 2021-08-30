<?php
    namespace Glowie\Core\Database;

    /**
     * Migration core for Glowie application.
     * @category Migrations
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    abstract class Migration{

        /**
         * Database connection handler.
         * @var Kraken
         */
        protected $db;

        /**
         * Migration name.
         * @var string
         */
        private $name;

        /**
         * Stores if the migrations history table was created.
         * @var bool
         */
        private static $tableCreated = false;

        /**
         * Instantiates a new migration.
         */
        final public function __construct(){
            // Creates the connection and stores the migration name
            $classname = explode('\\', get_class($this));
            $this->name = end($classname);
            $this->db = new Kraken();

            // Creates the migrations history table if not exists
            if(!self::$tableCreated){
                self::$tableCreated = $this->db->query(
                    'CREATE TABLE IF NOT EXISTS migrations(
                            name VARCHAR(255) NOT NULL,
                            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP()
                    )');
            }
        }

        /**
         * Checks if the migration was already applied.
         * @return bool Returns true if applied or false if not.
         */
        final public function isApplied(){
            $this->db->clearQuery();
            return $this->db->table('migrations')->where('name', $this->name)->exists();
        }

        /**
         * Saves the migration to the migrations history table.
         * @return bool Returns true on success or false on errors.
         */
        final public function saveMigration(){
            $this->db->clearQuery();
            return $this->db->table('migrations')->insert(['name' => $this->name]);
        }

        /**
         * Deletes the migration from the migrations history table.
         * @return bool Returns true on success or false on errors.
         */
        final public function deleteMigration(){
            $this->db->clearQuery();
            return $this->db->table('migrations')->where('name', $this->name)->delete();
        }

        /**
         * Runs the migration.
         * @return bool Returns true on success or false on errors.
         */
        abstract public function run();

        /**
         * Rolls back the migration.
         * @return bool Returns true on success or false on errors.
         */
        abstract public function rollback();

    }

?>