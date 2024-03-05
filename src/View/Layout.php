<?php
    namespace Glowie\Core\View;

    use Glowie\Core\Http\Rails;
    use Glowie\Core\Traits\ElementTrait;
    use Glowie\Core\Exception\FileException;
    use Config;
    use Util;
    use BadMethodCallException;
    use JsonSerializable;

    /**
     * Layout core for Glowie application.
     * @category Layout
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     */
    class Layout implements JsonSerializable{
        use ElementTrait;

        /**
         * Layout content.
         * @var string
         */
        private $_content;

        /**
         * Internal view content
         * @var string
         */
        private $_view = '';

        /**
         * Layout original filename.
         * @var string
         */
        private $_filename;

        /**
         * View helpers instance.
         * @var Helpers
         */
        private static $_helpers;

        /**
         * Layout local parameters.
         * @var array
         */
        private $_params;

        /**
         * Instantiates a new Layout.
         * @param string $layout Layout filename to instantiate.
         * @param string|null $view (Optional) View filename to parse inside the layout.
         * @param array $params (Optional) View parameters to parse.
         */
        public function __construct(string $layout, ?string $view = null, array $params = [], bool $absolute = false){
            // Save original filename
            $this->_filename = $layout;
            $layout = !$absolute ? Util::location('views/layouts/' . $layout) : $layout;
            $layout .= !Util::endsWith($layout, '.phtml') ? '.phtml' : '';
            if(!is_file($layout)) throw new FileException(sprintf('Layout file "%s" not found', $this->_filename));

            // Instantiate helpers
            $helpers = 'Glowie\Helpers\Helpers';
            if(!self::$_helpers && is_file(Util::location('views/helpers/Helpers.php'))) self::$_helpers = new $helpers;

            // Parse parameters
            $this->_params = $params;
            $globalParams = Rails::getController()->view->toArray();
            $this->__constructTrait(array_merge($globalParams, $this->_params));

            // Parse view
            if(!empty($view)){
                $view = new View($view, $this->_params, false, $absolute);
                $this->_view = $view->getContent();
            }

            // Render layout
            if(Config::get('skeltch.enabled', true)) $layout = Skeltch::run($layout);
            $this->_content = $this->getBuffer($layout);

            // Add to rendered views
            View::addRendered($this->_filename, $this->toArray());
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
                throw new BadMethodCallException(sprintf('Helper method "%s()" is not defined (Layout: "%s")', $method, $this->_filename));
            }
        }

        /**
         * Gets a layout buffer.
         * @param string $path Layout filename to include.
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
         * Renders a raw view code using Skeltch engine.
         * @param string $view View content in HTML.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         */
        final public function renderInline(string $content, array $params = []){
            Rails::getController()->renderInline($content, $params);
        }

        /**
         * Returns the layout content as string.
         * @return string Layout content.
         */
        public function getContent(){
            return $this->_content;
        }

        /**
         * Returns the internal view content as string.
         * @return string View content.
         */
        public function getView(){
            return $this->_view;
        }

        /**
         * Starts a layout block.
         * @param string $name Block name.
         */
        public static function startBlock(string $name){
            View::startBlock($name);
        }

        /**
         * Finishes a layout block.
         */
        public static function endBlock(){
            View::endBlock();
        }

        /**
         * Gets a block content.
         * @param string $name Block name.
         * @param string $default (Optional) Default content to return.
         * @return string Returns the block content or the default if block is not found.
         */
        public static function getBlock(string $name, string $default = ''){
            return View::getBlock($name, $default);
        }

        /**
         * Pushes content to a layout stack.
         * @param string $name Stack name.
         */
        public static function pushStack(string $name){
            View::pushStack($name);
        }

        /**
         * Prepends content to the start of a layout stack.
         * @param string $name Stack name.
         */
        public static function prependStack(string $name){
            View::prependStack($name);
        }

        /**
         * Finishes a layout stack.
         */
        public function endStack(){
            View::endStack();
        }

        /**
         * Gets a stack content.
         * @param string $name Stack name.
         * @param string $default (Optional) Default content to return.
         * @return string Returns the stack content or the default if block is not found.
         */
        public function getStack(string $name, string $default = ''){
            return View::getStack($name, $default);
        }

    }

?>