<?php
    namespace Glowie\Core\View;

    use Glowie\Core\Http\Rails;
    use Glowie\Core\Traits\ElementTrait;
    use Glowie\Core\View\Buffer;
    use Glowie\Core\Exception\FileException;
    use Config;
    use Util;
    use BadMethodCallException;
    use Exception;
    use JsonSerializable;

    /**
     * View core for Glowie application.
     * @category View
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class View implements JsonSerializable{
        use ElementTrait;

        /**
         * View content.
         * @var string
         */
        private $_content;

        /**
         * View original filename.
         * @var string
         */
        private $_filename;

        /**
         * View helpers instance.
         * @var Helpers
         */
        private static $_helpers;

        /**
         * View local parameters.
         * @var array
         */
        private $_params;

        /**
         * View blocks.
         * @var array
         */
        private static $_blocks;

        /**
         * Current block name.
         * @var string
         */
        private static $_block;

        /**
         * View stacks.
         * @var array
         */
        private static $_stacks;

        /**
         * Current stack name.
         * @var string
         */
        private static $_stack;

        /**
         * If stack should be prepended instead of pushed to end.
         * @var bool
         */
        private static $_prependStack = false;

        /**
         * Instantiates a new View.
         * @param string $view View filename to instantiate.
         * @param array $params (Optional) View parameters to parse.
         * @param bool $partial (Optional) Restrict view partial scope.
         */
        public function __construct(string $view, array $params = [], bool $partial = false, bool $absolute = false){
            // Validate file
            $this->_filename = $view;
            $view = !$absolute ? Util::location('views/' . $view) : $view;
            $view .= !Util::endsWith($view, '.phtml') ? '.phtml' : '';
            if(!is_file($view)) throw new FileException(sprintf('View file "%s" not found', $this->_filename));

            // Instantiate helpers
            $helpers = 'Glowie\Helpers\Helpers';
            if(!self::$_helpers && is_file(Util::location('views/helpers/Helpers.php'))) self::$_helpers = new $helpers;

            // Parse parameters
            $this->_params = $params;
            $globalParams = !$partial ? Rails::getController()->view->toArray() : [];
            $this->__constructTrait(array_merge($globalParams, $this->_params));

            // Render view
            if(Config::get('skeltch.enabled', true)) $view = Skeltch::run($view);
            $this->_content = $this->getBuffer($view);
        }

        /**
         * Calls a helper method dynamically.
         * @param mixed $method Method to be called from `Glowie\Helpers\Helpers` class.
         * @param mixed $args Arguments to pass to the method.
         */
        public function __call($method, $args){
            if(is_callable([self::$_helpers, $method])){
                return call_user_func_array([self::$_helpers, $method], $args);
            }else{
                throw new BadMethodCallException('Helper method "' . $method .'()" is not defined (View: "' . $this->_filename . '")');
            }
        }

        /**
         * Gets a view buffer.
         * @param string $path View filename to include.
         * @return string The buffer contents as string.
         */
        private function getBuffer(string $path){
            Buffer::start();
            include($path);
            return Buffer::get();
        }

        /**
         * Renders a view file.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         * @param bool $absolute (Optional) Use an absolute path for the view file.
         */
        public function renderView(string $view, array $params = [], bool $absolute = false){
            Rails::getController()->renderView($view, array_merge($this->_params, $params), $absolute);
        }

        /**
         * Renders a layout file.
         * @param string $layout Layout filename. Must be a **.phtml** file inside **app/views/layouts** folder, extension is not needed.
         * @param string|null $view (Optional) View filename to render within layout. You can place its content by using `$this->getView()`\
         * inside the layout file. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the rendered view and layout. Should be an associative array with each variable name and value.
         * @param bool $absolute (Optional) Use an absolute path for the view file.
         */
        public function renderLayout(string $layout, ?string $view = null, array $params = [], bool $absolute = false){
            Rails::getController()->renderLayout($layout, $view, array_merge($this->_params, $params), $absolute);
        }

        /**
         * Renders a view file in a private scope. No global or parent view properties will be inherited.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         * @param bool $absolute (Optional) Use an absolute path for the view file.
         */
        public function renderPartial(string $view, array $params = [], bool $absolute = false){
            Rails::getController()->renderPartial($view, $params, $absolute);
        }

        /**
         * Returns the view content as string.
         * @return string View content.
         */
        public function getContent(){
            return $this->_content;
        }

        /**
         * Starts a layout block.
         * @param string $name Block name.
         */
        public static function startBlock(string $name){
            if(self::$_block) throw new Exception('startBlock(): Block is already started');
            self::$_block = $name;
            Buffer::start();
        }

        /**
         * Finishes a layout block.
         */
        public static function endBlock(){
            if(!self::$_block) throw new Exception('endBlock(): No block was started');
            self::$_blocks[self::$_block] = Buffer::get();
            self::$_block = null;
        }

        /**
         * Gets a block content.
         * @param string $name Block name.
         * @param string $default (Optional) Default content to return.
         * @return string Returns the block content or the default if block is not found.
         */
        public static function getBlock(string $name, string $default = ''){
            return self::$_blocks[$name] ?? $default;
        }

        /**
         * Pushes content to a layout stack.
         * @param string $name Stack name.
         */
        public static function pushStack(string $name){
            if(self::$_stack) throw new Exception('pushStack(): Stack is already started');
            self::$_stack = $name;
            self::$_prependStack = false;
            Buffer::start();
        }

        /**
         * Prepends content to the start of a layout stack.
         * @param string $name Stack name.
         */
        public static function prependStack(string $name){
            if(self::$_stack) throw new Exception('pushStack(): Stack is already started');
            self::$_stack = $name;
            self::$_prependStack = true;
            Buffer::start();
        }

        /**
         * Finishes a layout stack.
         */
        public static function endStack(){
            if(!self::$_stack) throw new Exception('endStack(): No stack was started');
            if(empty(self::$_stacks[self::$_stack])) self::$_stacks[self::$_stack] = [];
            $content = Buffer::get();
            if(self::$_prependStack){
                array_unshift(self::$_stacks[self::$_stack], $content);
            }else{
                self::$_stacks[self::$_stack][] = $content;
            }
            self::$_stack = null;
        }

        /**
         * Gets a stack content.
         * @param string $name Stack name.
         * @param string $default (Optional) Default content to return.
         * @return string Returns the stack content or the default if block is not found.
         */
        public static function getStack(string $name, string $default = ''){
            if(empty(self::$_stacks[$name])) return $default;
            return implode(PHP_EOL, self::$_stacks[$name]);
        }

    }

?>