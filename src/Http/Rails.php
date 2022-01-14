<?php
    namespace Glowie\Core\Http;

    use Util;
    use Config;
    use Glowie\Core\Exception\RoutingException;
    use Glowie\Core\Exception\FileException;

    /**
     * Router and starting point for Glowie application.
     * @category Router
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.1
     */
    class Rails{

        /**
         * Auto routing setting.
         * @var bool
         */
        private static $auto_routing = false;

        /**
         * Current controller.
         * @var Controller
         */
        private static $controller;

        /**
         * Current middleware.
         * @var Middleware
         */
        private static $middleware;

        /**
         * Request handler.
         * @var Request
         */
        private static $request;

        /**
         * Response handler.
         * @var Response
         */
        private static $response;

        /**
         * Application routes.
         * @var array
         */
        private static $routes = [];

        /**
         * Loads the route configuration file.
         */
        public static function load(){
            $file = Util::location('config/Routes.php');
            if(!file_exists($file)) throw new FileException('Route configuration file "' . $file . '" was not found');
            require_once($file);
        }

        /**
         * Setup a new route for the application.
         * @param string $route The route URI to setup.
         * @param string $controller (Optional) The namespaced controller name that this route will instantiate.\
         * You can use `ControllerName::class` to get this property correctly.
         * @param string|null $action (Optional) The action name from the controller that this route will instantiate.
         * @param string|array $methods (Optional) HTTP methods that this route accepts. Can be a single method or\
         * an array of methods. Leave empty for all.
         * @param string $name (Optional) Route name.
         */
        public static function addRoute(string $route, string $controller = 'Glowie\Controllers\Main', ?string $action = null, $methods = [], string $name = ''){
            if(empty($name)) $name = $route;
            if(empty($action)) $action = Util::camelCase($name);
            if(empty($controller)) throw new RoutingException('Route controller cannot be empty');
            self::$routes[$name] = [
                'uri' => $route,
                'controller' => $controller,
                'action' => $action,
                'methods' => (array)$methods
            ];
        }

        /**
         * Setup a new protected route for the application.
         * @param string $route The route URI to setup.
         * @param string|array $middleware (Optional) The namespaced middleware name that this route will use to protect itself.\
         * You can use `MiddlewareName::class` to get this property correctly.\
         * You can also use an array of multiple middlewares.
         * @param string $controller (Optional) The namespaced controller name that this route will instantiate.\
         * You can use `ControllerName::class` to get this property correctly.
         * @param string|null $action (Optional) The action name from the controller that this route will instantiate.
         * @param string|array $methods (Optional) HTTP methods that this route accepts. Can be a single method or\
         * an array of methods. Leave empty for all.
         * @param string $name (Optional) Route name.
         */
        public static function addProtectedRoute(string $route, $middleware = 'Glowie\Middlewares\Authenticate', string $controller = 'Glowie\Controllers\Main', ?string $action = null, $methods = [], string $name = ''){
            if(empty($name)) $name = $route;
            if(empty($action)) $action = Util::camelCase($name);
            if(empty($controller)) throw new RoutingException('Route controller cannot be empty');
            if(empty($middleware)) throw new RoutingException('Route middleware cannot be empty');
            self::$routes[$name] = [
                'uri' => $route,
                'controller' => $controller,
                'action' => $action,
                'middleware' => (array)$middleware,
                'methods' => (array)$methods
            ];
        }

        /**
         * Setup a new redirect route for the application.
         * @param string $route The route URI to redirect.
         * @param string $target The target URl to redirect this route to.
         * @param int $code (Optional) HTTP status code to pass with the redirect.
         * @param string|array $methods (Optional) HTTP methods that this route accepts. Can be a single method or\
         * an array of methods. Leave empty for all.
         * @param string $name (Optional) Route name.
         */
        public static function addRedirect(string $route, string $target, int $code = Response::HTTP_TEMPORARY_REDIRECT, $methods = [], string $name = ''){
            if(empty($name)) $name = $route;
            if(empty($target)) throw new RoutingException('Route redirect target cannot be empty');
            self::$routes[$name] = [
                'uri' => $route,
                'redirect' => $target,
                'code' => $code,
                'methods' => (array)$methods
            ];
        }

        /**
         * Sets the auto routing feature on or off.
         * @param bool $option (Optional) **True** for turning on auto routing or **false** for turning it off.
         */
        public static function setAutoRouting(bool $option = true){
            self::$auto_routing = $option;
        }

        /**
         * Gets an specific route configuration.
         * @param string $route Name of the route to get.
         * @return array|null Returns the route setting as an array if valid or null if not.
         */
        public static function getRoute(string $route){
            return self::$routes[$route] ?? null;
        }

        /**
         * Returns the current Request instance.
         * @return Request Request instance.
         */
        public static function getRequest(){
            return self::$request;
        }

        /**
         * Returns the current Response instance.
         * @return Response Response instance.
         */
        public static function getResponse(){
            return self::$response;
        }

        /**
         * Returns the current Controller instance.
         * @return Controller Controller instance.
         */
        public static function getController(){
            return self::$controller;
        }

        /**
         * Returns the current Middleware instance.
         * @return Middleware Middleware instance.
         */
        public static function getMiddleware(){
            return self::$middleware;
        }

        /**
         * Initializes application routing.
         */
        public static function init(){
            // Creates the request and response instance
            self::$request = new Request();
            self::$response = new Response();

            // Retrieves the request URI
            $route = self::$request->getURI();
            if(empty($route)) $route = '/';

            // Checks for maintenance mode
            if(Config::get('maintenance.enabled', false)){
                // Validates secret bypass route
                $cookies = new Cookies();
                $key = Config::get('maintenance.bypass_key', '470c054cfc6780df66bf3922eddbd883');

                // Saves the maintenance key in the cookies
                if($route == $key){
                    $cookies->set('MAINTENANCE_KEY', $key);
                    return self::$response->redirectBase();
                }

                // Validates the cookie
                if($cookies->get('MAINTENANCE_KEY') != $key) return self::callServiceUnavailable($route);
            }

            // Stores current route configuration
            $config = null;
            $routeName = $route;

            // Loops through routes configuration to find a valid route pattern
            foreach (self::$routes as $key => $item) {
                // Creates a regex replacing dynamic parameters to valid regex patterns
                $regex = str_replace('/', '\/', preg_replace('(:[^\/]+)', '([^/]+)', ltrim($item['uri'], '/')));
                if (preg_match_all('/^' . $regex . '$/', rtrim($route, '/'), $params)) {
                    // Fetch route parameters
                    $result = [];
                    foreach(explode('/', $item['uri']) as $segment){
                        if(Util::startsWith($segment, ':')) $result[] = substr($segment, 1);
                    }

                    // If all parameters were filled
                    if (!empty($result)) {
                        array_shift($params);
                        $result = array_combine($result, array_column($params, 0));
                    }

                    // Saves the configuration
                    $config = $item;
                    $routeName = $key;
                    break;
                }
            }

            // Checks if route was found
            if ($config) {
                // Checks if there is a request method configuration
                if(!empty($config['methods'])){
                    $config['methods'] = array_map('strtoupper', $config['methods']);
                    if(!in_array(self::$request->getMethod(), $config['methods'])) return self::callMethodNotAllowed($route, $result);
                }

                // Checks if there is not a redirect configuration
                if(empty($config['redirect'])){
                    // Gets the controller
                    $controller = $config['controller'];

                    // If the controller class does not exist, trigger an error
                    if (!class_exists($controller)) throw new RoutingException("\"{$controller}\" was not found");

                    // Instantiates the controller
                    self::$controller = new $controller($routeName, $result);

                    // Checks for the route middlewares
                    if(!empty($config['middleware'])){
                        // Runs each middleware
                        foreach($config['middleware'] as $middleware){
                            // If middleware class does not exist, trigger an error
                            if (!class_exists($middleware)) throw new RoutingException("\"{$middleware}\" was not found");

                            // Instantiates the middleware
                            self::$middleware = new $middleware($routeName, $result);
                            if (!is_callable([self::$middleware, 'handle'])) throw new RoutingException("\"{$middleware}\" does not have a \"handle()\" method");
                            if (is_callable([self::$middleware, 'init'])) self::$middleware->init();

                            // Calls middleware handle() method
                            $response = self::$middleware->handle();
                            if ($response) {
                                // Middleware passed
                                if (is_callable([self::$middleware, 'success'])) self::$middleware->success();
                            } else {
                                // Middleware blocked
                                if (is_callable([self::$middleware, 'fail'])) {
                                    self::$response->setStatusCode(Response::HTTP_FORBIDDEN);
                                    return self::$middleware->fail();
                                } else {
                                    return self::callForbidden($routeName, $result);
                                };
                            }
                        }
                    }

                    // Gets the action
                    $action = $config['action'];

                    // If action does not exist, trigger an error
                    if (is_callable([self::$controller, $action])) {
                        // Runs the controller init() method
                        if (is_callable([self::$controller, 'init'])) self::$controller->init();

                        // Calls action
                        return self::$controller->{$action}();
                    } else {
                        throw new RoutingException("Action \"{$action}()\" not found in \"{$controller}\"");
                    }
                }else{
                    // Redirects to the target URL
                    return self::$response->redirect($config['redirect'], $config['code']);
                }
            } else {
                // Check if auto routing is enabled
                if(self::$auto_routing){

                    // Get URI parameters
                    $autoroute = explode('/', $route);

                    // Cleans empty parameters or trailing slashes
                    foreach($autoroute as $key => $value){
                        if(empty($value) || trim($value) == '') unset($autoroute[$key]);
                    }

                    // If no route was specified
                    if($route == '/'){
                        $controller = 'Glowie\Controllers\Main';
                        $action = 'index';
                        return self::callAutoRoute($controller, $action, $routeName);

                    // If only the controller was specified
                    }else if(count($autoroute) == 1){
                        $controller = 'Glowie\Controllers\\' . Util::pascalCase($autoroute[0]);
                        $action = 'index';
                        return self::callAutoRoute($controller, $action, $routeName);

                    // Controller and action were specified
                    }else if(count($autoroute) == 2){
                        $controller = 'Glowie\Controllers\\' . Util::pascalCase($autoroute[0]);
                        $action = Util::camelCase($autoroute[1]);
                        return self::callAutoRoute($controller, $action, $routeName);

                    // Controller, action and parameters were specified
                    }else{
                        $controller = 'Glowie\Controllers\\' . Util::pascalCase($autoroute[0]);
                        $action = Util::camelCase($autoroute[1]);
                        $params = array_slice($autoroute, 2);
                        return self::callAutoRoute($controller, $action, $routeName, $params);
                    }
                }else{
                    // Route was not found
                    return self::callNotFound($routeName);
                }
            }
        }

        /**
         * Calls `notFound()` action in Error controller.
         * @param string $route Request route.
         */
        private static function callNotFound(string $route){
            self::$response->setStatusCode(Response::HTTP_NOT_FOUND);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller($route);
                if (is_callable([self::$controller, 'init'])) self::$controller->init();
                if (is_callable([self::$controller, 'notFound'])) self::$controller->notFound();
            }
        }

        /**
         * Calls `forbidden()` action in Error controller.
         * @param string $route Request route.
         * @param array $params (Optional) Route parameters.
         */
        private static function callForbidden(string $route, array $params = []){
            self::$response->setStatusCode(Response::HTTP_FORBIDDEN);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller($route, $params);
                if (is_callable([self::$controller, 'init'])) self::$controller->init();
                if (is_callable([self::$controller, 'forbidden'])) self::$controller->forbidden();
            }
        }

         /**
         * Calls `methodNotAllowed()` action in Error controller.
         * @param string $route Request route.
         * @param array $params (Optional) Route parameters.
         */
        private static function callMethodNotAllowed(string $route, array $params = []){
            self::$response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller($route, $params);
                if (is_callable([self::$controller, 'init'])) self::$controller->init();
                if (is_callable([self::$controller, 'methodNotAllowed'])) self::$controller->methodNotAllowed();
            }
        }

        /**
         * Calls `serviceUnavailable()` action in Error controller.
         * @param string $route Request route.
         * @param array $params (Optional) Route parameters.
         */
        private static function callServiceUnavailable(string $route, array $params = []){
            self::$response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller($route, $params);
                if (is_callable([self::$controller, 'init'])) self::$controller->init();
                if (is_callable([self::$controller, 'serviceUnavailable'])) self::$controller->serviceUnavailable();
            }
        }

        /**
         * Calls the auto routing settings.
         * @param string $controller Controller name.
         * @param string $action Action name.
         * @param string $route Request route.
         * @param array $params (Optional) Route parameters.
         */
        private static function callAutoRoute(string $controller, string $action, string $route, array $params = []){
            if (class_exists($controller)) {
                if (!empty($params)){
                    foreach($params as $key => $value){
                        $params['param' . ($key + 1)] = $value;
                        unset($params[$key]);
                    }
                }
                self::$controller = new $controller($route, $params);
                if (is_callable([self::$controller, 'init'])) self::$controller->init();
                if (is_callable([self::$controller, $action])) {
                    self::$controller->{$action}();
                } else {
                    self::callNotFound($route);
                };
            } else {
                self::callNotFound($route);
            }
        }
    }

?>