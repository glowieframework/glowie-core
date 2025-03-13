<?php

namespace Glowie\Migrations;

use Config;
use Glowie\Core\Database\Migration;
use Glowie\Core\Database\Skeleton;

/**
 * Queue table migration for Glowie application.
 * @category Migration
 * @package glowieframework/glowie
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://gabrielsilva.dev.br/glowie
 * @see https://gabrielsilva.dev.br/glowie/docs/latest/extra/migrations
 */
class __FIREFLY_TEMPLATE_NAME__ extends Migration
{

    /**
     * Migration database connection name (from your app configuration).
     * @var string
     */
    protected $database = 'default';

    /**
     * Cache table name (from your app configuration).
     * @var string
     */
    private $table = 'queue';

    /**
     * This method will be called before any other methods from this migration.
     */
    public function init()
    {
        $this->database = Config::get('queue.connection', 'default');
        $this->table = Config::get('queue.table', 'queue');
    }

    /**
     * Runs the migration.
     * @return bool Returns true on success or false on errors.
     */
    public function run()
    {
        $this->forge->table($this->table)
            ->id()
            ->createColumn('job')->type(Skeleton::TYPE_TEXT)
            ->createColumn('queue')->type(Skeleton::TYPE_STRING)->size(255)
            ->createColumn('data')->type(Skeleton::TYPE_LONG_BLOB)->nullable()
            ->createColumn('added_at')->type(Skeleton::TYPE_DATETIME)->default(Skeleton::raw('NOW()'))
            ->createColumn('delayed_to')->type(Skeleton::TYPE_DATETIME)->nullable()
            ->createColumn('ran_at')->type(Skeleton::TYPE_DATETIME)->nullable()
            ->createColumn('attempts')->type(Skeleton::TYPE_INTEGER)->unsigned()->default('0')
            ->createColumn('errors')->type(Skeleton::TYPE_LONG_TEXT)->nullable()
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
