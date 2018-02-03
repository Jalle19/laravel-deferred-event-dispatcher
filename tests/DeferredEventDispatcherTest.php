<?php

namespace Jalle19\Laravel\Events\Tests;

use Illuminate\Contracts\Container\Container;
use Jalle19\Laravel\Events\DeferredEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class DeferredEventDispatcherTest
 * @package Jalle19\Laravel\Events\Tests
 */
class DeferredEventDispatcherTest extends TestCase
{

    /**
     * @throws \Exception
     */
    public function testEventHandling()
    {
        // Create a dispatcher that ignores "baz" events and DummyEvents
        $dispatcher = new DeferredEventDispatcher($this->getMockedContainer(), [
            'baz',
            DummyEvent::class,
        ]);

        // No listeners should be registered out of the box
        $this->assertCount(0, $dispatcher->getListeners('foo'));

        // Add a new listener, actual listeners should still be zero
        $dispatcher->listen('foo', [$this, 'dummyHandler']);
        $this->assertCount(0, $dispatcher->getListeners('foo'));

        // Add an event subscriber, actual event listeners should still be zero
        $dispatcher->subscribe(new DummyEventSubscriber());
        $this->assertCount(0, $dispatcher->getListeners('foo'));

        // Dispatch an ignored events, actual event listeners should still be zero
        $dispatcher->dispatch(new DummyEvent());
        $this->assertCount(0, $dispatcher->getListeners('foo'));

        // Dispatch a non-ignored event, now the listeners should be registered
        $dispatcher->dispatch('foo');
        $this->assertCount(2, $dispatcher->getListeners('foo'));
        $this->assertCount(1, $dispatcher->getListeners('bar'));

        // Also ignored events should be registered now
        $this->assertCount(1, $dispatcher->getListeners('baz'));
    }

    /**
     *
     */
    public function dummyHandler()
    {

    }

    /**
     * @return MockObject|Container
     */
    private function getMockedContainer()
    {
        return $this->getMockBuilder(Container::class)
                    ->getMock();
    }
}

