<?php

namespace Glowie\Controllers;

use Glowie\Core\Http\Controller;

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
class __FIREFLY_TEMPLATE_NAME__ extends Controller
{

    /**
     * This method will be called before any other methods from this controller.
     */
    public function init()
    {
        //
    }

    /**
     * Route to list the resources.
     * GET /{resource}
     */
    public function index()
    {
        // Get the resource models and render the list view here
    }

    /**
     * Route to view an existing resource.
     * GET /{resource}/:id
     */
    public function show()
    {
        $id = $this->params->id;
        // Get the resource model and render the show view here
    }

    /**
     * Route to show the create form for a new resource.
     * GET /{resource}/create
     */
    public function create()
    {
        // Render the create view here
    }

    /**
     * Route to save a new resource.
     * POST /{resource}
     */
    public function store()
    {
        $data = $this->post;
        // Create the resource model here
    }

    /**
     * Route to show the edit form for an existing resource.
     * GET /{resource}/:id
     */
    public function edit()
    {
        $id = $this->params->id;
        // Get the resource model and render the edit view here
    }

    /**
     * Route to update an existing resource.
     * PATCH /{resource}/:id
     */
    public function update()
    {
        $id = $this->params->id;
        $data = $this->post;
        // Update the resource model here
    }

    /**
     * Route to delete an existing resource.
     * DELETE /{resource}/:id
     */
    public function destroy()
    {
        $id = $this->params->id;
        // Delete the resource model here
    }
}
