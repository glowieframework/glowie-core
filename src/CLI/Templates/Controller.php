<?php

namespace Glowie\Controllers;

/**
 * __FIREFLY_TEMPLATE_NAME__ controller for Glowie application.
 * @category Controller
 * @package glowieframework/glowie
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/basic-application-modules/controllers
 */
class __FIREFLY_TEMPLATE_NAME__ extends BaseController
{

    /**
     * This method will be called before any other methods from this controller.
     */
    public function init()
    {
        // Calls the BaseController init() method
        if (is_callable([parent::class, 'init'])) parent::init();
    }

    /**
     * Index action.
     */
    public function index()
    {
        // Create something awesome
    }
}
