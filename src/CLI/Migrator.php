<?php

namespace Glowie\Core\CLI;

use Config;
use Util;
use Glowie\Core\Database\Kraken;
use Glowie\Core\Database\Skeleton;
use Glowie\Core\Exception\ConsoleException;
use Glowie\Core\Exception\FileException;
use Glowie\Core\Exception\QueryException;

/**
 * Database migration runner.
 * @category CLI
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class Migrator
{

    /**
     * Creates a new migration file.
     * @param string $name Name of the migration.
     * @param string $template (Optional) Template filename.
     * @return bool True on success, false on failure.
     */
    public static function create(string $name, string $template = 'Migration.php')
    {
        // Checks permissions
        if (!is_dir(Util::location('migrations'))) mkdir(Util::location('migrations'), 0775, true);
        if (!is_writable(Util::location('migrations'))) throw new FileException('Directory "app/migrations" is not writable, please check your chmod settings');

        // Validates the migration name
        if (Util::isEmpty($name)) throw new ConsoleException(Firefly::getCommand(), Firefly::getArgs(), 'Missing required argument "name" for this command');

        // Checks if the file exists
        $cleanName = Util::pascalCase($name);
        $name = 'm' . date('Y_m_d_His_') . $cleanName;
        $targetFile = Util::location('migrations/' . $name . '.php');
        if (is_file($targetFile)) throw new ConsoleException(Firefly::getCommand(), Firefly::getArgs(), "Migration {$cleanName} already exists!");

        // Creates the file
        $template = file_get_contents(Firefly::TEMPLATES_FOLDER . $template);
        $template = str_replace('__FIREFLY_TEMPLATE_NAME__', $name, $template);
        file_put_contents($targetFile, $template);

        // Success message
        Firefly::print(Firefly::color('[' . date('Y-m-d H:i:s') . ']' . " Migration {$cleanName} created successfully!", 'green'));
        Firefly::print(Firefly::color('File: ' . $targetFile, 'cyan'));
        return true;
    }

    /**
     * Runs pending migrations.
     * @return bool True on success, false on failure.
     */
    public static function migrate()
    {
        // Gets the args
        $steps = (int)Firefly::getArg('steps', 'all');
        $path = Firefly::getArg('path');

        // Stores current state
        $migrateRun = false;
        $stepsDone = 0;

        // Checks for schema files
        foreach (glob(Util::location('migrations/*.sql')) as $filename) {
            // Gets the connection name from the file
            $connection = pathinfo($filename, PATHINFO_FILENAME);

            // Checks if the migrations table already exists
            $forge = new Skeleton('glowie', $connection);
            if ($forge->tableExists(Config::get('migrations.table', 'migrations'))) continue;

            // Runs the schema file
            $db = new Kraken('glowie', $connection);
            $sql = file_get_contents($filename);

            try {
                $db->query($sql, false);
                $migrateRun = true;
            } catch (QueryException $th) {
                $db->getConnection()->rollback();
                throw $th;
            }
        }

        // Loops through all the migration files
        foreach (glob(Util::location('migrations/*.php')) as $filename) {
            // Checks current state
            if ($steps !== 'all' && $stepsDone === $steps) break;

            // Checks the path
            $name = pathinfo($filename, PATHINFO_FILENAME);
            if (!empty($path) && $path !== $name) continue;

            // Stores the execution start time
            $time = microtime(true);

            // Gets the migration class name
            $classname = 'Glowie\Migrations\\' . $name;
            if (!class_exists($classname)) continue;

            // Instantiates the migration class
            $migration = new $classname;
            if (is_callable([$migration, 'init'])) $migration->init();

            // Checks if the migration was already applied
            if (!$migration->isApplied()) {
                $date = date('Y-m-d H:i:s');
                Firefly::print(Firefly::color("[{$date}] Applying migration {$name}...", 'blue'));
                $migration->run();
                $migration->saveMigration();
                $migrateRun = true;
                $stepsDone++;
                $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
                Firefly::print(Firefly::color("[{$date}] Migration {$name} applied successfully in {$time}!", 'green'));
            }
        }

        // Checks if no migrations were run
        if (!$migrateRun) {
            Firefly::print(Firefly::color('[' . date('Y-m-d H:i:s') . '] There are no new migrations to apply.', 'yellow'));
            return true;
        } else {
            Firefly::print('');
            Firefly::print(Firefly::color('[' . date('Y-m-d H:i:s') . '] ' . $stepsDone . ' migrations were applied successfully.', 'yellow'));
            return true;
        }
    }

    /**
     * Rolls back applied migrations.
     * @return bool True on success, false on failure.
     */
    public static function rollback()
    {
        // Gets the args
        $steps = (int)Firefly::getArg('steps', 1);
        $path = Firefly::getArg('path');

        // Stores current state
        $rollbackRun = false;
        $stepsDone = 0;

        // Loops through all the migration files
        foreach (array_reverse(glob(Util::location('migrations/*.php'))) as $filename) {
            // Checks current state
            if ($steps !== 'all' && $stepsDone === $steps) break;

            // Checks the path
            $name = pathinfo($filename, PATHINFO_FILENAME);
            if (!empty($path) && $path !== $name) continue;

            // Stores the execution start time
            $time = microtime(true);

            // Gets the migration class name
            $classname = 'Glowie\Migrations\\' . $name;
            if (!class_exists($classname)) continue;

            // Instantiates the migration class
            $migration = new $classname;
            if (is_callable([$migration, 'init'])) $migration->init();

            // Checks if the migration was already applied
            if ($migration->isApplied()) {
                $date = date('Y-m-d H:i:s');
                Firefly::print(Firefly::color("[{$date}] Rolling back migration {$name}...", 'blue'));
                $migration->rollback();
                $migration->deleteMigration();
                $rollbackRun = true;
                $stepsDone++;
                $time = round((microtime(true) - $time) * 1000, 2) . 'ms';
                Firefly::print(Firefly::color("[$date] Migration {$name} rolled back successfully in {$time}!", 'green'));
            }
        }

        // Checks if migrations were rolled back
        if (!$rollbackRun) {
            Firefly::print(Firefly::color('[' . date('Y-m-d H:i:s') . '] There are no migrations to rollback.', 'yellow'));
            return true;
        } else {
            Firefly::print('');
            Firefly::print(Firefly::color('[' . date('Y-m-d H:i:s') . '] ' . $stepsDone . ' migrations were rolled back successfully.', 'yellow'));
            return true;
        }
    }

    /**
     * Gets the status of the migrations.
     * @return bool True on success, false on failure.
     */
    public static function list()
    {
        // Loops through all the migration files
        $result = [];
        foreach (glob(Util::location('migrations/*.php')) as $filename) {
            // Gets the migration class name
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $classname = 'Glowie\Migrations\\' . $name;
            if (!class_exists($classname)) continue;

            // Instantiates the migration class and check if it was applied
            $migration = new $classname;
            $result[] = [$name, $migration->isApplied() ? Firefly::color('APPLIED', 'green') : Firefly::color('PENDING', 'yellow')];
        }

        // Checks for empty migrations folder
        if (empty($result)) {
            Firefly::print(Firefly::color('[' . date('Y-m-d H:i:s') . '] There are no migrations.', 'yellow'));
            return false;
        }

        // Prints the result as a table
        $count = count($result);
        Firefly::print(Firefly::color("Migrations status ($count): ", 'yellow'));
        Firefly::print('');
        Firefly::table(['Name', 'Status'], $result);
        return true;
    }

    /**
     * Squashes the migrations into a schema file.
     * @return bool True on success, false on failure.
     */
    public static function squash()
    {
        // Gets the connection name
        $connection = Firefly::getArg('connection', 'default');

        // Gets the driver type
        $driver = Config::get("database.$connection.driver", 'mysql');
        if ($driver !== 'mysql') throw new ConsoleException(Firefly::getCommand(), Firefly::getArgs(), 'Squashing is only available for mysql driver');

        // Gets the migrations for this connection
        $pendingMigrations = [];
        $appliedMigrations = [];

        foreach (glob(Util::location('migrations/*.php')) as $filename) {
            // Gets the migration class name
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $classname = 'Glowie\Migrations\\' . $name;
            if (!class_exists($classname)) continue;

            // Instantiates the migration class
            $migration = new $classname;
            if ($migration->getDatabase() !== $connection) continue;

            // Checks if the migration was applied
            if ($migration->isApplied()) {
                $appliedMigrations[] = $filename;
            } else {
                $pendingMigrations[] = $name;
            }
        }

        // Checks for pending migrations
        if (count($pendingMigrations)) {
            Firefly::print(Firefly::color('[' . date('Y-m-d H:i:s') . ']' . ' Unable to squash, there are pending migrations to be applied.', 'yellow'));
            return false;
        }

        // Gets the tables from the database
        $db = new Kraken('glowie', $connection);
        $tables = $db->query('SHOW TABLES');
        if (empty($tables)) throw new ConsoleException(Firefly::getCommand(), Firefly::getArgs(), "There are no tables on the database \"$connection\"");

        // Maps the table names to the next query
        $tables = collect($tables)->map(function ($row) {
            return $row->toCollection()->values()->first();
        });

        // Gets the CREATE script for each table
        $queries = [];
        foreach ($tables as $table) {
            $query = $db->query("SHOW CREATE TABLE `$table`");

            if (!empty($query[0])) {
                $queries[] = Util::replaceFirst($query[0]->get('Create Table'), 'CREATE TABLE', 'CREATE TABLE IF NOT EXISTS');
            }
        }

        // Gets the data for the migrations table, if exists
        $table = Config::get('migrations.table', 'migrations');
        if ($tables->contains($table)) {
            $data = $db->table($table)->fetchAll();

            // Fetches the migrations table values
            if (!empty($data)) {
                $values = [];

                foreach ($data as $row) {
                    $name = $db->escape($row->name);
                    $applied_at = $db->escape($row->applied_at);
                    $values[] = "($name, $applied_at)";
                }

                $values = implode(', ', $values);
                $queries[] = "INSERT IGNORE INTO `$table` (`name`, `applied_at`) VALUES $values";
            }
        }

        // Prepares the dump header
        $charset = $db->query('SELECT @@character_set_client as `charset`')[0]->charset ?? 'utf8mb4';
        $header = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n" .
            "/*!40101 SET NAMES $charset */;\n" .
            "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n" .
            "/*!40103 SET TIME_ZONE='+00:00' */;\n" .
            "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n" .
            "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n" .
            "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\nSET AUTOCOMMIT = 0;\nSTART TRANSACTION;\n\n";

        // Prepares the dump footer
        $footer = ";\n\nCOMMIT;\n\n/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;\n" .
            "/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;\n" .
            "/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;\n" .
            "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n" .
            "/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;";

        // Join the queries
        $result = $header . implode(";\n\n", $queries) . $footer;

        // Writes the result to the schema file
        $path = Util::location("migrations/$connection.sql");
        if (!file_put_contents($path, $result)) throw new ConsoleException(Firefly::getCommand(), Firefly::getArgs(), "Failed to write to file \"$path\"");

        // Deletes the applied migrations
        foreach ($appliedMigrations as $filename) unlink($filename);

        // Prints the result
        Firefly::print(Firefly::color('[' . date('Y-m-d H:i:s') . ']' . ' Migrations squashed successfully.', 'green'));
        return true;
    }
}
