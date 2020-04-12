---
title: "Passport Guide Part II: Server-side web app clients"
date: 2020-04-12
draft: true
---
## Introduction

This is Part II of a multi-part series about Laravel Passport. In [part I](./../passport-guide-auth-server) we built the authorization server. Today we are going to buil our first OAuth client, a server-side web app.

Server-side apps are the simplest OAuth clients to build because they can keep secrets. A server-side app is considered a 'confidential' client in OAuth terms. Public clients like JavaScript web apps and mobile apps use the same basic flow as server-side apps, but then have to go through a bunch of extra steps because they can't keep secrets. Even if you only plan on building public clients, it's a good idea to build a server-side app first so you can learn the basics.

If at any point you get stuck check the [example application on Github](https://github.com/matt-allan/passport-guide-client-server-side-web-app). You can [view the commits](https://github.com/matt-allan/passport-guide-client-server-side-web-app/commits/master) to see what changes were made in each section of the guide.


## Registering the client

Let's get started by creating a new client for our Passport Authorization server.

After logging in to the Passport server click on 'Create New Client'. You can use anything you'd like for the client name, but choose something descriptive like 'Server-side web app'. The 'Redirect URL' is the URL the user will be redirected to after they approve the app's authorization request. We're going to set up our app at `server-app.test` so go ahead and use `http://server-app.test/login/callback` as the redirect URL. Leave the 'Confidential' checkbox checked.

{{< figure src="/img/passport-guide-part-II-server-side-web-app-clients/register-server-client-800.png" link="/img/passport-guide-part-II-server-side-web-app-clients/register-server-client.png" title="Registering the server-side web app client" class="tc" target="_blank" >}}

After you click 'create' you will see a 'Client ID' and 'secret'. Keep this tab open or write them down because you need them to configure your client later.

## Bootstrapping

Now you can build the app. Begin by scaffolding a new Laravel application. Use the `--auth` flag again to scaffold the default authentication -- we are going to rip most of it out but the blade templates will save time.

```
laravel new --auth server-app
```

Follow the same steps you used to setup the authorization server: Add the `server-app.test` domain to Homestead or Valet and update the `.env` file with database credentials and the `APP_URL`. The app will have it's own `users` table so it needs a database too, but don't run `php artisan migrate` yet.

Open `server-app.test` in your browser and you should see the welcome page. If you click on the 'login' and 'register' links you will see the default forms. Since this app is going to delegate auth to the Passport server you don't need a register link and the login link should redirect to the Passport server for login.

Go ahead and delete every controller in `app/Http/Controllers/Auth` except for the login controller. You can also delete the blade templates in `resources/views/auth`. The `CreatePasswordResetsTable` migration (`migrations/2014_10_12_100000_create_password_resets_table.php`) should be deleted too.

Navigate to the `LoginController` in your editor (you can find it at `app/Http/Controllers/Auth/LoginController.php`). Right now it's almost empty. That's because most of the logic is in the `AuthenticatesUsers` trait. The default login code doesn't work for OAuth login so go ahead and delete the `use AuthenticatesUsers` line.

Since you aren't using the trait anymore you need to write your own `login` and `logout` methods. The `login` method will kick off the OAuth flow -- it needs to redirect to the Passport server's `/oauth/authorize` route and include the necessary OAuth parameters in the query string. The final redirect URI will look something like this:

```
http://passport.test/oauth/authorize?client_id=3&redirect_uri=http://server-app.test/login/callback&response_type=code&scope=&state=xxxxxxxxxx
```

The query parameters are defined by the OAuth 2.0 specification. The OAuth client needs to use these exact parameters or the request will be rejected. You don't need to remember this but it's helpful to understand what they mean:

- client_id: This is the unique ID for the client making the request. With Passport this is the client model's primary key. You can get this from the 'OAuth Clients' Vue component we setup in the previous chapter.
- redirect_uri: This is the redirect URI you registered for the client earlier. It's the URI the user's browser will be redirected to after they approve or deny the authorization request. The redirect_uri is optional if the client only registered a single redirect URI but most clients will include it anyway.
- response_type: All you really need to know about this is it should always be included and set to `code`. The OAuth 2.0 specification used to support an alternative flow called the Implicit grant and you would make an Implicit grant request by setting `response_type` to `token`. The Implicit grant is insecure and no longer recommended so you shouldn't ever need to change this parameter.
- scope: This parameter is used to let the authorization server know what permissions you are requesting. We will learn more about this in a later post.
- state: The state parameter is used to protect against cross site request forgery (CSRF) attacks. The client generates a cryptographically secure random string before making the request. When the user's browser is redirected back to the client the state token returned from the authorization server is compared to the state token we generated earlier. If they don't match we know the redirect didn't come from the same authorization server we originally redirected to.

You could write all of this code yourself but it's tedious, and you need to be really careful to avoid security vulnerabilites. Luckily Laravel created an official package for authenticating with OAuth providers called [Socialite](https://laravel.com/docs/6.x/socialite). Socialite doesn't ship with an official driver for Passport servers it's available as a third party package. To pull in Socialite and the Passport driver install the `matt-allan/passport-socialite` package with Composer.

```
composer require matt-allan/passport-socialite
```

Next you need to add a Passport section to the app's `services` config file and add the credentials to the app's `.env` file. Add the following snippet to `config/services.php`:

```php
<?php

'passport' => [
    'client_id' => env('PASSPORT_CLIENT_ID'),
    'client_secret' => env('PASSPORT_CLIENT_SECRET'),
    'url' => env('PASSPORT_URL'),
    'redirect' => env('PASSPORT_REDIRECT'),
],
```

Then add the following snippet to `.env`:

```
PASSPORT_CLIENT_ID=1
PASSPORT_CLIENT_SECRET=7h233eobsGr1V9HxrejyBwjTzVZdqWnGkxyew1D1
PASSPORT_URL=http://passport.test
PASSPORT_REDIRECT=${APP_URL}/login/callback
```

The `PASSPORT_CLIENT_ID` and `PASSPORT_CLIENT_SECRET` should match the values you were given when you created the client.

## Login

Now that Socialite is setup you can finish the `LoginController`. First add the `login` method. This will initiate the OAuth flow by calling the `redirect` method on the Socialite driver.


```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login()
    {
        return Socialite::driver('passport')->redirect();
    }
}
```

After the user is redirected to the Passport server and approves the request they will be redirected back to the application. The authorization server will append a query string to the redirect URL like this:

```
http://server-app.test/login/callback?code=xxxxxxxxxx&state=xxxxxxxxxx
```

The application then needs to verify the `state` parameter. If the state parameter matches the token stored earlier a second request is made to trade the authorization code for an access token. With Socialite this is done automatically when you call the `user` method. Let's add another controller method for the login callback that completes the OAuth flow. Eventually this method will redirect to the user's intended destination but for now return a hello world response to confirm everything works.

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    // existing code...

    public function loginCallback()
    {
        $user = Socialite::driver('passport')->user();

        return "Hello {$user->name}!";
    }
}
```

This is everything you need to login. Since the controller methods have changed the default auth routes no longer work. Open `routes/web.php` and register the new routes. Make sure to remove the call to `Auth::routes` first.

```php
<?php

