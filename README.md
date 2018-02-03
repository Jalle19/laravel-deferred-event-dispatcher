# laravel-deferred-event-dispatcher

[![Build Status](https://travis-ci.org/Jalle19/laravel-deferred-event-dispatcher.svg?branch=master)](https://travis-ci.org/Jalle19/laravel-deferred-event-dispatcher)
[![Coverage Status](https://coveralls.io/repos/github/Jalle19/laravel-deferred-event-dispatcher/badge.svg?branch=master)](https://coveralls.io/github/Jalle19/laravel-deferred-event-dispatcher?branch=master)

A deferring event dispatcher for the Laravel and Lumen frameworks.

## Motivation

Events are a powerful feature of any framework. It allows you to decouple your code so that the logic that triggers an 
event doesn't have to know what should happen in reaction to that event.

While the default implementation in Laravel/Lumen is perfectly adequate in many cases, it is fairly naive, which can 
lead to subpar performance. Consider the following scenario:

1. You have your own `EventServiceProvider` where you have registered a bunch of event subscribers.
2. You register this service provider in your bootstrap file

Under the hood, whenever an `EventServiceProvider` is registered, the `EventDispatcher` will also be constructed. The 
event dispatcher in turn will go through all the event handlers you've defined and construct each of them 
so that it knows which listeners to trigger in the case of an event.

While this doesn't sound that bad, it means that even for the simplest of requests (e.g. a route that only prints "OK", 
e.g. a health check route) will cause all your event subscribers to be constructed, which can pull in a lot of code 
that you don't need to fulfill the request (e.g. Doctrine and other "heavy" services").

This wouldn't be so bad if it weren't for the fact that the majority of your requests probably won't be triggering any 
events, so constructing and registering all your event handlers is pointless. This event dispatcher 
implementation aims to solve that problem.

### Deferring the resolving of listeners and subscribers

A simple yet fairly effective solution is to defer the resolving of event handlers until an event is 
actually dispatched. This assures we don't do any unnecessary work in requests that don't trigger any events. But 
there's a problem...

A lot of services use events under the hood:

* Some service providers will dispatch events before and after they've booted to allow other service providers to 
hook into these and perform optional bootstraping logic
* The cache repository will dispatch `Illuminate/Cache/Events/CacheHit` and `Illuminate/Cache/Events/CacheMissed` 
events every time you attempt to retrieve something from your cache. Depending on your application, this is something 
that could happen during practically every request.

Even if we deferr the resolving of event handlers until an event is dispatched, chances are there is 
at least one event dispatched on every request anyway, defeating the whole point.

### Deferring specific events

Chances are you don't care about whether a cache operation resulted in a hit or a miss, or whether a service provider 
has been booted or not. Ignoring these events would defer the resolving of event handlers even further, 
possibly for the duration of the request, which finally means we've accomplished our goal of not constructing all 
event handlers needlessly.

## Requirements

* Laravel/Lumen >= 5.4
* PHP >= 7.1

## Usage

1. Install the library using Composer:

```bash
composer require jalle19/laravel-deferred-event-dispatcher
```

2. Swap the default event dispatcher for this one:

```php
// The event dispatcher must be a singleton
$app->singleton(\Jalle19\Laravel\Events\DeferredEventDispatcher::class, function () use ($app) {
    return new \Jalle19\Laravel\Events\DeferredEventDispatcher($app, [
        // Cache events
        Illuminate\Cache\Events\CacheHit::class,
        Illuminate\Cache\Events\CacheMissed::class,
    ]);
});

// Swap the default implementation for this one. Some classes type-hint the interface, others simply use "events"
$app->alias(\Jalle19\Laravel\Events\DeferredEventDispatcher::class, 'events');
$app->alias(\Jalle19\Laravel\Events\DeferredEventDispatcher::class, Illuminate\Contracts\Events\Dispatcher::class);
```

In this example we've decided to defer resolving of event handlers whenever a cache event is dispatched.

3. Make sure your event service provider is registered somewhere after these lines

That's it, your event handlers will now be resolved only when an event that you haven't explicitly deferred is 
dispatched!

## License

MIT
