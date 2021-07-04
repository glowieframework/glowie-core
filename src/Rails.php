<?php
    namespace Glowie\Core;

    use Util;

    /**
     * Router and starting point for Glowie application.
     * @category Router
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
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
         * Application routes.
         * @var array
         */
        private static $routes = [];

        /**
         * Setup a new route for the application.
         * @param string $route The route URI to setup.
         * @param string $controller (Optional) The namespaced controller name that this route will instantiate.\
         * You can use `ControllerName::class` to get this property the correct way.
         * @param string $action (Optional) The action name from the controller that this route will instantiate.
         * @param string[] $methods (Optional) Array of allowed HTTP methods that this route accepts. Leave empty for all.
         * @param string $name (Optional) Route internal name/identifier.
         */
        public static function addRoute(string $route, string $controller = 'Glowie\Controllers\Main', string $action = 'index', array $methods = [], string $name = ''){
            if(empty($name)) $name = $route;
            self::$routes[$name] = [
                'uri' => $route,
                'controller' => $controller,
                'action' => $action,
                'methods' => $methods
            ];
        }
        
        /**
         * Setup a new protected route for the application.
         * @param string $route The route URI to setup.
         * @param string $middleware (Optional) The namespaced middleware name that this route will use to protect itself.\
         * You can use `MiddlewareName::class` to get this property the correct way.
         * @param string $controller (Optional) The namespaced controller name that this route will instantiate.\
         * You can use `ControllerName::class` to get this property the correct way.
         * @param string $action (Optional) The action name from the controller that this route will instantiate.
         * @param string[] $methods (Optional) Array of allowed HTTP methods that this route accepts. Leave empty for all.
         * @param string $name (Optional) Route internal name/identifier.
         */
        public static function addProtectedRoute(string $route, string $middleware = 'Glowie\Middlewares\Authenticate', string $controller = 'Glowie\Controllers\Main', string $action = 'index', array $methods = [], string $name = ''){
            if(empty($name)) $name = $route;
            self::$routes[$name] = [
                'uri' => $route,
                'controller' => $controller,
                'action' => $action,
                'middleware' => $middleware,
                'methods' => $methods
            ];
        }
        
        /**
         * Setup a new redirect route for the application.
         * @param string $route The route URI to redirect.
         * @param string $target The target URl to redirect this route to.
         * @param string[] $methods (Optional) Array of allowed HTTP methods that this route accepts. Leave empty for all.
         * @param string $name (Optional) Route internal name/identifier.
         */
        public static function addRedirect(string $route, string $target, array $methods = [], string $name = ''){
            if(empty($name)) $name = $route;
            self::$routes[$name] = [
                'uri' => $route,
                'redirect' => $target,
                'methods' => $methods
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
         * @param string $route Route internal name/identifier to get.
         * @return array|null Returns the route setting as an array if valid or null if not.
         */
        public static function getRoute(string $route){
            if(isset(self::$routes[$route])){
                return self::$routes[$route];
            }else{
                return null;
            }
        }

        /**
         * Initializes application routing.
         */
        public static function init(){
            // Cleans request URI
            $route = trim(substr(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), strlen(GLOWIE_APP_FOLDER)), '/');
            if (empty($route)) $route = '/';

            // Stores current route configuration
            $config = null;
            $routeName = $route;

            // Loops through routes configuration to find a valid route pattern
            foreach (self::$routes as $key => $item) {
                // Creates a regex replacing dynamic parameters to valid regex patterns
                $regex = str_replace('/', '\/', preg_replace('(:[^\/]+)', '([^/]+)', ltrim($item['uri'], '/')));
                if (preg_match_all('/^' . $regex . '$/i', rtrim($route, '/'), $params)) {
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

                    $config = $item;
                    $routeName = $key;
                    break;
                }
            }

            // Check if route was found
            if ($config) {
                // Check if there is a request method configuration
                if(!empty($config['methods'])){
                    if(!in_array(strtolower($_SERVER['REQUEST_METHOD']), $config['methods'])) return self::callForbidden($route, $result);
                }

                // Check if there is a redirect configuration
                if(empty($config['redirect'])){
                    // Gets the controller
                    $controller = $config['controller'];

                    // If controller class does not exists, trigger an error
                    if (!class_exists($controller)) trigger_error("Rails: Controller \"{$controller}\" not found", E_USER_ERROR);

                    // Instantiates new controller
                    self::$controller = new $controller($routeName, $result);

                    // Checks for the route middleware
                    if(!empty($config['middleware'])){
                        // Gets the middleware
                        $middleware = $config['middleware'];

                        // If middleware class does not exists, trigger an error
                        if (!class_exists($middleware)) trigger_error("Rails: Middleware \"{$middleware}\" not found", E_USER_ERROR);
                        
                        // Instantiates new middleware
                        self::$middleware = new $middleware(self::$controller, $routeName, $result);
                        if (!method_exists(self::$middleware, 'handle')) trigger_error("Rails: Middleware \"{$middleware}\" does not have a handle() method", E_USER_ERROR);
                        if (method_exists(self::$middleware, 'init')) call_user_func([self::$middleware, 'init']);
                        
                        // Calls middleware handle() method
                        $response = call_user_func([self::$middleware, 'handle']);
                        if($response){
                            // Middleware passed
                            if (method_exists(self::$middleware, 'success')) call_user_func([self::$middleware, 'success']);
                        }else{
                            // Middleware blocked
                            if (method_exists(self::$middleware, 'fail')){
                                http_response_code(403);
                                return call_user_func([self::$middleware, 'fail']);
                            }else{
                                return self::callForbidden($routeName, $result);
                            };
                        }
                    }

                    // Gets the action
                    $action = $config['action'];

                    // If action does not exists, trigger an error
                    if (method_exists(self::$controller, $action)) {
                        // Runs the controller init() method
                        if (method_exists(self::$controller, 'init')) call_user_func([self::$controller, 'init']);

                        // Calls action
                        return call_user_func([self::$controller, $action]);
                    } else {
                        trigger_error("Rails: Action \"{$action}()\" not found in {$controller} controller", E_USER_ERROR);
                    }
                }else{
                    // Redirects to the target URL
                    return Util::redirect($config['redirect']);
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
                        $controller = 'Glowie\Controllers\\' . Util::camelCase($autoroute[0], true);
                        $action = 'index';
                        return self::callAutoRoute($controller, $action, $routeName);

                    // Controller and action were specified
                    }else if(count($autoroute) == 2){
                        $controller = 'Glowie\Controllers\\' . Util::camelCase($autoroute[0], true);
                        $action = Util::camelCase($autoroute[1]);
                        return self::callAutoRoute($controller, $action, $routeName);
                    
                    // Controller, action and parameters were specified
                    }else{
                        $controller = 'Glowie\Controllers\\' . Util::camelCase($autoroute[0], true);
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
            http_response_code(404);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller($route);
                if (method_exists(self::$controller, 'init')) call_user_func([self::$controller, 'init']);
                if (method_exists(self::$controller, 'notFound')) call_user_func([self::$controller, 'notFound']);
            }
        }

        /**
         * Calls `forbidden()` action in Error controller.
         * @param string $route Request route.
         * @param array $params (Optional) Route parameters.
         */
        private static function callForbidden(string $route, array $params = []){
            http_response_code(403);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller($route, $params);
                if (method_exists(self::$controller, 'init')) call_user_func([self::$controller, 'init']);
                if (method_exists(self::$controller, 'forbidden')) call_user_func([self::$controller, 'forbidden']);
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
                if (method_exists(self::$controller, 'init')) call_user_func([self::$controller, 'init']);
                if (method_exists(self::$controller, $action)) {
                    call_user_func([self::$controller, $action]);
                } else {
                    self::callNotFound($route);
                };
            } else {
                self::callNotFound($route);
            }
        }
    }

?>