Route::get('/login', 'Auth\LoginController@login')->name('login');

Route::get('/login/callback', 'Auth\LoginController@loginCallback')->name('login.callback');
```

Navigate to `http://server-app.test/` and click 'login'. If you aren't already logged in to `passport.test` you will be asked to login. Next you will be asked to grant permission to the app to access your account. Click 'Authorize' and you should be greeted by your new app!


{{< figure src="/img/passport-guide-part-II-server-side-web-app-clients/server-auth-prompt-800.png" link="/img/passport-guide-part-II-server-side-web-app-clients/server-auth-prompt.png" title="The server-side web app authorization prompt" class="tc" target="_blank" >}}

{{< warning >}}
If you are using Valet and receiving a 404 error you may need to tweak your computer's DNS settings. Sometimes Valet has issues making CURL requests from one Valet app to another. Adding `127.0.0.1` to the DNS servers list in Network Preferences resolves the issue.
{{</ warning >}}

## Sessions

At this point the app the OAuth flow is implemented but the user is not actually logged in to the client app. If you reload the page you will get an error. To actually login we can follow the same steps we would follow for any other third party login.

Open the `CreateUsersTable` migration at `database/migrations/2014_10_12_000000_create_users_table.php` and remove the `password`, and `remember_token` columns. Next add a unique big integer column named `provider_uid`. The `provider_uid` column will store the user ID returned from the Passport server. Your updated `up` method should look like this:

