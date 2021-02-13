<?php

    /**
     * Internationalization helper for Glowie application.
     * @category Internationalization
     * @package glowie
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class Babel{

        /**
         * Gets an internalization string from a language configuration.
         * @param string $key String key to get.
         * @param string $lang (Optional) Language to get string from.
         * @return string|null Returns internationalization string or null if not found.
         */
        public static function getTranslation(string $key, string $lang = 'en'){
            if(isset($GLOBALS['glowieLang']) && !empty($GLOBALS['glowieLang'])){
                if(isset($GLOBALS['glowieLang'][$lang]) && !empty($GLOBALS['glowieLang'][$lang])){
                    if(isset($GLOBALS['glowieLang'][$lang][$key])){
                        return $GLOBALS['glowieLang'][$lang][$key];
                    }else{
                        return null;
                    }
                }else{
                    trigger_error('Language "'.$lang.'" not found');
                }
            }else{
                trigger_error('Language configuration not found');
            }
        }

    }

?>