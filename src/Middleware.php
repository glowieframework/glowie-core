<?php
    namespace Glowie\Core;

    /**
     * Middleware core for Glowie application.
     * @category Middleware
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.3-alpha
     */
    class Middleware{

        /**
         * Controller that this middleware is handling.
         * @var Controller
         */
        public $controller;

        /**
         * Request GET parameters.
         * @var Element
         */
        public $get;

        /**
         * URI parameters.
         * @var Element
         */
        public $params;

        /**
         * Request POST parameters.
         * @var Element
         */
        public $post;

        /**
         * Request parameters.
         * @var Element
         */
        public $request;

        /**
         * Current instantiated route.
         * @var string
         */
        public $route;

        /**
         * Web server parameters.
         * @var Element
         */
        public $server;

         /**
         * Instantiates a new instance of the middleware.
         * @param Controller $controller Referenced controller that this middleware is handling.
         */
        public function __construct(Controller &$controller){
            $this->controller = $controller;
            $this->get = new Element($_GET);
            $this->params = new Element();
            $this->post = new Element($_POST);
            $this->request = new Element($_REQUEST);
            $this->server = new Element($_SERVER);
        }
        
    }

?>