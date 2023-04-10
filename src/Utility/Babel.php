<?php
    use Glowie\Core\Exception\i18nException;

    /**
     * Internationalization helper for Glowie application.
     * @category Internationalization
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class Babel{

        /**
         * Active language configuration.
         * @var string
         */
        private static $activeLanguage;

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

            // Sets the default language
            self::setActiveLanguage(Config::get('other.language', 'en'));
        }

        /**
         * Sets the current active language configuration.
         * @param string $lang Language name to set as active.
         */
        public static function setActiveLanguage(string $lang){
            self::$activeLanguage = $lang;
        }

        /**
         * Gets the current active language configuration.
         * @return string Active language name.
         */
        public static function getActiveLanguage(){
            return self::$activeLanguage;
        }

        /**
         * Gets an internalization string from a language configuration.
         * @param string $key String key to get (accepts dot notation keys).
         * @param array $params (Optional) Associative array of parameters to bind into the string.
         * @param string|null $lang (Optional) Language name to get string from. Leave empty to use the current active language.
         * @param string $default (Optional) Default value to return if the key is not found.
         * @return string Returns internationalization string if found or the default value if not.
         */
        public static function get(string $key, array $params = [], ?string $lang = null, string $default = ''){
            // Parses active language
            if(!$lang) $lang = self::$activeLanguage;

            // Checks if specified language was defined
            if(empty(self::$languages[$lang])) throw new i18nException('Language "' . $lang . '" does not exist in "app/languages"');

            // Get string
            $result = Util::arrayGet(self::$languages[$lang], $key, $default);

            // Parse parameters
            if(!empty($params) && !empty($result)){
                foreach($params as $key => $value){
                    $result = preg_replace('~(?<!\\\):' . preg_quote($key) . '~i', $value, $result);
                    $result = preg_replace('~\\\:' . preg_quote($key) . '~i', ':' . $key, $result);
                }
            }

            // Return result
            return $result;
        }

    }

?>