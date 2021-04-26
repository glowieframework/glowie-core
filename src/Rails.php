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
     * @version 0.3-alpha
     */
    class Rails{
        
        /**
         * Current controller.
         * @var Controller
         */
        public static $controller;

        /**
         * Current middleware.
         * @var Middleware
         */
        public static $middleware;

        /**
         * Setup a new route for the application.
         * @param string $route The route URI to setup.
         * @param string $controller (Optional) The **namespaced** controller name that this route will instantiate.
         * @param string $action (Optional) The action name from the controller that this route will instantiate.
         * @param string[] $methods (Optional) Array of allowed HTTP methods that this route accepts. Leave empty for all.
         */
        public static function addRoute(string $route, string $controller = 'Glowie\Controllers\Main', string $action = 'index', array $methods = []){
            $GLOBALS['glowieRoutes']['routes'][$route] = [
                'controller' => $controller,
                'action' => $action,
                'methods' => $methods
            ];
        }

        /**
         * Setup a new protected route for the application.
         * @param string $route The route URI to setup.
         * @param string $middleware (Optional) The **namespaced** middleware name that this route will use to protect itself.
         * @param string $controller (Optional) The **namespaced** controller name that this route will instantiate.
         * @param string $action (Optional) The action name from the controller that this route will instantiate.
         * @param string[] $methods (Optional) Array of allowed HTTP methods that this route accepts. Leave empty for all.
         */
        public static function addProtectedRoute(string $route, string $middleware = 'Glowie\Middlewares\Main', string $controller = 'Glowie\Controllers\Main', string $action = 'index', array $methods = []){
            $GLOBALS['glowieRoutes']['routes'][$route] = [
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
         */
        public static function addRedirect(string $route, string $target, array $methods = []){
            $GLOBALS['glowieRoutes']['routes'][$route] = [
                'redirect' => $target,
                'methods' => $methods
            ];
        }

        /**
         * Sets the auto routing feature on or off.
         * @param bool $option (Optional) **True** for turning on auto routing or **false** for turning it off.
         */
        public static function setAutoRouting(bool $option = true){
            $GLOBALS['glowieRoutes']['auto_routing'] = $option;
        }

        /**
         * Initializes application routing.
         */
        public static function init(){
            // Clean request URI
            $cleanRoute = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), !empty(GLOWIE_APP_FOLDER) ? strlen(GLOWIE_APP_FOLDER) + 2 : strlen(GLOWIE_APP_FOLDER) + 1);
            
            // Get current route
            if (!empty($cleanRoute) && trim($cleanRoute) != '') {
                $route = trim($cleanRoute);
            } else {
                $route = '/';
            }

            // Stores current route configuration
            $config = null;

            // Loops through routes configuration to find a valid route pattern
            foreach ($GLOBALS['glowieRoutes']['routes'] as $key => $item) {
                $regex = str_replace('/', '\/', preg_replace('(:[^\/]+)', '([^/]+)', ltrim($key, '/')));
                if (preg_match_all('/^' . $regex . '$/i', rtrim($route, '/'), $params)) {
                    // Fetch route parameters
                    $keys = explode('/:', $key);
                    $result = [];
                    if (count($keys) > 1) {
                        unset($params[0]);
                        unset($keys[0]);
                        foreach ($keys as $i => $value) $result[$value] = $params[$i][0];
                    }
                    $config = $item;
                    break;
                }
            }

            // Check if route was found
            if ($config) {
                // Check if there is a request method configuration
                if(!empty($config['methods'])){
                    if(!in_array(strtolower($_SERVER['REQUEST_METHOD']), $config['methods'])) return self::callForbidden($route);
                }

                // Check if there is a redirect configuration
                if(empty($config['redirect'])){
                    // Gets the controller
                    $controller = $config['controller'];
                    $friendlyName = str_replace('Glowie\Controllers\\', '', $controller);

                    // If controller class does not exists, trigger an error
                    if (!class_exists($controller)){
                        trigger_error("Rails: Controller \"{$friendlyName}\" not found");
                        exit;
                    }

                    // Instantiates new controller
                    self::$controller = new $controller;
                    self::$controller->route = $route;

                    // Runs the middleware
                    if(!empty($config['middleware'])){
                        // Gets the middleware
                        $middleware = $config['middleware'];
                        $friendlyMiddleware = str_replace('Glowie\Middlewares\\', '', $middleware);

                        // If middleware class does not exists, trigger an error
                        if (!class_exists($middleware)){
                            trigger_error("Rails: Middleware \"{$friendlyMiddleware}\" not found");
                            exit;
                        }

                        // Instantiates new middleware
                        self::$middleware = new $middleware(self::$controller);
                        self::$middleware->route = $route;

                        // Calls middleware handle() method
                        if(method_exists(self::$middleware, 'handle')){
                            $response = call_user_func([self::$middleware, 'handle']);
                            if($response){
                                // Middleware passed
                                if (method_exists(self::$middleware, 'success')) call_user_func([self::$middleware, 'success']);
                            }else{
                                // Middleware blocked
                                if (method_exists(self::$middleware, 'fail')){
                                    return call_user_func([self::$middleware, 'fail']);
                                }else{
                                    return self::callForbidden($route);
                                };
                            }
                        }else{
                            trigger_error("Rails: Middleware \"{$friendlyMiddleware}()\" does not have a handle() function");
                            exit;
                        }
                    }

                    // Gets the action
                    $action = $config['action'];

                    // If action does not exists, trigger an error
                    if (method_exists(self::$controller, $action)) {
                        // Parses URI parameters, if available
                        if (!empty($result)) self::$controller->params = new Element($result);

                        // Calls action
                        if (method_exists(self::$controller, 'init')) call_user_func([self::$controller, 'init']);
                        return call_user_func([self::$controller, $action]);
                    } else {
                        trigger_error("Rails: Action \"{$action}()\" not found in {$friendlyName} controller");
                        exit;
                    }
                }else{
                    // Redirects to the target URL
                    return Util::redirect($config['redirect']);
                }
            } else {
                // Check if auto routing is enabled
                if($GLOBALS['glowieRoutes']['auto_routing']){

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
                        return self::callAutoRoute($controller, $action, $route);

                    // If only the controller was specified
                    }else if(count($autoroute) == 1){
                        $controller = 'Glowie\Controllers\\' . self::parseName($autoroute[0], true);
                        $action = 'index';
                        return self::callAutoRoute($controller, $action, $route);

                    // Controller and action were specified
                    }else if(count($autoroute) == 2){
                        $controller = 'Glowie\Controllers\\' . self::parseName($autoroute[0], true);
                        $action = self::parseName($autoroute[1]);
                        return self::callAutoRoute($controller, $action, $route);
                    
                    // Controller, action and parameters were specified
                    }else{
                        $controller = 'Glowie\Controllers\\' . self::parseName($autoroute[0], true);
                        $action = self::parseName($autoroute[1]);
                        $params = array_slice($autoroute, 2);
                        return self::callAutoRoute($controller, $action, $route, $params);
                    }
                }else{
                    // Route was not found
                    return self::callNotFound($route);
                }
            }
        }

        /**
         * Parses names to camelCase convention. It also removes all accents and characters that are not\
         * valid letters, numbers or underscores.
         * @param string $string Name to be parsed.
         * @param bool $firstUpper (Optional) Determines if the first character should be uppercased.
         * @return string Parsed name.
         */
        private static function parseName(string $string, bool $firstUpper = false){
            $string = strtr(utf8_decode(strtolower($string)), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $string = preg_replace('/[^a-zA-Z0-9_]/', ' ', $string);
            if($firstUpper){
                return str_replace(' ', '', ucwords($string));
            }else{
                return str_replace(' ', '', lcfirst(ucwords($string)));
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
                self::$controller = new $controller;
                self::$controller->route = $route;
                if (method_exists(self::$controller, 'init')) call_user_func([self::$controller, 'init']);
                if (method_exists(self::$controller, 'notFound')) call_user_func([self::$controller, 'notFound']);
            }
        }

        /**
         * Calls `forbidden()` action in Error controller.
         * @param string $route Request route.
         */
        private static function callForbidden(string $route){
            http_response_code(403);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                self::$controller = new $controller;
                self::$controller->route = $route;
                if (method_exists(self::$controller, 'init')) call_user_func([self::$controller, 'init']);
                if (method_exists(self::$controller, 'forbidden')) call_user_func([self::$controller, 'forbidden']);
            }
        }

        /**
         * Calls the auto routing settings.
         * @param string $controller Controller name.
         * @param string $action Action name.
         * @param string $route Request route.
         * @param array $params (Optional) Optional URI parameters.
         */
        private static function callAutoRoute(string $controller, string $action, string $route, array $params = []){
            if (class_exists($controller)) {
                self::$controller = new $controller;
                self::$controller->route = $route;
                if (method_exists(self::$controller, $action)) {
                    if (!empty($params)){
                        foreach($params as $key => $value){
                            $params['param' . ($key + 1)] = $value;
                            unset($params[$key]);
                        }
                        self::$controller->params = new Element($params);
                    }
                    if (method_exists(self::$controller, 'init')) call_user_func([self::$controller, 'init']);
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