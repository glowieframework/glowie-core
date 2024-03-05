<?php
    namespace Glowie\Core\Http;

    /**
     * Generic controller for Glowie application.
     * @category Controller
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     */
    class Generic extends Controller{

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
         * Request handler.
         * @var Request
         */
        public $request;

        /**
         * Response handler.
         * @var Response
         */
        public $response;

        /**
         * Current requested route.
         * @var string
         */
        public $route;

        /**
         * Session parameters.
         * @var Session
         */
        public $session;

        /**
         * Calls the action callback.
         * @param Closure $callback Closure function to be called.
         */
        final public function action($callback){
            $callback($this);
        }

    }

?>