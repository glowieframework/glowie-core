<?php

    /**
     * Internationalization helper for Glowie application.
     * @category Internationalization
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class Babel{

        /**
         * Sets the current active language configuration.
         * @param string $lang Language to set as active.
         */
        public static function setActiveLanguage(string $lang){
            $GLOBALS['glowieLang']['active'] = $lang;
        }

        /**
         * Sets an array of internationalization strings to a language configuration.\
         * **Warning:** This replaces all current language defined strings.
         * @param string $lang Language to set strings.
         * @param array $strings Associative array of strings with key and value.
         */
        public static function set(string $lang, array $strings){
            $GLOBALS['glowieLang']['languages'][$lang] = $strings;
        }

        /**
         * Sets an internationalization string in a specific key in a language configuration.
         * @param string $lang Language to set string.
         * @param string $key String key to set.
         * @param string $string Internationalization string to set.
         */
        public static function setLine(string $lang, string $key, string $string){
            if(empty($GLOBALS['glowieLang']['languages'][$lang])) $GLOBALS['glowieLang']['languages'][$lang] = [];
            $GLOBALS['glowieLang']['languages'][$lang][$key] = $string;
        }

        /**
         * Gets an internalization string from a language configuration.
         * @param string $key (Optional) String key to get. Leave empty to get an array with all strings.
         * @param string $lang (Optional) Language to get string from. Leave empty to use the current active language.
         * @return mixed Returns internationalization string (or an associative array if key is empty) or null if not found.
         */
        public static function get(string $key = '', string $lang = ''){
            // Checks if languages were defined
            if(!empty($GLOBALS['glowieLang']['languages'])){
                // Parses active language
                if(empty($lang)) $lang = $GLOBALS['glowieLang']['active'];

                // Checks if specified language was defined
                if(!empty($GLOBALS['glowieLang']['languages'][$lang])){
                    // Checks if key exists
                    if(empty($key)) return $GLOBALS['glowieLang']['languages'][$lang];
                    if(!empty($GLOBALS['glowieLang']['languages'][$lang][$key])){
                        return $GLOBALS['glowieLang']['languages'][$lang][$key];
                    }else{
                        return null;
                    }
                }else{
                    trigger_error('Babel: Language "'.$lang.'" not found');
                }
            }else{
                trigger_error('Babel: Language configuration not found');
            }
        }

    }

?>