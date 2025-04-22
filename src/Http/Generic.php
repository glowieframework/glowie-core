<?php

namespace Glowie\Core\Http;

/**
 * Generic controller for Glowie application.
 * @category Controller
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/basic-application-modules/controllers
 */
class Generic extends Controller
{

    /**
     * Request GET parameters.
     * @var Element
     */
    public $get;

    /**
     * URI parameters.
     * @var Element
     */
    public $params;

    /**
     * Request POST parameters.
     * @var Element
     */
    public $post;

    /**
     * Request handler.
     * @var Request
     */
    public $request;

    /**
     * Response handler.
     * @var Response
     */
    public $response;

    /**
     * Current requested route.
     * @var string
     */
    public $route;

    /**
     * Session parameters.
     * @var Session
     */
    public $session;

    /**
     * Calls the action callback.
     * @param callable $callback Function to be called.
     */
    final public function action(callable $callback)
    {
        call_user_func_array($callback, [$this]);
    }
}
