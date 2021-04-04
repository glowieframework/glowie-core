<?php
    namespace Glowie\Core;

    use Glowie\Helpers\Helpers;

    /**
     * View core for Glowie application.
     * @category View
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.3-alpha
     */
    class View extends Element{

        /**
         * View content.
         * @var string
         */
        public $_content;

        /**
         * Controller that instantiated this view.
         * @var Controller
         */
        public $_controller;

        /**
         * View helpers instance.
         * @var Helpers
         */
        private $_helpers;

        /**
         * View file path.
         * @var string
         */
        private $_path;
        
        /**
         * Instantiates a new View object.
         * @param string $view View filename to instantiate.
         * @param array $params View parameters to parse.
         * @param bool $skeltch Preprocess view using Skeltch.
         * @param Controller $controller Current controller.
         */
        public function __construct(string $view, array $params, bool $skeltch, bool $parse, Controller $controller){
            // Parse parameters
            $this->_controller = $controller;
            $this->_helpers = new Helpers();
            $this->_path = $view;
            $controller = get_object_vars($this->_controller->view);
            if(!empty($controller)) foreach ($controller as $key => $value) $this->$key = $value;
            if(!empty($params)) foreach($params as $key => $value) $this->$key = $value;
            
            // Render view
            if($skeltch) $this->_path = Skeltch::run($this->_path);
            $this->_content = $this->getBuffer();
            if($parse) echo $this->_content;
        }

        /**
         * Calls a helper method dynamically.
         * @param mixed $method Method to be called from the Helpers class.
         * @param mixed $args Arguments to pass to the method.
         */
        public function __call($method, $args){
            if(method_exists($this->_helpers, $method)){
                return call_user_func_array([$this->_helpers, $method], $args);
            }else{
                trigger_error('View: Method "' . $method . '" does not exist in "app/views/helpers/Helpers.php"');
            }
        }

        /**
         * Gets a view buffer.
         * @return string The buffer contents as string.
         */
        private function getBuffer(){
            ob_start();
            require($this->_path);
            return ob_get_clean();
        }

        /**
         * Renders a view file.
         * @param string $view View filename without extension. Must be a **.phtml** file inside **views** folder.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with\
         * each variable name and value.
         * @param bool $skeltch (Optional) Use Skeltch templating engine to compile the view.
         */
        public function renderView(string $view, array $params = [], bool $skeltch = false){
            $this->_controller->renderView($view, $params, $skeltch);
        }

        /**
         * Renders a layout file.
         * @param string $layout Layout filename without extension. Must be a **.phtml** file inside **views/layouts** folder.
         * @param string $view (Optional) View filename to render within layout. You can place this view by using **$this->_content**\
         * in the layout file. Must be a **.phtml** file inside **views** folder.
         * @param array $params (Optional) Parameters to pass into the rendered view or layout. Should be an associative array with\
         * each variable name and value.
         * @param bool $skeltch (Optional) Use Skeltch templating engine to compile the layout and view.
         */
        public function renderLayout(string $layout, string $view = '', array $params = [], bool $skeltch = false){
            $this->_controller->renderLayout($layout, $view, $params, $skeltch);
        }

    }
?>