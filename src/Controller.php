<?php
    namespace Glowie;

    /**
     * Controller core for Glowie application.
     * @category Controller
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class Controller{
        /**
         * Content handler for templates.
         * @var string
         */
        public $content;

        /**
         * Request GET parameters.
         * @var \Objectify
         */
        public $get;

        /**
         * URI parameters.
         * @var \Objectify
         */
        public $params;

        /**
         * Request POST parameters.
         * @var \Objectify
         */
        public $post;

        /**
         * Request parameters.
         * @var \Objectify
         */
        public $request;

        /**
         * Current controller properties.
         * @var \Objectify
         */
        public $self;

        /**
         * Web server parameters.
         * @var \Objectify
         */
        public $server;

        /**
         * Current Glowie version.
         * @var string
         */
        public $version;

        /**
         * Data bridge between controller and view.
         * @var \Objectify
         */
        public $view;

        /**
         * Instantiates a new instance of the controller.
         */
        public function __construct(){
            // Common properties
            $this->version = '0.2-alpha';
            $this->self = new \Objectify();

            // View and template properties
            $this->content = '';
            $this->view = new \Objectify();

            // Request parameters
            $this->get = new \Objectify($_GET, true);
            $this->post = new \Objectify($_POST, true);
            $this->request = new \Objectify($_REQUEST, true);
            $this->server = new \Objectify($_SERVER, true);

            // URI parameters
            $this->params = new \Objectify();
        }

        /**
         * Renders a view file.
         * @param string $view View filename without extension. Must be a **.phtml** file inside **views** folder.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with\
         * each variable name and value.
         * @param bool $skeltch (Optional) Use Skeltch preprocessor to compile the view.
         */
        public function renderView(string $view, array $params = [], bool $skeltch = false){
            if(!is_array($params)) trigger_error('renderView: $params must be an array');
            $view = '../views/' . $view . '.phtml';
            if(file_exists($view)){
                if(!empty($params)) extract($params);
                if($skeltch) $view = Skeltch::run($view);
                ob_start();
                require($view);
                echo ob_get_clean();
            }else{
                trigger_error('renderView: File "' . $view . '" not found');
                exit;
            }
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
            if (!is_array($params)) trigger_error('renderTemplate: $params must be an array');
            $template = '../views/templates/' . $template . '.phtml';
            if(!empty($view)){
                $view = '../views/' . $view . '.phtml';
                if (file_exists($template)) {
                    if(file_exists($view)){
                        // View
                        if (!empty($params)) extract($params);
                        if($skeltch) $view = Skeltch::run($view);
                        ob_start();
                        require($view);
                        $this->content = ob_get_clean();

                        // Template
                        if($skeltch) $template = Skeltch::run($template);
                        ob_start();
                        require($template);
                        echo ob_get_clean();
                    }else{
                        trigger_error('renderTemplate: File "' . $view . '" not found');
                        exit;
                    }
                } else {
                    trigger_error('renderTemplate: File "' . $template . '" not found');
                    exit;
                }
            }else{
                if (file_exists($template)) {
                    if (!empty($params)) extract($params);
                    if($skeltch) $template = Skeltch::run($template);
                    $this->content = '';
                    ob_start();
                    require($template);
                    echo ob_get_clean();
                } else {
                    trigger_error('renderTemplate: File "' . $template . '" not found');
                    exit;
                }
            }
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