<?php

    /**
     * Router and starting point for Glowie application.
     * @category Router
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 0.2-alpha
     */
    class Rails{
        /**
         * Current controller.
         * @var Glowie\Controller
         */
        private $controller;

        /**
         * Setup a new route for the application.
         * @param string $route The route URI to setup.
         * @param array $settings Route settings. Must be an associative array of valid route settings (see docs).
         */
        public static function addRoute(string $route, array $settings){
            if (!is_array($settings) || empty($settings)) return trigger_error('addRoute: $settings must be an array');
            $GLOBALS['glowieRoutes']['routes'][$route] = $settings;
        }

        /**
         * Sets the auto routing feature on or off.
         * @param bool $option (Optional) **True** for turning on auto routing (default) or **false** for turning it off.
         */
        public static function setAutoRouting(bool $option = true){
            $GLOBALS['glowieRoutes']['auto_routing'] = $option;
        }

        /**
         * Initializes application routes.
         */
        public function init(){
            // Clean request URI
            $appFolder = $GLOBALS['glowieConfig']['app_folder'];
            if(!Util::startsWith($appFolder, '/')) $appFolder = '/' . $appFolder;
            if(!Util::endsWith($appFolder, '/')) $appFolder = $appFolder . '/';
            $cleanRoute = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen($appFolder));
            
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
                    if(!is_array($config['methods'])) return trigger_error('Route methods setting must be an array of allowed methods');
                    if(!in_array(strtolower($_SERVER['REQUEST_METHOD']), $config['methods'])) return $this->callForbidden($route);
                }

                // Check if there is a redirect configuration
                if(empty($config['redirect'])){
                    // If controller was not specified, calls the MainController
                    if(!empty($config['controller'])){
                        $controller = $this->parseName($config['controller'], true) . 'Controller';
                        $flowController = $config['controller'];
                    }else{
                        $controller = 'MainController';
                        $flowController = 'main';
                    }

                    // If controller class does not exists, trigger an error
                    if (!class_exists($controller)){
                        trigger_error('Controller "' . $controller . '" not found');
                        exit;
                    }

                    // If action was not specified, calls the indexAction
                    !empty($config['action']) ? $action = $this->parseName($config['action']) : $action = 'index';

                    // Instantiates new controller
                    $this->controller = new $controller;
                    $this->controller->flow->controller = trim(strtolower($flowController));
                    $this->controller->flow->action = trim(strtolower($action));
                    $this->controller->flow->route = trim(strtolower($route));
                    $this->controller->flow->method = trim(strtolower($_SERVER['REQUEST_METHOD']));

                    // If action does not exists, trigger an error
                    if (method_exists($this->controller, $action  . 'Action')) {
                        // Parses URI parameters, if available
                        if (!empty($result)) $this->controller->params = new Glowie\Objectify($result);

                        // Calls action
                        if (method_exists($this->controller, 'init')) call_user_func([$this->controller, 'init']);
                        call_user_func([$this->controller, $action  . 'Action']);
                    } else {
                        trigger_error('Action "' . $action . 'Action()" not found in ' . $controller);
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
                        $controller = 'MainController';
                        $action = 'indexAction';
                        $this->callAutoRoute($controller, $action, [
                            'controller' => 'main', 
                            'action' => 'index', 
                            'route' => '/'
                        ]);

                    // If only the controller was specified
                    }else if(count($autoroute) == 1){
                        $controller = $this->parseName($autoroute[0], true) . 'Controller';
                        $action = 'indexAction';
                        $this->callAutoRoute($controller, $action , [
                            'controller' => $autoroute[0],
                            'action' => 'index',
                            'route' => $route
                        ]);

                    // Controller and action were specified
                    }else if(count($autoroute) == 2){
                        $controller = $this->parseName($autoroute[0], true) . 'Controller';
                        $action = $this->parseName($autoroute[1]) . 'Action';
                        $this->callAutoRoute($controller, $action, [
                            'controller' => $autoroute[0],
                            'action' => $autoroute[1],
                            'route' => $route
                        ]);
                    
                    // Controller, action and parameters were specified
                    }else{
                        $controller = $this->parseName($autoroute[0], true) . 'Controller';
                        $action = $this->parseName($autoroute[1]) . 'Action';
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
         * @param string $string Name to be encoded.
         * @param bool $firstUpper (Optional) Determines if the first character should be uppercase.
         * @return string Encoded string.
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
         * Calls notFoundAction() in ErrorController.
         * @param string $route Current triggered route.
         */
        private function callNotFound(string $route){
            http_response_code(404);
            $controller = 'ErrorController';
            if (class_exists($controller)) {
                $this->controller = new $controller;
                $this->controller->flow->controller = 'error';
                $this->controller->flow->action = 'not-found';
                $this->controller->flow->route = trim(strtolower($route));
                $this->controller->flow->method = strtolower($_SERVER['REQUEST_METHOD']);
                if (method_exists($this->controller, 'init')) call_user_func([$this->controller, 'init']);
                if (method_exists($this->controller, 'notFoundAction')) call_user_func([$this->controller, 'notFoundAction']);
            }
        }

        /**
         * Calls forbiddenAction() in ErrorController.
         * @param string $route Current triggered route.
         */
        private function callForbidden(string $route){
            http_response_code(403);
            $controller = 'ErrorController';
            if (class_exists($controller)) {
                $this->controller = new $controller;
                $this->controller->flow->controller = 'error';
                $this->controller->flow->action = 'forbidden';
                $this->controller->flow->route = trim(strtolower($route));
                $this->controller->flow->method = strtolower($_SERVER['REQUEST_METHOD']);
                if (method_exists($this->controller, 'init')) call_user_func([$this->controller, 'init']);
                if (method_exists($this->controller, 'forbiddenAction')) call_user_func([$this->controller, 'forbiddenAction']);
            }
        }

        /**
         * Performs checking and calls the auto routing parameters.
         * @param string $controller Controller class.
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
                            $params['segment' . ($key + 1)] = $value;
                            unset($params[$key]);
                        }
                        $this->controller->params = new Glowie\Objectify($params);
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