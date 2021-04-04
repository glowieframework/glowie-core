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
        private $controller;

        /**
         * Setup a new route for the application.
         * @param string $route The route URI to setup.
         * @param string $controller (Optional) The controller name that this route will instantiate.
         * @param string $action (Optional) The action name from the controller that this route will instantiate.
         * @param string[] $methods (Optional) Array of allowed HTTP methods that this route accepts. Leave empty for all.
         */
        public static function addRoute(string $route, string $controller = 'main', string $action = 'index', array $methods = []){
            $GLOBALS['glowieRoutes']['routes'][$route] = [
                'controller' => $controller,
                'action' => $action,
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
        public function init(){
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
                    if(!in_array(strtolower($_SERVER['REQUEST_METHOD']), $config['methods'])) return $this->callForbidden($route);
                }

                // Check if there is a redirect configuration
                if(empty($config['redirect'])){
                    // Gets the controller
                    $controller = 'Glowie\Controllers\\' . $this->parseName($config['controller'], true);
                    $flowController = $config['controller'];

                    // If controller class does not exists, trigger an error
                    if (!class_exists($controller)){
                        trigger_error('Rails: Controller "' . str_replace('Glowie\Controllers\\', '', $controller) . '" not found');
                        exit;
                    }

                    // Gets the action
                    $action = $this->parseName($config['action']);

                    // Instantiates new controller
                    $this->controller = new $controller;
                    $this->controller->flow->controller = trim(strtolower($flowController));
                    $this->controller->flow->action = trim(strtolower($action));
                    $this->controller->flow->route = trim(strtolower($route));
                    $this->controller->flow->method = trim(strtolower($_SERVER['REQUEST_METHOD']));

                    // If action does not exists, trigger an error
                    if (method_exists($this->controller, $action)) {
                        // Parses URI parameters, if available
                        if (!empty($result)) $this->controller->params = new Element($result);

                        // Calls action
                        if (method_exists($this->controller, 'init')) call_user_func([$this->controller, 'init']);
                        call_user_func([$this->controller, $action]);
                    } else {
                        trigger_error('Rails: Action "' . $action . '()" not found in ' . str_replace('Glowie\Controllers\\', '', $controller));
                        exit;
                    }
                }else{
                    // Redirects to the target URL
                    Util::redirect($config['redirect']);
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
                        $this->callAutoRoute($controller, $action, [
                            'controller' => 'main', 
                            'action' => 'index', 
                            'route' => '/'
                        ]);

                    // If only the controller was specified
                    }else if(count($autoroute) == 1){
                        $controller = 'Glowie\Controllers\\' . $this->parseName($autoroute[0], true);
                        $action = 'index';
                        $this->callAutoRoute($controller, $action , [
                            'controller' => $autoroute[0],
                            'action' => 'index',
                            'route' => $route
                        ]);

                    // Controller and action were specified
                    }else if(count($autoroute) == 2){
                        $controller = 'Glowie\Controllers\\' . $this->parseName($autoroute[0], true);
                        $action = $this->parseName($autoroute[1]);
                        $this->callAutoRoute($controller, $action, [
                            'controller' => $autoroute[0],
                            'action' => $autoroute[1],
                            'route' => $route
                        ]);
                    
                    // Controller, action and parameters were specified
                    }else{
                        $controller = 'Glowie\Controllers\\' . $this->parseName($autoroute[0], true);
                        $action = $this->parseName($autoroute[1]);
                        $params = array_slice($autoroute, 2);
                        $this->callAutoRoute($controller, $action, [
                            'controller' => $autoroute[0],
                            'action' => $autoroute[1],
                            'route' => $route
                        ], $params);
                    }
                }else{
                    // Route was not found
                    $this->callNotFound($route);
                }
            }
        }

        /**
         * Parses names to camelCase convention. It also removes all accents and characters that are not\
         * valid letters, numbers or underscores.
         * @param string $string Name to be parsed.
         * @param bool $firstUpper (Optional) Determines if the first character should be uppercase.
         * @return string Parsed name.
         */
        private function parseName(string $string, bool $firstUpper = false){
            $string = strtr(utf8_decode(strtolower($string)), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $string = preg_replace('/[^a-zA-Z0-9_]/', ' ', $string);
            if($firstUpper){
                return str_replace(' ', '', ucwords($string));
            }else{
                return str_replace(' ', '', lcfirst(ucwords($string)));
            }
        }

        /**
         * Calls notFound() in ErrorController.
         * @param string $route Current triggered route.
         */
        private function callNotFound(string $route){
            http_response_code(404);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                $this->controller = new $controller;
                $this->controller->flow->controller = 'error';
                $this->controller->flow->action = 'not-found';
                $this->controller->flow->route = trim(strtolower($route));
                $this->controller->flow->method = strtolower($_SERVER['REQUEST_METHOD']);
                if (method_exists($this->controller, 'init')) call_user_func([$this->controller, 'init']);
                if (method_exists($this->controller, 'notFound')) call_user_func([$this->controller, 'notFound']);
            }
        }

        /**
         * Calls forbidden() in ErrorController.
         * @param string $route Current triggered route.
         */
        private function callForbidden(string $route){
            http_response_code(403);
            $controller = 'Glowie\Controllers\Error';
            if (class_exists($controller)) {
                $this->controller = new $controller;
                $this->controller->flow->controller = 'error';
                $this->controller->flow->action = 'forbidden';
                $this->controller->flow->route = trim(strtolower($route));
                $this->controller->flow->method = strtolower($_SERVER['REQUEST_METHOD']);
                if (method_exists($this->controller, 'init')) call_user_func([$this->controller, 'init']);
                if (method_exists($this->controller, 'forbidden')) call_user_func([$this->controller, 'forbidden']);
            }
        }

        /**
         * Performs checking and calls the auto routing parameters.
         * @param string $controller Controller name.
         * @param string $action Action name.
         * @param array $flowData Current flow parameters.
         * @param array $params (Optional) Optional URI parameters.
         */
        private function callAutoRoute(string $controller, string $action, array $flowData, array $params = []){
            if (class_exists($controller)) {
                $this->controller = new $controller;
                $this->controller->flow->controller = trim(strtolower($flowData['controller']));
                $this->controller->flow->action = trim(strtolower($flowData['action']));
                $this->controller->flow->route = trim(strtolower($flowData['route']));
                $this->controller->flow->method = trim(strtolower($_SERVER['REQUEST_METHOD']));
                if (method_exists($this->controller, $action)) {
                    if (!empty($params)){
                        foreach($params as $key => $value){
                            $params['param' . ($key + 1)] = $value;
                            unset($params[$key]);
                        }
                        $this->controller->params = new Element($params);
                    }
                    if (method_exists($this->controller, 'init')) call_user_func([$this->controller, 'init']);
                    call_user_func([$this->controller, $action]);
                } else {
                    $this->callNotFound($flowData['route']);
                };
            } else {
                $this->callNotFound($flowData['route']);
            }
        }
    }

?>