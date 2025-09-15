<?php

namespace Glowie\Commands;

use Glowie\Core\CLI\Command;
use Glowie\Core\CLI\Scheduler;

/**
 * Task scheduler CLI command for Glowie application.
 * @category Command
 * @package glowieframework/glowie
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 * @see https://glowie.gabrielsilva.dev.br/docs/latest/extra/cli
 */
class __FIREFLY_TEMPLATE_NAME__ extends Command
{

    /**
     * The command script.
     */
    public function run()
    {
        // Define your scheduled tasks here
        Scheduler::schedule(function () {
            // Your task
        })->daily();

        // At the end, call the run method
        return Scheduler::run();
    }
}
