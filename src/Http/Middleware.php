<?php
    namespace Glowie\Core\Http;

    use Glowie\Core\Element;

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
         * Current requested route.
         * @var string
         */
        protected $route;

        /**
         * Session parameters.
         * @var Session
         */
        protected $session;

         /**
         * Creates a new instance of the middleware.
         * @param string $route (Optional) Requested route.
         * @param array $params (Optional) Route parameters.
         */
        final public function __construct(string $route = '', array $params = []){
            $this->controller = Rails::getController();
            $this->get = new Element($_GET);
            $this->params = new Element($params);
            $this->post = new Element($_POST);
            $this->request = Rails::getRequest();
            $this->response = Rails::getResponse();
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