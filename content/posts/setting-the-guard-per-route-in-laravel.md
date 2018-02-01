---
title: Setting the Guard Per Route in Laravel
date: 2016-07-22T12:00:00-05:00
aliases: [/2016/setting-the-guard-per-route-in-laravel/]
---

**edit** you will be able to do this natively in 5.3. The auth middleware will set the default guard and the user resolver.


I've been working with Laravel for awhile, and the [authentication](https://laravel.com/docs/master/authentication) that comes out of the box is pretty awesome.

Laravel let's you setup different guards which authenticate the user differently.  You might have a web guard that uses the session and cookies and an api guard that uses an OAuth token.

If you use two guards in the same app, you need to specify which one you want everytime you access it.

```php
<?php
$user = Auth::user();               // Uses the default guard
$user = Auth::guard('api')->user(); // You have to manually specify the guard
```

I thought it would be really nice to be able to set the guard for a route group like this:

```php
<?php
Route::group(['domain' => 'api.app.dev', guard' => 'api'], function () {
    // ...
});
```

That way I only need to set it once, and every time I call `Auth::user()` or inject the Guard I get the correct instance.

After a little digging, I found out that Laravel emits an event when it matches a route.  [The event is called RouteMatched](https://github.com/laravel/framework/blob/5.2/src/Illuminate/Routing/Events/RouteMatched.php) and you can access the current route from it.

First I registerer a listener for the event.  The listener gets the current route, and checks if the route has the 'guard' attribute.  I added the code to my AppServiceProvider in the boot method.

Next I needed to hook into authentication and set the guard.  When you call `Auth::user()` or `auth()`, what actually gets loaded is the [`AuthManager`](https://github.com/laravel/framework/blob/5.2/src/Illuminate/Auth/AuthManager.php).  The AuthManager is aliased as 'auth', so I just used that to access it.

The first method, `resolveUsersUsing`, lets you set a callback which will be invoked when someone tries to get the current user from the auth manager.  I thought that would be what get's called when you call `Auth::user()`, but it's not ¯\_(ツ)_/¯.  Apparently that's used to resolve the `Authenticatable` contract, when making a new `Gate`, and when you call `$request->user()`.

The second method sets the default driver.  That's what actually gets used when you inject the guard or call `Auth::user()`.  I figured that out by reading the [auth service provider](https://github.com/laravel/framework/blob/5.2/src/Illuminate/Auth/AuthServiceProvider.php).

The whole thing looks like this:

```php
<?php
$this->app['router']->matched(function (\Illuminate\Routing\Events\RouteMatched $event) {
    $route = $event->route;
    if (!array_has($route->getAction(), 'guard')) {
        return;
    }
    $routeGuard = array_get($route->getAction(), 'guard');
    $this->app['auth']->resolveUsersUsing(function ($guard = null) use ($routeGuard) {
        return $this->app['auth']->guard($routeGuard)->user();
    });
    $this->app['auth']->setDefaultDriver($routeGuard);
});
```

Now our routes can specify a guard, and the default guard will be swapped to that one as soon as the route is resolved.  Awesome 👍

```php
<?php
Route::group(['guard' => 'api'], function () {
    Route::get('/api/whoami', function () {
        return Auth::user()->toJson();
    });
});

Route::group(['guard' => 'web'], function () {
    Route::get('/whoami', function () {
        $name = Auth::user()->name;
        return "hello {$name}!";
    });
});
```
