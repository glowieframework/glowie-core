<?php
    namespace Glowie\Core\View;

    use Glowie\Core\Http\Rails;
    use Glowie\Core\Traits\ElementTrait;
    use Glowie\Core\View\Buffer;
    use Config;
    use BadMethodCallException;

    /**
     * View core for Glowie application.
     * @category View
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class View{
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
         * Instantiates a new View object.
         * @param string $view View filename to instantiate.
         * @param array $params View parameters to parse.
         * @param bool $parse Immediately parse view content.
         */
        public function __construct(string $view, array $params, bool $parse){
            // Save original filename
            $this->_filename = str_replace(['../views/', '.phtml'], '', $view);

            // Instantiate helpers
            $helpers = 'Glowie\Helpers\Helpers';
            if(!self::$_helpers) self::$_helpers = new $helpers;

            // Parse parameters
            $this->_params = $params;
            $globalParams = Rails::getController()->view->toArray();
            $this->__constructTrait(array_merge($globalParams, $this->_params));

            // Render view
            if(Config::get('skeltch.enabled', true)) $view = Skeltch::run($view);
            $this->_content = $this->getBuffer($view);
            if($parse) echo $this->_content;
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
         * @return void
         */
        public function renderView(string $view, array $params = []){
            return Rails::getController()->renderView($view, array_merge($this->_params, $params));
        }

        /**
         * Renders a layout file.
         * @param string $layout Layout filename. Must be a **.phtml** file inside **app/views/layouts** folder, extension is not needed.
         * @param string $view (Optional) View filename to render within layout. You can place its content by using `$this->getContent()`\
         * inside the layout file. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the rendered view and layout. Should be an associative array with each variable name and value.
         * @return void
         */
        public function renderLayout(string $layout, string $view = '', array $params = []){
            return Rails::getController()->renderLayout($layout, $view, array_merge($this->_params, $params));
        }

        /**
         * Returns the view content as string.
         * @return string View content.
         */
        public function getContent(){
            return $this->_content;
        }

    }

?>