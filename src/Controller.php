<?php
    namespace Glowie\Core;

    /**
     * Controller core for Glowie application.
     * @category Controller
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.3-alpha
     */
    class Controller{
        /** 
         * Application flow properties.
         * @var Objectify
         */
        public $flow;
        
        /**
         * Request GET parameters.
         * @var Objectify
         */
        public $get;

        /**
         * URI parameters.
         * @var Objectify
         */
        public $params;

        /**
         * Request POST parameters.
         * @var Objectify
         */
        public $post;

        /**
         * Request parameters.
         * @var Objectify
         */
        public $request;

        /**
         * Web server parameters.
         * @var Objectify
         */
        public $server;

        /**
         * Data bridge between controller and view.
         * @var Objectify
         */
        public $view;

        /**
         * Instantiates a new instance of the controller.
         */
        public function __construct(){
            // Set properties
            $this->flow = new Objectify();
            $this->get = new Objectify($_GET);
            $this->params = new Objectify();
            $this->post = new Objectify($_POST);
            $this->request = new Objectify($_REQUEST);
            $this->server = new Objectify($_SERVER);
            $this->view = new Objectify();
        }

        /**
         * Renders a view file.
         * @param string $view View filename without extension. Must be a **.phtml** file inside **views** folder.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with\
         * each variable name and value.
         * @param bool $skeltch (Optional) Use Skeltch templating engine to compile the view.
         */
        public function renderView(string $view, array $params = [], bool $skeltch = false){
            if(!is_array($params)) trigger_error('renderView: $params must be an array');
            $view = '../views/' . $view . '.phtml';
            if(file_exists($view)){
                return new View($view, $params, $skeltch, true, $this);
            }else{
                trigger_error('renderView: File "' . $view . '" not found');
                exit;
            }
        }

        /**
         * Renders a layout file.
         * @param string $layout Layout filename without extension. Must be a **.phtml** file inside **views/layouts** folder.
         * @param string $view (Optional) View filename to render within layout. You can place this view by using **$this->content**\
         * in the layout file. Must be a **.phtml** file inside **views** folder.
         * @param array $params (Optional) Parameters to pass into the rendered view or layout. Should be an associative array with\
         * each variable name and value.
         * @param bool $skeltch (Optional) Use Skeltch templating engine to compile the layout and view.
         */
        public function renderLayout(string $layout, string $view = '', array $params = [], bool $skeltch = false){
            if (!is_array($params)) trigger_error('renderLayout: $params must be an array');
            $layout = '../views/layouts/' . $layout . '.phtml';
            if(!empty($view)){
                $view = '../views/' . $view . '.phtml';
                if (file_exists($layout)) {
                    if(file_exists($view)){
                        return new Layout($layout, $view, $params, $skeltch, $this);
                    }else{
                        trigger_error('renderLayout: File "' . $view . '" not found');
                        exit;
                    }
                } else {
                    trigger_error('renderLayout: File "' . $layout . '" not found');
                    exit;
                }
            }else{
                if (file_exists($layout)) {
                    return new Layout($layout, '', $params, $skeltch, $this);
                } else {
                    trigger_error('renderLayout: File "' . $layout . '" not found');
                    exit;
                }
            }
        }

    }

?>