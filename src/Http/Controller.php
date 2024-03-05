<?php
    namespace Glowie\Core\Http;

    use Glowie\Core\Element;
    use Glowie\Core\View\View;
    use Glowie\Core\View\Layout;
    use Util;

    /**
     * Controller core for Glowie application.
     * @category Controller
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://gabrielsilva.dev.br/glowie
     *
     * @method void init() This method will be called before any other methods from this controller.
     * @method void action($callback) Calls the action callback.
     * @method void notFound() Handler for 404 Not Found errors.
     * @method void forbidden() Handler for 403 Forbidden errors.
     * @method void methodNotAllowed() Handler for 405 Method Not Allowed errors.
     * @method void serviceUnavailable() Handler for 503 Service Unavailable errors.
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
            $this->get = Rails::getRequest()->fromGet();
            $this->params = Rails::getParams();
            $this->post = Rails::getRequest()->fromPost();
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
        final public function renderView(string $view, array $params = [], bool $absolute = false){
            $view = new View($view, $params, false, $absolute);
            echo $view->getContent();
        }

        /**
         * Renders a layout file.
         * @param string $layout Layout filename. Must be a **.phtml** file inside **app/views/layouts** folder, extension is not needed.
         * @param string|null $view (Optional) View filename to render within layout. You can place its content by using `$this->getView()`\
         * inside the layout file. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the rendered view and layout. Should be an associative array with each variable name and value.
         * @param bool $absolute (Optional) Use an absolute path for the view file.
         */
        final public function renderLayout(string $layout, ?string $view = null, array $params = [], bool $absolute = false){
            $layout = new Layout($layout, $view, $params, $absolute);
            echo $layout->getContent();
        }

        /**
         * Renders a view file in a private scope. No global or parent view properties will be inherited.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         * @param bool $absolute (Optional) Use an absolute path for the view file.
         */
        final public function renderPartial(string $view, array $params = [], bool $absolute = false){
            $view = new View($view, $params, true, $absolute);
            echo $view->getContent();
        }

        /**
         * Renders a raw view code using Skeltch engine.
         * @param string $view View content in HTML.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         */
        final public function renderInline(string $content, array $params = []){
            $filename = Util::location('storage/cache/' . md5($content) . '.phtml');
            file_put_contents($filename, $content);
            $view = new View($filename, $params, false, true);
            echo $view->getContent();
        }

    }

?>