<?php

namespace Glowie\Core\Queue;

/**
 * Job core for Glowie application.
 * @category Queue
 * @package glowieframework/glowie-core
 * @author Glowie
 * @copyright Copyright (c) Glowie
 * @license MIT
 * @link https://glowie.gabrielsilva.dev.br
 */
abstract class Job
{

    /**
     * Job data.
     * @var mixed
     */
    public $data = null;

    /**
     * Instantiates a new job.
     * @param mixed $data (Optional) Data to pass to the job.
     */
    final public function __construct($data = null)
    {
        $this->data = $data;
    }

    /**
     * Adds this job to the queue.
     * @param string $queue (Optional) Queue name to add this job to.
     * @param int $delay (Optional) Delay in seconds to run this job.
     */
    final public function dispatch(string $queue = 'default', int $delay = 0)
    {
        Queue::add(get_class($this), $this->data, $queue, $delay);
    }

    /**
     * Runs the job.
     */
    public abstract function run();
}
