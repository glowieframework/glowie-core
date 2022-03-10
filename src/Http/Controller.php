<?php
    namespace Glowie\Core\Http;

    use Glowie\Core\Element;
    use Glowie\Core\View\View;
    use Glowie\Core\View\Layout;

    /**
     * Controller core for Glowie application.
     * @category Controller
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.2
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
         * Data to pass globally to views.
         * @var Element
         */
        public $view;

        /**
         * Creates a new instance of the controller.
         * @param string $route (Optional) Requested route.
         * @param array $params (Optional) Route parameters.
         */
        final public function __construct(string $route = '', array $params = []){
            $this->get = new Element($_GET);
            $this->params = new Element($params);
            $this->post = new Element($_POST);
            $this->request = Rails::getRequest();
            $this->response = Rails::getResponse();
            $this->route = $route;
            $this->session = new Session();
            $this->view = new Element();
        }

        /**
         * Renders a view file.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         * @return void
         */
        final public function renderView(string $view, array $params = []){
            return new View($view, $params);
        }

        /**
         * Renders a layout file.
         * @param string $layout Layout filename. Must be a **.phtml** file inside **app/views/layouts** folder, extension is not needed.
         * @param string|null $view (Optional) View filename to render within layout. You can place its content by using `$this->getContent()`\
         * inside the layout file. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the rendered view and layout. Should be an associative array with each variable name and value.
         * @return void
         */
        final public function renderLayout(string $layout, ?string $view = null, array $params = []){
            return new Layout($layout, $view, $params);
        }

    }

?>