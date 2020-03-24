---
title: "Passport Guide Part I: Building the Authorization server"
date: 2020-03-24
draft: false
---

## Introduction

This is part I of a multi-part blog series about [Laravel Passport](https://laravel.com/docs/7.x/passport). We're going to build an authorization server and a few of the popular client types most servers will need to support. Along the way I'll explain best practices and provide guidance around the security decisions you have to make.

In part I we will build the Passport Authorization server. The Authorization server hosts your login page, password reset page, and any other authentication pages. Typically it will also host the API that's being protected.

If at any point you get stuck check the [example application on Github](https://github.com/matt-allan/passport-guide-server). You can [view the commits](https://github.com/matt-allan/passport-guide-server/commits/master) to see what changes were made in each section of the guide.

## Setting up Laravel

To get started, create a new Laravel application. The simplest way to get started is to use the [Laravel Installer](https://laravel.com/docs/7.x/installation#installing-laravel). Use the `--auth` flag to scaffold the default authentication when the application is created.

```
laravel new --auth passport
```

Next you need to configure a database. Open the `.env` file with your editor and add the connection information for your database. If you aren't using Laravel Homestead's default database you will need to create a database using the GUI or CLI client for your database of choice.

Once the database is configured you can run the `migrate` command. This will create the default `users` table as well as the tables needed for Laravel's default authentication.

Finally you need to make the server available at the `passport.test` domain. Later we are going to build OAuth flows that redirect between different domains so it's important you use actual domains -- accessing the server at `localhost` isn't going to cut it. The exact steps depend on your development environment.

If you are using Laravel Homestead you will need to add a mapping to your `homestead.yaml` file.

```
sites:
    - map: passport.test
      to: /home/vagrant/passport/public
```

If you are using Laravel Valet and you created the passport server in your "parked" directory you should be all set -- open `passport.test` in your browser to confirm it works. Otherwise you will need to run `valet link passport` from the root of the new Laravel application.

Go ahead and open your browser and navigate to `passport.test`. You should be greeted by the default welcome page. If something went wrong review Laravel's [getting started documentation](https://laravel.com/docs/6.x/installation).

{{< figure src="/img/passport-guide-part-I-authorization-server/welcome-800.png" link="/img/passport-guide-part-I-authorization-server/welcome.png" title="The default welcome page" class="tc" target="_blank" >}}

If you don't see the "login" and "register" links in the navigation bar you may have forgotten to scaffold the authentication. Run the commands `php artisan ui:auth` and `php artisan migrate`, then reload the page.

Go ahead and set the `APP_URL` to `http://passport.test` in your `.env` file.


## Installing Passport

Now you can install Passport. Start by requiring the composer package.

```
composer require laravel/passport
```

Since Passport provides database migrations you will need to run the `php artisan migrate` command again. This will create additional tables needed to store OAuth clients and credentials.

Next you should run the `passport:install` artisan command. This will create personal access and password grant clients we will use later. Write down the client ID and secret for each client in a safe place such as a password manager.

```
php artisan passport:install
```

Now you can begin integrating Passport with the backend of the application. The first step is to add the `HasApiTokens` trait to the `User` model. This trait adds helpful methods for accessing the user's access tokens and OAuth clients.

```php
<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // existing code...
}
```

The next step is to add a call to the `Passport::routes` method in the `boot` method of the `AuthServiceProvider`. The `Passport::routes` method registers Passport's JSON API as well as the views needed for redirect flows.

```php
<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
    }
}
```

Finally you can instruct the `api` authentication guard to use Passport instead of token authentication. Within the `config/auth.php` config file you will find an `api` guard. The `driver` option should be changed from `token` to `passport`. The `hash` option is not supported at this time so it can be deleted. The updated configuration should look like this:

```php
<?php
    // ...
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
    ],
    // ...
```

## Registering the frontend

At this point Passport is setup and you can use Passport for API authentication. However it's likely you will want to allow third party developers to create OAuth 2.0 clients for their applications. Luckily Passport ships with Vue components you can use to quickly build a UI for your application. The Vue components use the Bootstrap CSS framework so they work out of the box with Laravel's default authentication templates.

You can publish the Vue components using the `vendor:publish` artisan command. This will add the components to a `passport` directory under your application's `resources/js/components` directory.

```
php artisan vendor:publish --tag=passport-components
```

Next you need to tell Vue where to load the components from. The following code should be added to your `resources/js/app.js` file below the existing call to `Vue.component`.

```js
Vue.component(
    'passport-clients',
    require('./components/passport/Clients.vue').default
);

Vue.component(
    'passport-authorized-clients',
    require('./components/passport/AuthorizedClients.vue').default
);

Vue.component(
    'passport-personal-access-tokens',
    require('./components/passport/PersonalAccessTokens.vue').default
);
```

If you haven't installed the npm dependencies yet you will need to run `npm install`. Next run `npm run dev`. This will recompile your application's JavaScript dependencies and ensure the Passport vue components are available.

Let's update the home template so the user can manage their OAuth clients and tokens once they login. Open `resources/home.blade.php` and remove the default "Dashboard" card, then add a new card for each of the Passport components. Wrap each Passport component in a row and a column, following the example of the "Dashboard" card, so your blade template looks like this:

```html
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <passport-clients></passport-clients>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 mt-3">
            <passport-authorized-clients></passport-authorized-clients>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8 mt-3">
            <passport-personal-access-tokens></passport-personal-access-tokens>
        </div>
    </div>
</div>
@endsection
```

At this point you can visit `passport.test` in your browser, register, and once you are redirected you should see the Passport components. You may have noticed we registered 3 components but only 2 are visible. The `passport-authorized-clients` component is only visible once you've authorized some clients so it won't show up right away.

{{< figure src="/img/passport-guide-part-I-authorization-server/passport-components-800.png" link="/img/passport-guide-part-I-authorization-server/passport-components.png" title="The Passport components" class="tc" target="_blank" >}}


## Conclusion

The Laravel application now serves a fully spec compliant OAuth 2.0 authorization server. Any OAuth 2.0 client will be able to complete the authorization flow and obtain an access token. In the next part of this series we will build our first client, a server-side web app, using the [Laravel Socialite](https://laravel.com/docs/7.x/socialite) package. You can [sign up to my newsletter](https://mailchi.mp/335f6a2271b0/mattallanme) to be notified when the next article is live.
