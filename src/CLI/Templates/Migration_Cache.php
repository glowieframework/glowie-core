<?php

namespace Glowie\Migrations;

use Config;
use Glowie\Core\Database\Migration;
use Glowie\Core\Database\Skeleton;

/**
 * Cache table migration for Glowie application.
 * @category Migration
 * @package glowieframework/glowie
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/extra/migrations
 */
class __FIREFLY_TEMPLATE_NAME__ extends Migration
{

    /**
     * Cache database connection name (from your app configuration).
     * @var string
     */
    protected $database = 'default';

    /**
     * Cache table name (from your app configuration).
     * @var string
     */
    private $table = 'cache';

    /**
     * This method will be called before any other methods from this migration.
     */
    public function init()
    {
        $this->database = Config::get('cache.connection', 'default');
        $this->table = Config::get('cache.table', 'cache');
    }

    /**
     * Runs the migration.
     * @return bool Returns true on success or false on errors.
     */
    public function run()
    {
        $this->forge->table($this->table)
            ->createColumn('key', Skeleton::TYPE_STRING, 255)
            ->createColumn('value', Skeleton::TYPE_BLOB)->nullable()
            ->createColumn('expires', Skeleton::TYPE_BIG_INTEGER_UNSIGNED)->nullable()
            ->primaryKey('key')
            ->ifNotExists()
            ->create();
    }

    /**
     * Rolls back the migration.
     * @return bool Returns true on success or false on errors.
     */
    public function rollback()
    {
        $this->forge->table($this->table)
            ->ifExists()
            ->drop();
    }
}
