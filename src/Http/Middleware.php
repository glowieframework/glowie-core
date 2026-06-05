<?php

namespace Glowie\Core\Http;

use Glowie\Core\Element;
use Glowie\Core\Exception\HttpException;

/**
 * Middleware core for Glowie application.
 * @category Middleware
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/basic-application-modules/middlewares
 *
 * @method void init() This method will be called before any other methods from this middleware.
 * @method void success() Called if the middleware handler returns true.
 * @method void fail() Called if the middleware handler returns false.
 */
abstract class Middleware
{

    /**
     * Controller that this middleware is handling.
     * @var Controller
     */
    protected $controller;

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
     * Creates a new instance of the middleware.
     */
    public function __construct($alias = null)
    {
        $this->controller = Rails::getController();
        $this->get = Rails::getRequest()->fromGet();
        $this->params = Rails::getParams();
        $this->post = Rails::getRequest()->fromPost();
        $this->request = Rails::getRequest();
        $this->response = Rails::getResponse();
        $this->route = Rails::getCurrentRoute();
        $this->session = Session::make();
        $this->alias = $alias;
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
     * The middleware handler.
     * @return bool Should return true on success or false on fail.
     */
    public abstract function handle();
}
