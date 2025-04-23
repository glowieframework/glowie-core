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
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/extra/migrations
 */
abstract class Migration
{

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
    final public function __construct()
    {
        // Creates the connection and stores the migration name
        $this->name = Util::classname($this);
        $this->table = Config::get('migrations.table', 'migrations');
        $this->db = new Kraken($this->table, $this->database);
        $this->forge = new Skeleton($this->table, $this->database);

        // Creates the migrations history table if not exists
        if (!isset(self::$tableCreated[$this->database])) $this->createMigrationsTable();
    }

    /**
     * Creates the migrations table in the database if not exists yet.
     */
    private function createMigrationsTable()
    {
        if (!$this->forge->tableExists($this->table)) {
            $this->forge->table($this->table)
                ->createColumn('name')->type(Skeleton::TYPE_STRING)->size(500)
                ->createColumn('applied_at')->type(Skeleton::TYPE_DATETIME)->default($this->getNowFunction())
                ->unique('name')
                ->ifNotExists()
                ->create();
        };

        self::$tableCreated[$this->database] = true;
    }

    /**
     * Gets the `NOW()` function by the database driver.
     * @return stdClass Returns the raw representation of the function.
     */
    private function getNowFunction()
    {
        switch ($this->forge->getDriver()) {
            case 'sqlite':
                return Skeleton::raw("datetime('now')");
                break;

            case 'pgsql':
                return Skeleton::raw('CURRENT_TIMESTAMP');
                break;

            case 'sqlsrv':
                return Skeleton::raw('GETDATE()');
                break;

            default:
                return Skeleton::raw('NOW()');
                break;
        }
    }

    /**
     * Checks if the migration was already applied.
     * @return bool Returns true if applied or false if not.
     */
    final public function isApplied()
    {
        return $this->db->clearQuery()
            ->database($this->database)
            ->table($this->table)
            ->where('name', $this->name)
            ->exists();
    }

    /**
     * Saves the migration to the migrations history table.
     * @return bool Returns true on success or false on errors.
     */
    final public function saveMigration()
    {
        return $this->db->clearQuery()
            ->database($this->database)
            ->table($this->table)
            ->insert(['name' => $this->name]);
    }

    /**
     * Deletes the migration from the migrations history table.
     * @return bool Returns true on success or false on errors.
     */
    final public function deleteMigration()
    {
        return $this->db->clearQuery()
            ->database($this->database)
            ->table($this->table)
            ->where('name', $this->name)
            ->delete();
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
