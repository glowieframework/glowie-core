<?php
    namespace Glowie;

    /**
     * View core for Glowie application.
     * @category View
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class View extends Objectify{
        /**
         * View content.
         * @var string
         */
        public $content;

        /**
         * Controller that instantiated this view.
         * @var Controller
         */
        public $controller;

        /**
         * View file path.
         * @var string
         */
        public $path;
        
        /**
         * Instantiates a new View object.
         * @param string $view View filename to instantiate.
         * @param array $params View parameters to parse.
         * @param bool $skeltch Preprocess view using Skeltch.
         * @param Controller $controller Current controller.
         */
        public function __construct(string $view, array $params, bool $skeltch, bool $parse, Controller $controller){
            // Parse parameters
            $this->controller = $controller;
            $this->path = $view;
            $controller = get_object_vars($this->controller->view);
            if(!empty($controller)) foreach ($controller as $key => $value) $this->$key = $value;
            if(!empty($params)) foreach($params as $key => $value) $this->$key = $value;
            
            // Render view
            if($skeltch) $this->path = Skeltch::run($this->path);
            $this->content = $this->getBuffer();
            if($parse) echo $this->content;
        }

        /**
         * Gets a view buffer.
         * @return string The buffer contents as string.
         */
        private function getBuffer(){
            ob_start();
            require($this->path);
            return ob_get_clean();
        }

        /**
         * Renders a view file.
         * @param string $view View filename without extension. Must be a **.phtml** file inside **views** folder.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with\
         * each variable name and value.
         * @param bool $skeltch (Optional) Use Skeltch preprocessor to compile the view.
         */
        public function renderView(string $view, array $params = [], bool $skeltch = false){
            $this->controller->renderView($view, $params, $skeltch);
        }

        /**
         * Renders a template file.
         * @param string $template Template filename without extension. Must be a **.phtml** file inside **views/templates** folder.
         * @param string $view (Optional) View filename to render within template. You can place this view by using **$this->content**\
         * in the template file. Must be a **.phtml** file inside **views** folder.
         * @param array $params (Optional) Parameters to pass into the rendered view or template. Should be an associative array with\
         * each variable name and value.
         * @param bool $skeltch (Optional) Use Skeltch preprocessor to compile the template and view.
         */
        public function renderTemplate(string $template, string $view = '', array $params = [], bool $skeltch = false){
            $this->controller->renderTemplate($template, $view, $params, $skeltch);
        }

        /**
         * Returns the page rendering time.
         * @return float Page rendering time.
         */
        public function getRenderTime(){
            return round((microtime(true) - $GLOBALS['glowieTimer']), 5);
        }

    }
?>