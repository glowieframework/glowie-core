<?php

namespace Glowie\Core\Http;

use Glowie\Core\Element;
use Glowie\Core\Exception\HttpException;
use Glowie\Core\Tools\Validator;
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
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/basic-application-modules/controllers
 *
 * @method void init() This method will be called before any other methods from this controller.
 * @method void action($callback, $params) Calls the action callback.
 * @method void badRequest() Handler for 400 Bad Request errors.
 * @method void unauthorized() Handler for 401 Unauthorized errors.
 * @method void forbidden() Handler for 403 Forbidden errors.
 * @method void notFound() Handler for 404 Not Found errors.
 * @method void methodNotAllowed() Handler for 405 Method Not Allowed errors.
 * @method void notAcceptable() Handler for 406 Not Acceptable errors.
 * @method void requestTimeout() Handler for 408 Request Timeout errors.
 * @method void conflict() Handler for 409 Conflict errors.
 * @method void gone() Handler for 410 Gone errors.
 * @method void contentTooLarge() Handler for 413 Content Too Large errors.
 * @method void pageExpired() Handler for 419 Page Expired errors.
 * @method void unprocessableEntity() Handler for 422 Unprocessable Entity errors.
 * @method void tooManyRequests() Handler for 429 Too Many Requests errors.
 * @method void requestHeaderFieldsTooLarge() Handler for 431 Request Header Fields Too Large errors.
 * @method void unavailableForLegalReasons() Handler for 451 Unavailable For Legal Reasons errors.
 * @method void internalServerError() Handler for 500 Internal Server Error errors.
 * @method void notImplemented() Handler for 501 Not Implemented errors.
 * @method void badGateway() Handler for 502 Bad Gateway errors.
 * @method void serviceUnavailable() Handler for 503 Service Unavailable errors.
 * @method void gatewayTimeout() Handler for 504 Gateway Timeout errors.
 */
class Controller
{

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
     * Alias parameters.
     * @var string|null
     */
    protected $alias;

    /**
     * Data to pass globally to views.
     * @var Element
     */
    public $view;

    /**
     * Creates a new instance of the controller.
     */
    public function __construct($alias = null)
    {
        $this->request = Rails::getRequest();
        $this->get = $this->request->fromGet();
        $this->post = $this->request->fromPost();
        $this->params = Rails::getParams();
        $this->response = Rails::getResponse();
        $this->route = Rails::getCurrentRoute();
        $this->session = Session::make();
        $this->alias = $alias;
        $this->view = new Element();
    }

    /**
     * Renders a view file.
     * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
     * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
     * @param bool $absolute (Optional) Use an absolute path for the view file.
     * @see https://glowie.gabrielsilva.dev.br/docs/latest/basic-application-modules/views
     */
    final public function renderView(string $view, array $params = [], bool $absolute = false)
    {
        $view = new View($view, $params, false, $absolute);
        echo $view->getContent();
    }

    /**
     * Renders a layout file.
     * @param string $layout Layout filename. Must be a **.phtml** file inside **app/views/layouts** folder, extension is not needed.
     * @param string|null $view (Optional) View filename to render within layout. You can place its content by using `$this->getView()`\
     * inside the layout file. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
     * @param array $params (Optional) Parameters to pass into the rendered view and layout. Should be an associative array with each variable name and value.
     * @param bool $absolute (Optional) Use an absolute path for the layout and view files.
     * @see https://glowie.gabrielsilva.dev.br/docs/latest/basic-application-modules/layouts
     */
    final public function renderLayout(string $layout, ?string $view = null, array $params = [], bool $absolute = false)
    {
        $layout = new Layout($layout, $view, $params, $absolute);
        echo $layout->getContent();
    }

    /**
     * Renders a view file in a private scope. No global or parent view properties will be inherited.
     * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
     * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
     * @param bool $absolute (Optional) Use an absolute path for the view file.
     * @see https://glowie.gabrielsilva.dev.br/docs/latest/basic-application-modules/views
     */
    final public function renderPartial(string $view, array $params = [], bool $absolute = false)
    {
        $view = new View($view, $params, true, $absolute);
        echo $view->getContent();
    }

    /**
     * Renders a raw view code using Skeltch engine.
     * @param string $content View content in HTML.
     * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
     * @see https://glowie.gabrielsilva.dev.br/docs/latest/basic-application-modules/views
     */
    final public function renderInline(string $content, array $params = [])
    {
        $filename = Util::location('storage/cache/' . md5($content) . '.phtml');
        file_put_contents($filename, $content);
        $view = new View($filename, $params, false, true);
        echo $view->getContent();
    }

    /**
     * Validates the request data and throws an HttpException if the validation fails.
     * @param array $rules Associative array with validation rules for each field.
     * @param bool $bail (Optional) Stop validation of each field after first failure found.
     * @param bool $bailAll (Optional) Stop validation of all fields after first failure found.
     * @param array $customMessages (Optional) An associative array with the custom validation error messages.
     * @throws HttpException Throws an HttpException with status code 400 if the validation fails.
     * @return bool Returns true if the validation passes, false otherwise.
     */
    final public function validate(array $rules, bool $bail = true, bool $bailAll = true, array $customMessages = [])
    {
        $response = $this->request->validate($rules, $bail, $bailAll, $customMessages);
        if (!$response) throw new HttpException(400);
        return $response;
    }

    /**
     * Gets the Validator instance used to validate the request data.
     * @return Validator The validator instance.
     */
    final public function getValidator()
    {
        return $this->request->getValidator();
    }
}
