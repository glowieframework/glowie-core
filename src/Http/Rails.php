<?php
    namespace Glowie\Core\Http;

    use Util;
    use Config;
    use Exception;
    use Closure;
    use Glowie\Core\Exception\RoutingException;
    use Glowie\Core\Exception\FileException;

    /**
     * Router and starting point for Glowie application.
     * @category Router
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class Rails{

        /**
         * Auto routing setting.
         * @var bool
         */
        private static $autoRouting = false;

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
         * Current route name.
         * @var string
         */
        private static $currentRoute;

        /**
         * Current route params.
         * @var array
         */
        private static $currentParams = [];

        /**
         * Current route group.
         * @var string|null
         */
        private static $group = null;

        /**
         * Current route prefix.
         * @var string
         */
        private static $prefix = '';

        /**
         * Loads the route configuration file.
         */
        public static function load(){
            $file = Util::location('config/Routes.php');
            if(!is_file($file)) throw new FileException('Route configuration file "' . $file . '" was not found');
            require_once($file);
        }

        /**
         * Setup a new route for the application.
         * @param string $route The route URI to setup.
         * @param string $controller (Optional) The namespaced controller name that this route will instantiate.\
         * You can use `ControllerName::class` to get this property correctly.
         * @param string|null $action (Optional) The action name from the controller that this route will instantiate.
         * @param string|array $methods (Optional) HTTP methods that this route accepts. Can be a single method or an array of methods.\
         * Leave empty for all.
         * @param string $name (Optional) Route name.
         */
        public static function addRoute(string $route, string $controller = 'Glowie\Controllers\Main', ?string $action = null, $methods = [], string $name = ''){
            if(Util::isEmpty($name)) $name = Util::slug($route, '-', true);
            if(Util::isEmpty($action)) $action = Util::camelCase($name);
            if(Util::isEmpty($controller)) throw new RoutingException('Controller cannot be empty for route "' . $name . '"');
            if(!empty(self::$routes[$name])) throw new RoutingException('Duplicate route name: "' . $name . '"');
            self::$routes[$name] = [
                'name' => $name,
                'uri' => trim(self::$prefix . $route, '/'),
                'controller' => $controller,
                'action' => $action,
                'methods' => (array)$methods,
                'group' => self::$group
            ];
        }

        /**
         * Setup an anonymous (controller-independent) route for the application.
         * @param string $route The route URI to setup.
         * @param Closure $callback A closure to run anonymously. A generic controller instance will be injected as a param of this function.
         * @param string|array $methods (Optional) HTTP methods that this route accepts. Can be a single method or an array of methods.\
         * Leave empty for all.
         * @param string $name (Optional) Route name.
         */
        public static function addAnonymous(string $route, Closure $callback, $methods = [], string $name = ''){
            if(Util::isEmpty($name)) $name = Util::slug($route, '-', true);
            if(!empty(self::$routes[$name])) throw new RoutingException('Duplicate route name: "' . $name . '"');
            self::$routes[$name] = [
                'name' => $name,
                'uri' => trim(self::$prefix . $route, '/'),
                'controller' => Generic::class,
                'action' => 'action',
                'callback' => $callback,
                'methods' => (array)$methods,
                'group' => self::$group
            ];
        }

        /**
         * Setup a new protected route for the application.
         * @param string $route The route URI to setup.
         * @param string|array $middleware (Optional) The namespaced middleware name that this route will use to protect itself.\
         * You can use `MiddlewareName::class` to get this property correctly. You can also use an array of multiple middlewares.
         * @param string $controller (Optional) The namespaced controller name that this route will instantiate.\
         * You can use `ControllerName::class` to get this property correctly.
         * @param string|null $action (Optional) The action name from the controller that this route will instantiate.
         * @param string|array $methods (Optional) HTTP methods that this route accepts. Can be a single method or an array of methods.\
         * Leave empty for all.
         * @param string $name (Optional) Route name.
         */
        public static function addProtectedRoute(string $route, $middleware = 'Glowie\Middlewares\Authenticate', string $controller = 'Glowie\Controllers\Main', ?string $action = null, $methods = [], string $name = ''){
            if(Util::isEmpty($name)) $name = Util::slug($route, '-', true);
            if(Util::isEmpty($action)) $action = Util::camelCase($name);
            if(Util::isEmpty($controller)) throw new RoutingException('Controller cannot be empty for route "' . $name . '"');
            if(Util::isEmpty($middleware)) throw new RoutingException('Middleware cannot be empty for route "' . $name . '"');
            if(!empty(self::$routes[$name])) throw new RoutingException('Duplicate route name: "' . $name . '"');
            self::$routes[$name] = [
                'name' => $name,
                'uri' => trim(self::$prefix . $route, '/'),
                'controller' => $controller,
                'action' => $action,
                'middleware' => (array)$middleware,
                'methods' => (array)$methods,
                'group' => self::$group
            ];
        }

        /**
         * Setup an anonymous (controller-independent) protected route for the application.
         * @param string $route The route URI to setup.
         * @param string|array $middleware (Optional) The namespaced middleware name that this route will use to protect itself.\
         * You can use `MiddlewareName::class` to get this property correctly. You can also use an array of multiple middlewares.
         * @param Closure $callback A closure to run anonymously. A generic controller instance will be injected as a param of this function.
         * @param string|array $methods (Optional) HTTP methods that this route accepts. Can be a single method or an array of methods.\
         * Leave empty for all.
         * @param string $name (Optional) Route name.
         */
        public static function addProtectedAnonymous(string $route, $middleware = 'Glowie\Middlewares\Authenticate', Closure $callback, $methods = [], string $name = ''){
            if(Util::isEmpty($name)) $name = Util::slug($route, '-', true);
            if(Util::isEmpty($middleware)) throw new RoutingException('Middleware cannot be empty for route "' . $name . '"');
            if(!empty(self::$routes[$name])) throw new RoutingException('Duplicate route name: "' . $name . '"');
            self::$routes[$name] = [
                'name' => $name,
                'uri' => trim(self::$prefix . $route, '/'),
                'controller' => Generic::class,
                'action' => 'action',
                'middleware' => (array)$middleware,
                'callback' => $callback,
                'methods' => (array)$methods,
                'group' => self::$group
            ];
        }

        /**
         * Setup a new redirect route for the application.
         * @param string $route The route URI to redirect.
         * @param string $target The target URl to redirect this route to.
         * @param int $code (Optional) HTTP status code to pass with the redirect.
         * @param string|array $methods (Optional) HTTP methods that this route accepts. Can be a single method or an array of methods.\
         * Leave empty for all.
         * @param string $name (Optional) Route name.
         */
        public static function addRedirect(string $route, string $target, int $code = Response::HTTP_TEMPORARY_REDIRECT, $methods = [], string $name = ''){
            if(Util::isEmpty($name)) $name = Util::slug($route, '-', true);
            if(Util::isEmpty($target)) throw new RoutingException('Redirect target cannot be empty for route "' . $name . '"');
            if(!empty(self::$routes[$name])) throw new RoutingException('Duplicate route name: "' . $name . '"');
            self::$routes[$name] = [
                'name' => $name,
                'uri' => trim(self::$prefix . $route, '/'),
                'redirect' => $target,
                'code' => $code,
                'methods' => (array)$methods,
                'group' => self::$group
            ];
        }

        /**
         * Maps multiple routes at once.
         * @param array $routes Associative array of routes to map. The key must be the route URI and the value must be an array\
         * with the **controller, action and name** (in this order). Parameters are optional.
         * @param string|array $methods (Optional) HTTP methods that these routes accept. Can be a single method or an array of methods.\
         * Leave empty for all.
         */
        public static function mapRoutes(array $routes, $methods = []){
            foreach($routes as $route => $config){
                $config = (array)$config;
                self::addRoute($route, $config[0] ?? 'Glowie\Controllers\Main', $config[1] ?? null, $methods, $config[2] ?? '');
            }
        }

        /**
         * Maps multiple protected routes at once.
         * @param array $routes Associative array of routes to map. The key must be the route URI and the value must be an array\
         * with the **controller, action and name** (in this order). Parameters are optional.
         * @param string|array $middleware (Optional) The namespaced middleware name that these routes will use to protect themself.\
         * You can use `MiddlewareName::class` to get this property correctly. You can also use an array of multiple middlewares.
         * @param string|array $methods (Optional) HTTP methods that these routes accept. Can be a single method or an array of methods.\
         * Leave empty for all.
         */
        public static function mapProtectedRoutes(array $routes, $middleware = 'Glowie\Middlewares\Authenticate', $methods = []){
            foreach($routes as $route => $config){
                $config = (array)$config;
                self::addProtectedRoute($route, $middleware, $config[0] ?? 'Glowie\Controllers\Main', $config[1] ?? null, $methods, $config[2] ?? '');
            }
        }

        /**
         * Groups a collection of routes inside a named group.
         * @param string $name Group name.
         * @param Closure $callback A function with the grouped route definition methods.
         * @param bool $prefix (Optional) Prepend the group name as a path prefix in all grouped routes.
         */
        public static function groupRoutes(string $name, Closure $callback, bool $prefix = false){
            if(Util::isEmpty($name)) throw new RoutingException('Route group name cannot be empty');
            self::$group = $name;
            if($prefix) self::$prefix = $name . '/';
            call_user_func($callback);
            self::$group = null;
            self::$prefix = '';
        }

        /**
         * Sets the auto routing feature on or off.
         * @param bool $option (Optional) **True** for turning on auto routing or **false** for turning it off.
         */
        public static function setAutoRouting(bool $option = true){
            self::$autoRouting = $option;
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
         * Gets all routes configuration as an associative array.
         * @return array Returns all routes.
         */
        public static function getAllRoutes(){
            return self::$routes;
        }

        /**
         * Gets the current route name.
         * @return string Returns the current route name.
         */
        public static function getCurrentRoute(){
            return self::$currentRoute;
        }

        /**
         * Gets the current route group name.
         * @return string|null Returns the current route group name or `null` if the route is not in any group.
         */
        public static function getCurrentGroup(){
            return self::$routes[self::$currentRoute]['group'];
        }

        /**
         * Gets the current route params.
         * @return array Associative array with the current route params.
         */
        public static function getParams(){
            return self::$currentParams;
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

            // Sets the response CORS headers
            self::$response->applyCors();

            // Retrieves the request URI
            $route = self::$request->getURI();

            // Checks for maintenance mode
            if(Config::get('maintenance.enabled', false)){
                // Validates secret bypass route
                $cookies = new Cookies();
                $key = Config::get('maintenance.bypass_key');
                if(Util::isEmpty($key)) throw new Exception('Application maintenance bypass key was not defined');

                // Saves the maintenance key in the cookies
                if($route == $key){
                    $cookies->set('MAINTENANCE_KEY', $key);
                    return self::$response->redirectBase();
                }

                // Validates the cookie
                if($cookies->get('MAINTENANCE_KEY') != $key) return self::callServiceUnavailable();
            }

            // Stores current route configuration
            $config = false;
            self::$currentRoute = $route;

            // Loops through routes configuration to find a valid route pattern
            foreach (self::$routes as $key => $item) {
                // Creates a regex replacing dynamic parameters to valid regex patterns
                $regex = '~^' . preg_replace('(\\\:[^\/\\\]+)', '([^\/]+)', preg_quote($item['uri'], '/')) . '$~';
                if (preg_match_all($regex, trim($route, '/'), $params)) {
                    // Parse route parameters
                    $result = [];
                    if(!empty($params)){
                        if(preg_match_all('~:([^\/:]+)~', $item['uri'], $segments) && !empty($segments[1])){
                            array_shift($params);
                            $result = array_combine($segments[1], array_column($params, 0));
                        }
                    }

                    // Saves the configuration
                    $config = $item;
                    self::$currentParams = $result;
                    self::$currentRoute = $key;
                    break;
                }
            }

            // Checks if route was found
            if ($config !== false) {
                // Checks if there is a request method configuration
                if(!empty($config['methods'])){
                    $config['methods'] = array_map('strtoupper', $config['methods']);
                    if(!in_array(self::$request->getMethod(), $config['methods'])) return self::callMethodNotAllowed();
                }

                // Checks if there is a redirect configuration
                if(!empty($config['redirect'])) return self::$response->redirect($config['redirect'], $config['code']);

                // Gets the controller
                $controller = !empty($config['callback']) ? Generic::class : $config['controller'];

                // If the controller class does not exist, trigger an error
                if (!class_exists($controller)) throw new RoutingException("\"{$controller}\" was not found");

                // Instantiates the controller
                self::$controller = new $controller;

                // Checks for the route middlewares
                if(!empty($config['middleware'])){
                    // Runs each middleware
                    foreach($config['middleware'] as $middleware){
                        // If middleware class does not exist, trigger an error
                        if (!class_exists($middleware)) throw new RoutingException("\"{$middleware}\" was not found");

                        // Instantiates the middleware
                        self::$middleware = new $middleware;
                        if (is_callable([self::$middleware, 'init'])) self::$middleware->init();

                        // Calls middleware handle() method
                        $response = self::$middleware->handle();
                        if ($response) {
                            // Middleware passed
                            if (is_callable([self::$middleware, 'success'])) self::$middleware->success();
                        } else {
                            // Middleware blocked
                            if (is_callable([self::$middleware, 'fail'])) {
                                self::$response->deny();
                                return self::$middleware->fail();
                            } else {
                                return self::callForbidden();
                            };
                        }
                    }
                }

                // Checks for anonymous controller
                if(!empty($config['callback'])) return self::$controller->action($config['callback']);

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
            }else if(self::$autoRouting){
                // Get URI parameters
                $autoroute = explode('/', trim($route, '/'));

                // If no route was specified
                if($route == '/'){
                    $controller = 'Glowie\Controllers\Main';
                    $action = 'index';
                    return self::callAutoRoute($controller, $action);

                // If only the controller was specified
                }else if(count($autoroute) == 1){
                    $controller = 'Glowie\Controllers\\' . Util::pascalCase($autoroute[0]);
                    $action = 'index';
                    return self::callAutoRoute($controller, $action);

                // Controller and action were specified
                }else if(count($autoroute) == 2){
                    $controller = 'Glowie\Controllers\\' . Util::pascalCase($autoroute[0]);
                    $action = Util::camelCase($autoroute[1]);
                    return self::callAutoRoute($controller, $action);

                // Controller, action and parameters were specified
                }else{
                    $controller = 'Glowie\Controllers\\' . Util::pascalCase($autoroute[0]);
                    $action = Util::camelCase($autoroute[1]);
                    $params = array_slice($autoroute, 2);
                    return self::callAutoRoute($controller, $action, $params);
                }
            }else{
                // Route was not found
                return self::callNotFound();
            }
        }

        /**
         * Calls `notFound()` action in Error controller.
         */
        private static function callNotFound(){
            self::$response->notFound();
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller;
                if (is_callable([self::$controller, 'init'])) self::$controller->init();
                if (is_callable([self::$controller, 'notFound'])) self::$controller->notFound();
            }
        }

        /**
         * Calls `forbidden()` action in Error controller.
         */
        private static function callForbidden(){
            self::$response->deny();
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller;
                if (is_callable([self::$controller, 'init'])) self::$controller->init();
                if (is_callable([self::$controller, 'forbidden'])) self::$controller->forbidden();
            }
        }

         /**
         * Calls `methodNotAllowed()` action in Error controller.
         */
        private static function callMethodNotAllowed(){
            self::$response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller;
                if (is_callable([self::$controller, 'init'])) self::$controller->init();
                if (is_callable([self::$controller, 'methodNotAllowed'])) self::$controller->methodNotAllowed();
            }
        }

        /**
         * Calls `serviceUnavailable()` action in Error controller.
         */
        private static function callServiceUnavailable(){
            self::$response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller;
                if (is_callable([self::$controller, 'init'])) self::$controller->init();
                if (is_callable([self::$controller, 'serviceUnavailable'])) self::$controller->serviceUnavailable();
            }
        }

        /**
         * Calls the auto routing settings.
         * @param string $controller Controller name.
         * @param string $action Action name.
         * @param array $params (Optional) Route parameters.
         */
        private static function callAutoRoute(string $controller, string $action, array $params = []){
            if (!class_exists($controller)) return self::callNotFound();
            if (!empty($params)){
                foreach($params as $key => $value){
                    $params['param' . ($key + 1)] = $value;
                    unset($params[$key]);
                }
            }
            self::$currentParams = $params;
            self::$controller = new $controller;
            if (is_callable([self::$controller, $action])) {
                if (is_callable([self::$controller, 'init'])) self::$controller->init();
                self::$controller->{$action}();
            } else {
                self::callNotFound();
            };
        }
    }

?>