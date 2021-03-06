<?php
    namespace Glowie\Core;

    use Util;

    /**
     * Controller core for Glowie application.
     * @category Controller
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Controller{
        
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
         * Current instantiated route.
         * @var string
         */
        protected $route;

        /**
         * Web server parameters.
         * @var Element
         */
        protected $server;

        /**
         * Session parameters.
         * @var Session
         */
        protected $session;

        /**
         * Data bridge between controller and view.
         * @var Element
         */
        public $view;

        /**
         * Instantiates a new instance of the controller.
         * @param string $route (Optional) Request route.
         * @param array $params (Optional) Route parameters.
         */
        public function __construct(string $route = '', array $params = []){
            $this->get = new Element($_GET);
            $this->params = new Element($params);
            $this->post = new Element($_POST);
            $this->route = $route;
            $this->server = new Element($_SERVER);
            $this->session = new Session();
            $this->view = new Element();
        }

        /**
         * Renders a view file.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         * @param bool $skeltch (Optional) Use Skeltch templating engine to compile the view.
         * @return void
         */
        public function renderView(string $view, array $params = [], bool $skeltch = false){
            if(!is_array($params)) trigger_error('renderView: $params must be an array', E_USER_ERROR);
            $view = '../views/' . $view . (!Util::endsWith($view, '.phtml') ? '.phtml' : '');
            if(file_exists($view)){
                return new View($view, $params, $skeltch, true, $this);
            }else{
                trigger_error('renderView: View file "' . str_replace('../', 'app/', $view) .'" not found', E_USER_ERROR);
            }
        }

        /**
         * Renders a layout file.
         * @param string $layout Layout filename. Must be a **.phtml** file inside **app/views/layouts** folder, extension is not needed.
         * @param string $view (Optional) View filename to render within layout. You can place its content by using `$this->getContent()`\
         * inside the layout file. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the rendered view and layout. Should be an associative array with each variable name and value.
         * @param bool $skeltch (Optional) Use Skeltch templating engine to compile the layout and view.
         * @return void
         */
        public function renderLayout(string $layout, string $view = '', array $params = [], bool $skeltch = false){
            if (!is_array($params)) trigger_error('renderLayout: $params must be an array', E_USER_ERROR);
            $layout = '../views/layouts/' . $layout . (!Util::endsWith($layout, '.phtml') ? '.phtml' : '');
            if(!empty($view)){
                $view = '../views/' . $view . (!Util::endsWith($view, '.phtml') ? '.phtml' : '');
                if (file_exists($layout)) {
                    if(file_exists($view)){
                        return new Layout($layout, $view, $params, $skeltch, $this);
                    }else{
                        trigger_error('renderLayout: View file "' . str_replace('../', 'app/', $view) .'" not found', E_USER_ERROR);
                    }
                } else {
                    trigger_error('renderLayout: Layout file "' . str_replace('../', 'app/', $layout) .'" not found', E_USER_ERROR);
                }
            }else{
                if (file_exists($layout)) {
                    return new Layout($layout, '', $params, $skeltch, $this);
                } else {
                    trigger_error('renderLayout: Layout file "' . str_replace('../', 'app/', $layout) .'" not found', E_USER_ERROR);
                }
            }
        }

    }

?>