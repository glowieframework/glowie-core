<?php

namespace Glowie\Core\Database;

use Closure;
use mysqli;

/**
 * Database factory for Glowie application.
 * @category Database
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/forms-and-data/databases
 */
class Factory
{

    /**
     * Database connection handlers.
     * @var array
     */
    private static $handlers = [];

    /**
     * Query listeners.
     * @var array
     */
    private static $listeners = [];

    /**
     * Gets a connection handler.
     * @param string $name Connection name.
     * @return mysqli|null The connection instance if exists or null if not.
     */
    public static function getHandler(string $name)
    {
        return self::$handlers[$name] ?? null;
    }

    /**
     * Sets a connection handler.
     * @param string $name Connection name.
     * @param mysql $connection Connection instance.
     */
    public static function setHandler(string $name, mysqli $connection)
    {
        self::$handlers[$name] = $connection;
    }

    /**
     * Setup a query listener.
     * @param Closure $callback Listener callback function. It receives the query, bindings, duration (ms) and status as parameters.
     */
    public static function listen(Closure $callback)
    {
        self::$listeners[] = $callback;
    }

    /**
     * Notify query listeners that a query ran.
     * @param string $query SQL query.
     * @param array $bindings Prepared bindings.
     * @param float $time Query duration in microsseconds.
     * @param bool $status Query status, true for success, false for fail.
     */
    public static function notifyListeners(string $query, array $bindings, float $time, bool $status)
    {
        if (empty(self::$listeners)) return;
        foreach (self::$listeners as $item) call_user_func_array($item, [$query, $bindings, round($time * 1000, 2), $status]);
    }
}
