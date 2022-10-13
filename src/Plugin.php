<?php
    namespace Glowie\Core;

    use Util;

    /**
     * Plugin core for Glowie application.
     * @category Plugin
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
     */
    abstract class Plugin{

        /**
         * Array of files to be published to the app folder.
         * @var array
         */
        protected $files = [];

        /**
         * Publishes the plugin files.
         * @param bool $force (Optional) Overwrite existing files.
         */
        final public function publish(bool $force = false){
            foreach($this->files as $origin => $target){
                if(!is_dir($origin)){
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
        private function copyFile(string $origin, string $target, bool $force){
            // Get real app location path
            $target = Util::location($target);

            // Check if base folder exists
            $dir = pathinfo($target, PATHINFO_DIRNAME);
            if(!is_dir($dir)) mkdir($dir, 0755, true);

            // Copy the file
            if($force || !is_file($target)) copy($origin, $target);
        }

        /**
         * Initializes the plugin.
         */
        public abstract function register();
    }

?>