```php
<?php

Schema::create('users', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->bigInteger('provider_uid')->unique();
    $table->timestamps();
});
```

Go ahead and update the `UserFactory` (`database/factories/UserFactory.php`), removing the `password` and `remember_token` attributes and adding `provider_uid`. The updated `UserFactory` should look like this:

```php
<?php

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => now(),
        'provider_uid' => $faker->unique()->randomNumber(),
    ];
});
```

Finally update the `User` model. Remove any references to `password` or `remember_token`, then add `provider_uid` to `fillable` and `hidden`. Since the users won't use remember me tokens (that's handled by the Passport server) set the `$rememberMeTokenName` property to `null`.

```php
<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'provider_uid',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $rememberTokenName = null;
}

```

Now you can migrate the database: `php artisan migrate`.

Once the database is migrated you can add the login and logout functionality. When the callback is received and the user's info is retrieved with Socialite's `user` method the `Socialite` user can be used to look up the app's own User model. The socialite `User` has an `id` property that corresponds to the user's primary key in the Passport database. This `id` will be stored as the `provider_uid` on the User model. Using the `provider_uid` rather than the user's email ensures that the lookup will still work when a user changes their email.

Open the `LoginController` and rename the `$user` variable to `$socialiteUser`. On the next line user the User model's `firstOrNew` method to find or create the user via the `provider_uid`. Because `provider_uid` is not mass assignable we will use the `unguarded` method.

To create our user we will need to retrieve the user's name and email from the Socialite user. Any additional attributes such as `email_verified_at` can be retrieved using the `getRaw` method. Finally we will call the `save` method to ensure the user is either created or updated.

Using the `tap` helper to keep the code succinct, the updated method will look like this:

```php
<?php

$socialiteUser = Socialite::driver('passport')->user();

$user = User::unguarded(function () use ($socialiteUser) {
    return tap(User::firstOrNew(['provider_uid' => $socialiteUser->id])
        ->fill([
            'name' => $socialiteUser->name,
            'email' => $socialiteUser->email,
            'email_verified_at' => Arr::get($socialiteUser->getRaw(), 'email_verified_at'),
        ]))->save();
});
```

{{< warning >}}
It's absolutely essential your Passport server uses HTTPS in production. Otherwise an attacker could MITM the connection between your client and server, returning a different user ID, and login as someone else.
{{</ warning >}}

Now that you have a User you can login. This is easily accomplished by calling the `login` method on the `auth` helper.

```php
<?php

auth()->login($user);
```

Anytime you log a user in you need to regenerate the session, regardless of the login method. This is really important to prevent session fixation attacks.

```php
<?php

session()->regenerate();
```

Logging the user in will set a cookie which allows the user to access pages requiring authentication in the client app. However this will not allow them to access APIs provided by the Passport server. To do that we will need to remember the access token returned from Socialite. The access token is short lived so it's a good idea to store it in the session rather than in the database. You will also need to store the token expiration and the refresh token so that you can refresh the access token when it expires.

