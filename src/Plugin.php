<?php

namespace Glowie\Core;

use Util;
use Glowie\Core\Exception\FileException;

/**
 * Plugin core for Glowie application.
 * @category Plugin
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
abstract class Plugin
{

    /**
     * Array of files and directories to be published to the app folder.
     * @var array
     */
    protected $files = [];

    /**
     * Publishes the plugin files.
     * @param bool $force (Optional) Overwrite existing files.
     */
    final public function publish(bool $force = false)
    {
        foreach ($this->files as $origin => $target) {
            if (is_dir($origin)) {
                $files = Util::getFiles($origin . '/*');
                foreach ($files as $file) {
                    if (!is_file($file)) continue;
                    $fileTarget = $target . Util::replaceFirst($file, $origin, '');
                    $this->copyFile($file, $fileTarget, $force);
                }
            } else if (is_file($origin)) {
                $this->copyFile($origin, $target, $force);
            }
        }
    }

    /**
     * Copies a file to the application folder.
     * @param string $origin Origin filename.
     * @param string $target Target filename.
     * @param bool $force Overwrite existing files.
     */
    private function copyFile(string $origin, string $target, bool $force)
    {
        // Get real app location path
        $target = Util::location($target);

        // Check if base folder exists
        $dir = pathinfo($target, PATHINFO_DIRNAME);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!is_writable($dir)) throw new FileException('Directory ' . $dir . ' is not writable, please check your chmod settings');

        // Copy the file
        if ($force || !is_file($target)) copy($origin, $target);
    }

    /**
     * Initializes the plugin.
     */
    public abstract function register();
}
