<?php
    namespace Glowie\Core\View;

    use Glowie\Core\Http\Rails;
    use Glowie\Core\Traits\ElementTrait;
    use Glowie\Core\Config;
    use BadMethodCallException;

    /**
     * Layout core for Glowie application.
     * @category Layout
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Layout{
        use ElementTrait;

        /**
         * Layout view content.
         * @var string
         */
        private $_content;

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
         * Instantiates a new Layout object.
         * @param string $layout Layout filename to instantiate.
         * @param string $view View filename to parse inside the layout.
         * @param array $params View parameters to parse.
         */
        public function __construct(string $layout, string $view, array $params){
            // Save original filename
            $this->_filename = str_replace(['../views/layouts/', '.phtml'], '', $layout);

            // Instantiate helpers
            $helpers = 'Glowie\Helpers\Helpers';
            if(!self::$_helpers) self::$_helpers = new $helpers;

            // Parse parameters
            $this->_params = $params;
            $globalParams = Rails::getController()->view->toArray();
            $params = array_merge($globalParams, $this->_params);
            if(!empty($params)) foreach($params as $key => $value) $this->{$key} = $value;

            // Parse view
            if(!empty($view)){
                $view = new View($view, $this->_params, false);
                $this->_content = $view->getContent();
            }

            // Render layout
            if(Config::get('skeltch.enabled', true)) $layout = Skeltch::run($layout);
            include($layout);
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
                throw new BadMethodCallException('Helper method "' . $method .'()" is not defined (Layout: "' . $this->_filename . '")');
            }
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
         * Returns the layout view content as string.
         * @return string View content.
         */
        public function getContent(){
            return $this->_content;
        }

    }

?>