```php
<?php

session([
    'token' => $socialiteUser->token,
    'refresh_token' => $socialiteUser->refreshToken,
    'expires_at' => now()->addSeconds($socialiteUser->expiresIn),
]);
```

{{< info >}}
If you are using Laravel Valet and the cookie session driver attempting to store a large amount of data in the session may cause a 502 Bad Gateway response from the server. This occurs because the buffer NGINX uses to buffer the response from PHP is too small. The easiest solution is to use an alternative session driver such as the `file` driver by changing `SESSION_DRIVER` in your `.env` file.
{{</ info >}}

Finally the user can be redirected to their intended destination using the `redirect()->intended` helper, falling back to the 'home' route. The completed method should look like this:

```php
<?php

public function loginCallback()
{
    $socialiteUser = Socialite::driver('passport')->user();

    $user = User::unguarded(function () use ($socialiteUser) {
        return tap(User::firstOrNew(['provider_uid' => $socialiteUser->id])
            ->fill([
                'name' => $socialiteUser->name,
                'email' => $socialiteUser->email,
                'email_verified_at' => Arr::get($socialiteUser->getRaw(), 'email_verified_at'),
            ]))->save();
    });

    auth()->login($user);

    session()->regenerate();

    session([
        'token' => $socialiteUser->token,
        'refresh_token' => $socialiteUser->refreshToken,
        'expires_at' => now()->addSeconds($socialiteUser->expiresIn),
    ]);

    return redirect()->intended($this->redirectTo);
}
```

## Logout

The logout method is simple. To log the user out you need to call `logout` on the authentication guard, invalidate the session, and redirect the user back to the landing page.

```php
<?php

public function logout()
{
    auth()->logout();

    session()->invalidate();

    return redirect('/');
}
```

The logout route will also need to be registered. This can be added directly below the login routes in `routes/web.php`.

```php
<?php

Route::post('/logout', 'Auth\LoginController@logout')->name('logout');
```

Open `server-app.test` in your browser and login. You should see your user's name in the dashboard.

{{< figure src="/img/passport-guide-part-II-server-side-web-app-clients/server-logged-in-800.png" link="/img/passport-guide-part-II-server-side-web-app-clients/server-logged-in.png" title="The logged in dashboard" class="tc" target="_blank" >}}

## Making API Requests

A real Passport server will offer API endpoints that aren't handled by Socialite. To make a request you will need to retrieve the access token from the session and pass it in the request's Authorization header. The Passport server returns a bearer token so the token should be prefixed with the word 'Bearer', followed by a single space:

```php
<?php

$client = new GuzzleHttp\Client();

$response = $client->request('GET', '/api/posts', [
    'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.session('token')
    ],
]);
```

## Handling Token Expiration

Eventually the access token will expire. When this happens you may use the refresh token to obtain a new access token. The Passport Socialite driver offers a `refresh` method for this purpose. If refreshing the token fails you should log the user out so they can login again and obtain fresh tokens. For example:

```php
<?php

if (now()->greaterThanOrEqualTo(session('expires_at'))) {
    try {
        Socialite::driver('passport')->refresh(session('token'));
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        info('refreshing the access token failed', ['exception' => $e]);

        auth()->logout();
    }

    session([
        'token' => $socialiteUser->token,
        'refresh_token' => $socialiteUser->refreshToken,
        'expires_at' => now()->addSeconds($socialiteUser->expiresIn),
    ]);
}

// make the HTTP request...
```

You would typically place this code in the API client class for the Passport server's API. Keep in mind it's also possible for the access token to be revoked before it expires, so you will need to watch for 401 unauthenticated errors from the Passport server and either attempt to refresh the token or log the user out.

## Conclusion

If you've made it this far you now have a working Passport server and client using Laravel. Now that you understand how to build an OAuth 2.0 client with a server side component you can move on to building JavaScript web apps. [Sign up for my newsletter](https://mailchi.mp/335f6a2271b0/mattallanme) and I will let you know when the next article is live.