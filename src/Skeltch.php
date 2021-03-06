<?php
    namespace Glowie\Core;

    /**
     * Templating engine for Glowie application.
     * @category Templating engine
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Skeltch{

        /**
         * Runs Skeltch view preprocessor.
         * @param string $filename View to process.
         * @return string Returns the processed file location.
         */
        public static function run(string $filename){
            // Checks for file and cache folder permissions
            $tmpdir = '../storage/cache/';
            if(!is_writable($tmpdir)) trigger_error('Skeltch: Directory "app/storage/cache" is not writable, please check your chmod settings', E_USER_ERROR);

            // Checks if cache is enabled or should be recompiled
            $tmpfile = $tmpdir . md5($filename) . '.tmp';
            if(!GLOWIE_CONFIG['cache'] || !file_exists($tmpfile) || filemtime($tmpfile) < filemtime($filename)) self::compile($filename, $tmpfile);

            // Returns the processed file location
            return $tmpfile;
        }

        /**
         * Compiles a view into a temporary file.
         * @param string $filename View to compile.
         * @param string $target Target file location.
         */
        private static function compile(string $filename, string $target){
            $code = file_get_contents($filename);
            $code = self::compileEchos($code);
            $code = self::compileLoops($code);
            $code = self::compileIfs($code);
            $code = self::compileFunctions($code);
            $code = self::compilePHP($code);
            $code = self::compileComments($code);
            $code = self::compileIgnores($code);
            file_put_contents($target, $code);
        }

        /**
         * Compiles conditional statements.\
         * example: `{if($condition)}` | `{/if}`
         * @param string $code Code to compile.
         * @return string Returns the compiled code.
         */
        private static function compileIfs(string $code){
            $code = preg_replace('~(?<!@){\s*if\s*\((.+?)\)\s*}~is', '<?php if($1): ?>', $code);
            $code = preg_replace('~(?<!@){\s*isset\s*\((.+?)\)\s*}~is', '<?php if(isset($1)): ?>', $code);
            $code = preg_replace('~(?<!@){\s*empty\s*\((.+?)\)\s*}~is', '<?php if(empty($1)): ?>', $code);
            $code = preg_replace('~(?<!@){\s*notempty\s*\((.+?)\)\s*}~is', '<?php if(!empty($1)): ?>', $code);
            $code = preg_replace('~(?<!@){\s*elseif\s*\((.+?)\)\s*}~is', '<?php elseif($1): ?>', $code);
            $code = preg_replace('~(?<!@){\s*else\s*}~is', '<?php else: ?>', $code);
            $code = preg_replace('~(?<!@){\s*(/if|/isset|/empty|/notempty|/notset)\s*}~is', '<?php endif; ?>', $code);
            return $code;
        }

        /**
         * Compiles Glowie functions.\
         * example: `{@url('/')}`
         * @param string $code Code to compile.
         * @return string Returns the compiled code.
         */
        private static function compileFunctions(string $code){
            $code = preg_replace('~(?<!@){\s*@view\s*\((.+?)\)\s*}~is', '<?php $this->renderView($1); ?>', $code);
            $code = preg_replace('~(?<!@){\s*@layout\s*\((.+?)\)\s*}~is', '<?php $this->renderLayout($1); ?>', $code);
            $code = preg_replace('~(?<!@){\s*@babel\s*\((.+?)\)\s*}~is', '<?php echo Babel::get($1); ?>', $code);
            $code = preg_replace('~(?<!@){\s*@url\s*\((.+?)\)\s*}~is', '<?php echo Util::baseUrl($1); ?>', $code);
            $code = preg_replace('~(?<!@){\s*@route\s*\((.+?)\)\s*}~is', '<?php echo Util::route($1); ?>', $code);
            $code = preg_replace('~(?<!@){\s*@content\s*}~is', '<?php echo $this->getContent(); ?>', $code);
            return $code;
        }

        /**
         * Compiles raw PHP statements.\
         * example: `{% $code %}`
         * @param string $code Code to compile.
         * @return string Returns the compiled code.
         */
        private static function compilePHP(string $code){
		    return preg_replace('~(?<!@){\s*%\s*(.+?)\s*%\s*}~is', '<?php $1 ?>', $code);
	    }

        /**
         * Compiles loop statements.\
         * example: `{foreach($variable as $key => $value)}` | `{/foreach}`
         * @param string $code Code to compile.
         * @return string Returns the compiled code.
         */
        private static function compileLoops(string $code){
            $code = preg_replace('~(?<!@){\s*foreach\s*\((.+?)\)\s*}~is', '<?php foreach($1): ?>', $code);
            $code = preg_replace('~(?<!@){\s*for\s*\((.+?)\)\s*}~is', '<?php for($1): ?>', $code);
            $code = preg_replace('~(?<!@){\s*/foreach\s*}~is', '<?php endforeach; ?>', $code);
            $code = preg_replace('~(?<!@){\s*/for\s*}~is', '<?php endfor; ?>', $code);
            $code = preg_replace('~(?<!@){\s*break\s*}~is', '<?php break; ?>', $code);
            $code = preg_replace('~(?<!@){\s*continue\s*}~is', '<?php continue; ?>', $code);
            return $code;
        }

        /**
         * Compiles echo statements.\
         * example: `{{ $var }}` | `{{!! $var !!}}`
         * @param string $code Code to compile.
         * @return string Returns the compiled code.
         */
        private static function compileEchos(string $code){
            $code = preg_replace('~(?<!@){{\s*!!\s*(.+?)\s*!!\s*}}~is', '<?php echo $1; ?>', $code);
            $code = preg_replace('~(?<!@){{\s*(.+?)\s*}}~is', '<?php echo htmlspecialchars($1); ?>', $code);
            return $code;
        }

        /**
         * Compiles comments.\
         * example: `{# this is a comment #}`
         * @param string $code Code to compile.
         * @return string Returns the compiled code.
         */
        private static function compileComments(string $code){
            return preg_replace('~(?<!@){\s*#\s*(.+?)\s*#\s*}~is', '<?php // $1 ?>', $code);
        }

        /**
         * Compiles ignored statements.
         * @param string $code Code to compile.
         * @return string Returns the compiled code.
         */
        private static function compileIgnores(string $code){
            return preg_replace('~@{~is', '{', $code);
        }

    }
?>