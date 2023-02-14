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
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
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
         */
        final public function __construct(){
            $this->get = new Element($_GET);
            $this->params = new Element(Rails::getParams());
            $this->post = new Element($_POST);
            $this->request = Rails::getRequest();
            $this->response = Rails::getResponse();
            $this->route = Rails::getCurrentRoute();
            $this->session = new Session();
            $this->view = new Element();
        }

        /**
         * Renders a view file.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         */
        final public function renderView(string $view, array $params = []){
            $view = new View($view, $params);
            echo $view->getContent();
        }

        /**
         * Renders a layout file.
         * @param string $layout Layout filename. Must be a **.phtml** file inside **app/views/layouts** folder, extension is not needed.
         * @param string|null $view (Optional) View filename to render within layout. You can place its content by using `$this->getView()`\
         * inside the layout file. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the rendered view and layout. Should be an associative array with each variable name and value.
         */
        final public function renderLayout(string $layout, ?string $view = null, array $params = []){
            $layout = new Layout($layout, $view, $params);
            echo $layout->getContent();
        }

        /**
         * Renders a view file in a private scope. No global or parent view properties will be inherited.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         */
        final public function renderPartial(string $view, array $params = []){
            $view = new View($view, $params, true);
            echo $view->getContent();
        }

    }

?>