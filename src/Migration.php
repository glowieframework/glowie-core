<?php
    namespace Glowie\Core;

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
         * Migration filename.
         * @var string
         */
        private $filename;

        /**
         * Stores if the migrations history table was created.
         * @var bool
         */
        private static $tableCreated = false;

        /**
         * Instantiates a new migration.
         */
        public function __construct(){
            // Creates the connection and stores the migration filename
            $this->db = new Kraken();
            $this->filename = 'app/migrations/' . str_replace('Glowie\Migrations\\', '', static::class) . '.php';

            // Creates the migrations history table if not exists
            if(!self::$tableCreated){
                self::$tableCreated = $this->db->query(
                    'CREATE TABLE IF NOT EXISTS migrations(
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            filename VARCHAR(255) NOT NULL,
                            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP()
                        )',
                    true
                );
            }
        }

        /**
         * Checks if the migration was already applied.
         * @return bool Returns true if applied or false if not.
         */
        public function isApplied(){
            return $this->db->table('migrations')->where('filename', $this->filename)->exists();
        }

        /**
         * Saves the migration to the migrations history table.
         * @return bool Returns true on success or false on errors.
         */
        public function saveMigration(){
            return $this->db->table('migrations')->insert(['filename' => $this->filename]);
        }
        
        /**
         * Deletes the migration from the migrations history table.
         * @return bool Returns true on success or false on errors.
         */
        public function deleteMigration(){
            return $this->db->table('migrations')->where('filename', $this->filename)->delete();
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