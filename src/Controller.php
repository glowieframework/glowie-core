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
         * @param bool $skeltch (Optional) Use Skeltch preprocessor to compile the view.
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
                        return new Template($template, $view, $params, $skeltch, $this);
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
                    return new Template($template, '', $params, $skeltch, $this);
                } else {
                    trigger_error('renderTemplate: File "' . $template . '" not found');
                    exit;
                }
            }
        }

    }

?>