<?php

namespace Jalle19\Laravel\Events;

use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;

/**
 * Class DeferredEventDispatcher
 * @package Jalle19\Laravel\Events
 */
class DeferredEventDispatcher extends Dispatcher
{

    /**
     * @var array
     */
    protected $unresolvedListeners = [];

    /**
     * @var array
     */
    protected $unresolvedSubscribers = [];

    /**
     * @var array
     */
    protected $deferredEvents = [];

    /**
     * DeferredEventDispatcher constructor.
     *
     * @param Container|null $container
     * @param array          $deferredEvents events that should not trigger resolving of listeners/subscribers
     */
    public function __construct(?Container $container = null, $deferredEvents = [])
    {
        parent::__construct($container);

        $this->deferredEvents = $deferredEvents;
    }

    /**
     * @inheritDoc
     */
    public function listen($events, $listener): void
    {
        $this->unresolvedListeners[] = [$events, $listener];
    }

    /**
     * @inheritDoc
     */
    public function subscribe($subscriber): void
    {
        $this->unresolvedSubscribers[] = $subscriber;
    }

    /**
     * @inheritDoc
     */
    public function dispatch($event, $payload = [], $halt = false): ?array
    {
        // Don't resolve subscribers and listeners for ignored events
        if (!$this->shouldDeferResolving($event)) {
            $this->resolveSubscribers();
            $this->resolveListeners();
        }

        return parent::dispatch($event, $payload, $halt);
    }

    /**
     * Resolves all unresolved listeners
     */
    protected function resolveListeners(): void
    {
        foreach ($this->unresolvedListeners as $definition) {
            [$events, $listener] = $definition;

            parent::listen($events, $listener);
        }

        $this->unresolvedListeners = [];
    }

    /**
     * Resolves all unresolved subscribers
     */
    protected function resolveSubscribers(): void
    {
        foreach ($this->unresolvedSubscribers as $subscriber) {
            parent::subscribe($subscriber);
        }

        $this->unresolvedSubscribers = [];
    }

    /**
     * Whether the specified event should trigger resolving of listeners/subscribers or not
     *
     * @param mixed $event
     *
     * @return bool
     */
    protected function shouldDeferResolving($event): bool
    {
        $eventName = \is_object($event) ? \get_class($event) : $event;

        return \in_array($eventName, $this->deferredEvents, true);
    }
}
