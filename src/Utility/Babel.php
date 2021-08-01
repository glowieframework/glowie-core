<?php

    use Glowie\Core\Exception\FileException;

    /**
     * Internationalization helper for Glowie application.
     * @category Internationalization
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
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
         * Sets the current active language configuration.
         * @param string $lang Language identificator to set as active.
         */
        public static function setActiveLanguage(string $lang){
            self::$active_language = $lang;
        }

        /**
         * Gets the current active language configuration.
         * @return string Active language identificator.
         */
        public static function getActiveLanguage(){
            return self::$active_language;
        }

        /**
         * Sets an array of internationalization strings to a language configuration.\
         * **Warning:** This replaces all current language defined strings.
         * @param string $lang Language identificator to set strings.
         * @param array $strings Associative array of strings with key and value.
         */
        public static function set(string $lang, array $strings){
            self::$languages[$lang] = $strings;
        }

        /**
         * Sets an internationalization string in a specific key in a language configuration.
         * @param string $lang Language identificator to set string.
         * @param string $key String key to set.
         * @param string $string Internationalization string to set.
         */
        public static function setString(string $lang, string $key, string $string){
            if(empty(self::$languages[$lang])) self::$languages[$lang] = [];
            self::$languages[$lang][$key] = $string;
        }

        /**
         * Gets an internalization string from a language configuration.
         * @param string $key String key to get.
         * @param string $lang (Optional) Language identificator to get string from. Leave empty to use the current active language.
         * @return string|null Returns internationalization string or null if not found.
         */
        public static function get(string $key, string $lang = ''){
            // Checks if languages were defined
            if(!empty(self::$languages)){
                // Parses active language
                if(empty($lang)) $lang = self::$active_language;

                // Checks if specified language was defined
                if(isset(self::$languages[$lang])){
                    // Returns the value
                    return self::$languages[$lang][$key] ?? null;
                }else{
                    throw new FileException('Babel: Language "'.$lang.'" does not exist in "app/languages"');
                }
            }else{
                throw new FileException('Babel: Language configuration not found in "app/languages"');
            }
        }

    }

?>