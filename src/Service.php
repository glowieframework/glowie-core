<?php

namespace Glowie\Core;

use Glowie\Core\Http\Rails;
use Glowie\Core\Http\Session;

/**
 * Service core for Glowie application.
 * @category Service
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
class Service
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
     * Creates a new instance of the service.
     */
    public function __construct()
    {
        $this->get = Rails::getRequest()->fromGet();
        $this->params = Rails::getParams();
        $this->post = Rails::getRequest()->fromPost();
        $this->request = Rails::getRequest();
        $this->route = Rails::getCurrentRoute();
        $this->session = new Session();
    }
}
