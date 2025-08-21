<?php
declare(strict_types=1);

namespace App\Event;

class SimpleEventDispatcher implements EventDispatcherInterface
{
    /** @var array<string,callable[]> */
    private array $listeners = [];

    public function addListener(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $class = get_class($event);
        foreach ($this->listeners[$class] ?? [] as $l) {
            $l($event);
        }
    }
}
