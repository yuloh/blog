---
title: "Passport Guide Part III: Client-side JavaScript clients"
description: "Building client-side JavaScript web app clients with Laravel Passport"
images: ["img/passport-guide.png"]
date: 2020-05-19
draft: false
---
## Introduction

This is Part III of a multi-part series about Laravel Passport. In [part I](./../passport-guide-auth-server) we built the authorization server. In [part II](./../passport-guide-server-side-web-app-clients) we build a server-side web app client. Today we are going to build our secound OAuth client, a client-side JavaScript web application.

In this guide we are going to build the client using [VueJS](https://vuejs.org/) and [NuxtJS](https://nuxtjs.org/), but the same principles apply to building clients with React or any other JavaScript framework that runs on the client.

You can view [the finished application on Github](https://github.com/matt-allan/passport-guide-client-javascript-web-app). You can follow along with the guide by looking at [the commits](https://github.com/matt-allan/passport-guide-client-javascript-web-app/commits/master).

## The client-side authorization code flow

Writing a secure OAuth client that runs on a user's device is tricky. Since JavaScript web apps run in the browser they face additional challenges that don't exist for server side apps.

A JavaScript web app uses the authorization code grant, just like a server-side app. But server-side apps send a `client_secret` along with the authorization code to get an access token. This ensures that even if the authorization code were to be intercepted, an attacker couldn't obtain an access token.

An app that runs on the user's device can't use a client secret. If it did anyone could inspect the code and extract the secret. Originally the OAuth spec didn't have a solution for this, but eventually the **Proof Key for Code Exchange** (**PKCE**, pronounced "pixy") extension was added.

The PKCE extension doesn't alter the authorization code flow at all. Instead it adds a couple of parameters to the authorization and access token requests.

- First the client generates a secret for each request (the `code_verifier`).
- Next the client hashes the secret and sends the hash (the `code_challenge`) along with the authorization request.
- Finally the client sends the secret along with the token request.

If the secret matches the hash sent with the authorization request the server knows the token request came from the real client.

It's all pretty complicated and challening to get right but luckily there are high quality open source libraries that handle all the hard stuff. For this guide we are going to use the [AppAuth library](https://appauth.io/) from the OpenID foundation.

## Getting started

Let's start by creating a new Vue application using NuxtJS. We're going to follow the [NuxtJS installation guide](https://nuxtjs.org/guide/installation) to quickly scaffold the application.

If you you have npx installed (npx is shipped by default since NPM 5.2.0 - If you've installed Node.JS already you should have it) run the following command:

```
$ npx create-nuxt-app js-web-app
```

Otherwise, if you prefer yarn:

```
$ yarn create nuxt-app js-web-app
```

The installer will ask a lot of questions. For this tutorial we are going to use 'Bootstrap Vue' as the UI framework and 'None' as the server framework. Go ahead and add the 'Axios' and 'DotEnv' modules for making HTTP requests and loading configuration respectively. Add any linting or testing tools you would like to use and choose 'Single Page App' as the rendering mode.

Once you've answered all the prompts follow the directions to `cd` into the nuxt app's directory and start the dev server (either `npm run dev` or `yarn run dev` depending on your package manager).

## Adding Pages

The Vue component for the page you see is at `./pages/index.vue`. We can delete the headings, links, etc. and simplify this considerably.

```js
<template>
  <div class="content">
    <Logo />
  </div>
</template>

<script>
import Logo from '~/components/Logo.vue'

export default {
  components: {
    Logo
  }
}
</script>

<style>
.content {
  text-align: center;
}
</style>
```

The index page is extending the layout at `layouts/default.vue`. We don't need any of the css anymore, so delete the `<style>` tags from the default layout. The default layout should now look like this:

```html
<template>
  <div>
    <nuxt />
  </div>
</template>
```

Next we need to add a navigation menu with login and logout links. Instead of putting all of that in the default layout, let's make a separate `app` layout at `layouts/app.vue`. To build the nav we can use the nav component from the Bootstrap Vue framework.

```html
<template>
  <div id="app">
    <b-navbar toggleable="md" type="light" class="bg-white shadow-sm">
      <b-navbar-brand to="/">
        JS Web App
      </b-navbar-brand>
      <b-navbar-toggle target="#nav-collapse"></b-navbar-toggle>
      <b-collapse id="nav-collapse" is-nav>
        <!-- left side of navbar -->
        <b-navbar-nav class="mr-auto"> </b-navbar-nav>

        <!-- right side of navbar -->
        <b-navbar-nav class="ml-auto">
          <b-nav-item href="#">
            <b-nav-item to="login">Login</b-nav-item>
          </b-nav-item>
        </b-navbar-nav>
      </b-collapse>
    </b-navbar>

    <main class="py-4">
      <nuxt />
    </main>
  </div>
</template>
```

To actually use the app layout you need to update our index component. Add `layout: 'app'` to the default export.

```js
import Logo from '~/components/Logo.vue'

export default {
  layout: 'app',
  components: {
    Logo
  }
}
```

Switch to your browser and the app should look like this:

{{< figure src="/img/passport-guide-part-III-client-side-javascript-clients/nuxt-index-800.png" link="/img/passport-guide-part-III-client-side-javascript-clients/nuxt-index.png" title="The NuxtJS app index page" class="tc" target="_blank" >}}


## Scaffolding authentication

### Login

To add the login page, create a new Vue component at `pages/login.vue`. The login page doesn't actually need to render anything, so the component only needs a `<script>` tag.

The login request should start as soon as the page loads so we will use Vue's `mounted` hook to call a `login` method. Eventually this will redirect to the server, but let's stub this out for now. Getting everything working before adding the complexity of OAuth will make debugging easier if something doesn't work.

To simulate logging in store a fake access token on the client. In NuxtJS state is typically stored in a VueX Store. The store can be accessed as `this.$store` in any Vue component.

Go ahead and add a new JavaScript file for the store at `store/index.js`. The only state needed right now is `auth`, which will be a JavaScript object with the access token, refresh token, etc. The state will be changed with a `setAuth` mutation.

```js
export const state = () => {
  return {
    auth: null
  }
}
export const mutations = {
  setAuth(state, auth) {
    state.auth = auth
  }
}

export const actions = {
}
```

Once the store is created the login component can use the store to set an access token. After setting the login token the component will redirect to an authenticated 'dashboard' page.

```js
<script>
export default {
  mounted() {
    this.login()
  },
  methods: {
    login() {
        this.$store.commit('setAuth', {
          accessToken: 'exampleAccessTokenForTesting'
        })
        this.$router.push('/dashboard')
    }
  }
}
</script>
```

### Authenticated Pages

Add a basic dashboard the user can be redirected to after login. This will extend the 'app' layout and use a Bootstrap Vue card component.

```js
<template>
  <b-container>
    <b-row align-v="center">
      <b-col md="8">
        <b-card header="Dashboard">
          You are logged in!
        </b-card>
      </b-col>
    </b-row>
  </b-container>
</template>

<script>
export default {
  layout: 'app',
}
</script>
```

However right now anyone can load the dashboard, even if they haven't logged in. Since a lot of pages will probably want to require authentication this is a good use for middleware. To create the middleware, add a file in the `middleware` directory named `auth.js`. The middleware will check the store state. If the auth object is not set the user will be redirected back to the login page.

```js
export default function({ store, redirect }) {
  // If the user is not authenticated redirect to the login page
  if (!store.state.auth) {
    return redirect('/login')
  }
}
```

To prevent authenticated users from accessing certain pages you can write a `guest` middleware.

```js
export default function({ store, redirect }) {
  // If the user is authenticated redirect to dashboard
  if (store.state.auth) {
    return redirect('/dashboard')
  }
}
```

Now you just need to tell your page component to use the middleware:

```js
<script>
export default {
  layout: 'app',
  middleware; 'auth',
}
</script>
```
### Logout

The navigation menu still has a login link, even when you are logged in. Let's fix that so it changes to a logout link if the user is logged in. Open the app layout and update the right side of the nav to use a `v-else` directive. The store is available as the `$store` property so auth can be checked via `$store.state.auth`.

```html
<!-- right side of navbar -->
<b-navbar-nav class="ml-auto">
  <div v-if="!$store.state.auth">
    <b-nav-item href="#">
      <b-nav-item to="login">Login</b-nav-item>
    </b-nav-item>
  </div>
  <div v-else>
    <b-nav-item href="#">
      <b-nav-item to="logout">Logout</b-nav-item>
    </b-nav-item>
  </div>
</b-navbar-nav>
```

{{< figure src="/img/passport-guide-part-III-client-side-javascript-clients/nuxt-dashboard-800.png" link="/img/passport-guide-part-III-client-side-javascript-clients/nuxt-dashboard.png" title="The NuxtJS app dashboard page" class="tc" target="_blank" >}}

Next you need a logout page. This will basically be the inverse of the login page -- remove the auth object from the store and redirect the user to the homepage.

```js
<script>
export default {
  middleware: 'auth',
  mounted() {
    this.logout()
  },
  methods: {
    logout() {
      this.$store.commit('setAuth', null)
      this.$router.push('/')
    }
  },
  render() {
    return null
  }
}
</script>
```

## Adding OAuth

Once everything is setup you can add OAuth. Go ahead and register a client with the Passport server. For the redirect URL you can use `http://localhost:3000/login/callback`. You should not check the confidential checkbox.

{{< figure src="/img/passport-guide-part-III-client-side-javascript-clients/register-js-client-800.png" link="/img/passport-guide-part-III-client-side-javascript-clients/register-js-client.png" title="Registering the JavaScript web app client" class="tc" target="_blank" >}}


Most of the heavy lifting will be handled by AppAuth JS, so add the `@openid/appauth` package.

```
npm install --save @openid/appauth
```

Or if you prefer yarn:

```
yarn add @openid/appauth
```

The AppAuth library is very flexible which unfortunately means you need to write a **lot** of code to use it. To keep it all in one place add a new `auth` folder and start a new `index.js` JavaScript file. Instead of exporting `AppAuth` with all the boilerplate it requires you can export a much simpler `Auth` class, exposing only the methods you need.

The following code can be used to make token requests using AppAuth:

```js
import { AuthorizationServiceConfiguration } from '@openid/appauth/built/authorization_service_configuration'
import { AuthorizationRequest } from '@openid/appauth/built/authorization_request'
import { RedirectRequestHandler } from '@openid/appauth/built/redirect_based_handler'
import { AuthorizationNotifier } from '@openid/appauth/built/authorization_request_handler'
import { BasicQueryStringUtils } from '@openid/appauth/built/query_string_utils'
import { BaseTokenRequestHandler } from '@openid/appauth/built/token_request_handler'
import {
  GRANT_TYPE_AUTHORIZATION_CODE,
  TokenRequest
} from '@openid/appauth/built/token_request'
import { FetchRequestor } from '@openid/appauth/built/xhr'

class NoHashQueryStringUtils extends BasicQueryStringUtils {
  parse(input, useHash) {
    // never use hash
    return super.parse(input)
  }
}

export class Auth {
  constructor() {
    this.configuration = new AuthorizationServiceConfiguration({
      authorization_endpoint: `${process.env.PASSPORT_URL}/oauth/authorize`,
      token_endpoint: `${process.env.PASSPORT_URL}/oauth/token`
    })

    this.notifier = new AuthorizationNotifier()

    this.authorizationHandler = new RedirectRequestHandler(
      undefined,
      new NoHashQueryStringUtils()
    )

    this.authorizationHandler.setAuthorizationNotifier(this.notifier)

    this.tokenHandler = new BaseTokenRequestHandler(new FetchRequestor())
  }

  makeAuthorizationRequest() {
    const request = new AuthorizationRequest({
      client_id: process.env.PASSPORT_CLIENT_ID,
      redirect_uri: `${process.env.BASE_URL}/login/callback`,
      scope: '',
      response_type: AuthorizationRequest.RESPONSE_TYPE_CODE
    })

    this.authorizationHandler.performAuthorizationRequest(
      this.configuration,
      request
    )
  }

  completeAuthorizationRequest() {
    return new Promise((resolve, reject) => {
      this.notifier.setAuthorizationListener((request, response, error) => {
        if (error) {
          reject(error)
        }

        if (response) {
          const code = response.code

          const extras = {}
          if (request.internal && request.internal.code_verifier) {
            extras.code_verifier = request.internal.code_verifier
          }
          this.makeAuthCodeTokenRequest(code, extras).then((response) => {
            resolve(response)
          })
        }
      })

      this.authorizationHandler.completeAuthorizationRequestIfPossible()
    })
  }

  makeAuthCodeTokenRequest(code, extras) {
    const tokenRequest = new TokenRequest({
      client_id: process.env.PASSPORT_CLIENT_ID,
      redirect_uri: `${process.env.BASE_URL}/login/callback`,
      grant_type: GRANT_TYPE_AUTHORIZATION_CODE,
      code,
      refresh_token: undefined,
      extras
    })

    return this.tokenHandler.performTokenRequest(
      this.configuration,
      tokenRequest
    )
  }
}
```

There are a few important things to note:

- By default AppAuth expects the `code` and `state` parameters to be returned in the URL hash instead of as query string parameters. When this happens the authorization request never completes. To override this behavior you have to pass your own `NoHashQueryStringUtils` class.
- By default AppAuth will attempt to perform XHR requests using JQuery's `$.ajax` method. If you do not use JQuery you can override this to use fetch instead.
- To use the PKCE extension you must retrieve the code_verifier from the request and pass it to the `makeAuthCodeTokenRequest` method.
- The authorization request response is returned using a callback. To make the consuming code easier to read you can wrap this in a promise.

The Auth class is reading configuration from `process.env`, so you need to add the corresponding entries to your `.env` file. The `PASSPORT_URL` should be the URL of your Passport server and the `PASSPORT_CLIENT_ID` should be the client ID assigned when you registered the JS web app client.

```sh
PASSPORT_URL=http://passport.test
PASSPORT_CLIENT_ID=2
```

## Requesting authorization

To perform the authorization request, open `pages/login.vue` and replace the stub auth method with a call to the new `Auth` class' `makeAuthorizationRequest` method.

```js
<script>
import { Auth } from '../auth'

export default {
  middleware: 'guest',
  mounted() {
    new Auth().makeAuthorizationRequest()
  },
  render() {
    return null
  }
}
</script>
```

If you test this now you will receive an error because the redirect route, `login/callback`, doesn't exist yet. Let's create that now. To create the `login/callback` page you need to:

- Create a new `login` directory within the `pages` directory.
- Move `pages/login.vue` to `pages/login/index.vue`. 
- Add a new Vue component, `pages/login/callback.vue`.

Since the login component was moved one directory deeper the relative import will need to be updated.

```js
import { Auth } from '../../auth'
```

## Requesting a token

The callback page will receive the authorization code and request a token. This can be done with the `completeAuthorizationRequest` method.

Because we have to wait for an HTTP request the `completeAuthorizationRequest` returns a promise that resolves to the response. To make the code a little more readable you can make the `mounted` method `async` and `await` the promise.


```js
<script>
import { Auth } from '../../auth'

export default {
  middleware: 'guest',
  async mounted() {
    const response = await new Auth().completeAuthorizationRequest()

    this.$store.commit('setAuth', response)
    this.$router.push('/dashboard')
  },
  render() {
    return null
  }
}
</script>
```

## API Requests

The Nuxt Axios module has a helpful `setToken` method that can be used to easily set a global authentication header. You can set the token on the callback page before redirecting to the dashboard. 

```js
this.$axios.setToken(response.accessToken, 'Bearer')
```

Now you can easily make API requests using the user's access token.

```js
const user = await this.$axios.$get(`${process.env.PASSPORT_URL}/api/user`)
```

## Obtaining fresh access tokens

Eventually the access token will expire and the API will return an error. On the server side we used refresh tokens, but using refresh tokens on the client is risky.

If you don't use refresh tokens with Passport you will need to start a new authorization request, which means the user has to be redirected to the authorization server. If the user is in the middle of filling out a form when this happens, redirecting them to another domain and losing all client-side state is not ideal. You can work around this issue with a technique called 'silent authentication', but silent authentication requires server-side support and Passport doesn't support it.

So for now you will either need to workaround the limitations or use refresh tokens. If you do choose to use refresh tokens make sure to read the [refresh token section](https://tools.ietf.org/html/draft-ietf-oauth-browser-based-apps-06#section-8) of the OAuth 2.0 for Browser-Based Apps specification.

## Storing access tokens

You may have noticed we aren't storing the access token anywhere. If the user refreshes the page, an access token won't be found, so they will be redirected to the login page to obtain a new access token before being redirected back to the dashboard.

This is the safest way to use access tokens. You really don't want to store the access token in local storage or in a cookie accessible to JavaScript. Keeping the token in memory is a little inconvenient but a lot safer.

## Wrapping up

If you've made it this far you should now understand how to write a secure Passport client for the browser. If you have any questions feel free to [reach out to me on Twitter](https://twitter.com/__mattallan). If you want to know when I publish new content like this you can [sign up for my newsletter](https://mailchi.mp/335f6a2271b0/mattallanme).