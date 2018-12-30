<?php
declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://hyperf.org
 * @document https://wiki.hyperf.org
 * @contact  group@hyperf.org
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Event;

class ListenerData
{
    /**
     * @var string
     */
    public $event;

    /**
     * @var callable
     */
    public $listener;

    /**
     * @var int
     */
    public $priority;

    public function __construct(string $event, callable $listener, int $priority)
    {
        $this->event = $event;
        $this->listener = $listener;
        $this->priority = $priority;
    }
}
