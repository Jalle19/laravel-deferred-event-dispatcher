<?php

namespace Jalle19\Laravel\Events\Tests;

use Illuminate\Contracts\Events\Dispatcher;

/**
 * Class DummyEventSubscriber
 * @package Jalle19\Laravel\Events\Tests
 */
class DummyEventSubscriber
{

    /**
     * @param Dispatcher $dispatcher
     */
    public function subscribe(Dispatcher $dispatcher)
    {
        $dispatcher->listen('foo', function () {

        });

        $dispatcher->listen('bar', function () {

        });

        $dispatcher->listen('baz', function () {

        });
    }
}
