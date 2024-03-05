<?php
    namespace Glowie\Core\Http;

    use Glowie\Core\Element;

    /**
     * Middleware core for Glowie application.
     * @category Middleware
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     *
     * @method void init() This method will be called before any other methods from this middleware.
     * @method void success() Called if the middleware handler returns true.
     * @method void fail() Called if the middleware handler returns false.
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
         */
        final public function __construct(){
            $this->controller = Rails::getController();
            $this->get = Rails::getRequest()->fromGet();
            $this->params = Rails::getParams();
            $this->post = Rails::getRequest()->fromPost();
            $this->request = Rails::getRequest();
            $this->response = Rails::getResponse();
            $this->route = Rails::getCurrentRoute();
            $this->session = new Session();
        }

        /**
         * The middleware handler.
         * @return bool Should return true on success or false on fail.
         */
        public abstract function handle();

    }

?>