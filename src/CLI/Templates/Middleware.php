<?php
    namespace Glowie\Middlewares;

    use Glowie\Core\Http\Middleware;

    /**
     * __FIREFLY_TEMPLATE_NAME__ middleware for Glowie application.
     * @category Middleware
     * @package glowieframework/glowie
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     */
    class __FIREFLY_TEMPLATE_NAME__ extends Middleware{

        /**
         * This method will be called before any other methods from this middleware.
         */
        public function init(){
            //
        }

        /**
         * The middleware handler.
         * @return bool Should return true on success or false on fail.
         */
        public function handle(){
            // Create something awesome
        }

        /**
         * Called if the middleware handler returns true.
         */
        public function success(){
            //
        }

        /**
         * Called if the middleware handler returns false.
         */
        public function fail(){
            //
        }

    }

?>