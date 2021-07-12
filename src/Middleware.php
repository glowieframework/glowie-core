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
     * @version 1.0
     */
    abstract class Middleware{

        /**
         * Controller that this middleware is handling.
         * @var Controller
         */
        protected $controller;

        /**
         * Request GET parameters.
         * @var Element
         */
        protected $get;

        /**
         * URI parameters.
         * @var Element
         */
        protected $params;

        /**
         * Request POST parameters.
         * @var Element
         */
        protected $post;

        /**
         * Request handler.
         * @var Request
         */
        protected $request;

        /**
         * Response handler.
         * @var Response
         */
        protected $response;

        /**
         * Current instantiated route.
         * @var string
         */
        protected $route;

        /**
         * Session parameters.
         * @var Session
         */
        protected $session;

         /**
         * Instantiates a new instance of the middleware.
         * @param Controller $controller Referenced controller that this middleware is handling.
         * @param string $route (Optional) Request route.
         * @param array $params (Optional) Route parameters.
         */
        public function __construct(Controller &$controller, string $route = '', array $params = []){
            $this->controller = $controller;
            $this->get = new Element($_GET);
            $this->params = new Element($params);
            $this->post = new Element($_POST);
            $this->request = new Request();
            $this->response = new Response();
            $this->route = $route;
            $this->session = new Session();
        }

        /**
         * The middleware handler.
         * @return bool Should return true on success or false on fail.
         */
        abstract public function handle();
        
    }

?>