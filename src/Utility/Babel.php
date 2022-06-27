<?php
    use Glowie\Core\Exception\i18nException;

    /**
     * Internationalization helper for Glowie application.
     * @category Internationalization
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
     */
    class Babel{

        /**
         * Active language configuration.
         * @var string
         */
        private static $active_language = 'en';

        /**
         * Language configurations.
         * @var array
         */
        private static $languages = [];

        /**
         * Loads the language files.
         */
        public static function load(){
            foreach(Util::getFiles(Util::location('languages/*.php')) as $file){
                $lang = pathinfo($file, PATHINFO_FILENAME);
                self::$languages[$lang] = include_once($file);
            }
        }

        /**
         * Sets the current active language configuration.
         * @param string $lang Language name to set as active.
         */
        public static function setActiveLanguage(string $lang){
            if(empty(self::$languages[$lang])) throw new i18nException('Language "' . $lang . '" does not exist in "app/languages"');
            self::$active_language = $lang;
        }

        /**
         * Gets the current active language configuration.
         * @return string Active language name.
         */
        public static function getActiveLanguage(){
            return self::$active_language;
        }

        /**
         * Gets an internalization string from a language configuration.
         * @param string $key String key to get (accepts dot notation keys).
         * @param string|null $lang (Optional) Language name to get string from. Leave empty to use the current active language.
         * @param mixed $default (Optional) Default value to return if the key is not found.
         * @return mixed Returns internationalization string if found or the default value if not.
         */
        public static function get(string $key, ?string $lang = null, $default = ''){
            // Parses active language
            if(!$lang) $lang = self::$active_language;

            // Checks if specified language was defined
            if(!empty(self::$languages[$lang])) return Util::arrayGet(self::$languages[$lang], $key, $default);

            // Language was not found
            throw new i18nException('Language "' . $lang . '" does not exist in "app/languages"');
        }

    }

?>