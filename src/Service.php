<?php

namespace Glowie\Core;

use Glowie\Core\Http\Rails;
use Glowie\Core\Http\Session;
use Util;

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
        $this->get = !Util::isCLI() ? Rails::getRequest()->fromGet() : new Element();
        $this->params = Rails::getParams();
        $this->post = !Util::isCLI() ? Rails::getRequest()->fromPost() : new Element();
        $this->request = !Util::isCLI() ? Rails::getRequest() : new Element();
        $this->route = Rails::getCurrentRoute();
        $this->session = !Util::isCLI() ? new Session() : new Element();
    }

    /**
     * Creates a new service instance in a static-binding.
     * @return $this New service instance.
     */
    public static function make()
    {
        return new static;
    }
}
