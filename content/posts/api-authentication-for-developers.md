---
title: API Authentication For Developers
description: A simple guide to choosing an authentication method for APIs
images: ["/img/api-auth/cover.png"]
tags: ["API", "OAuth", "SPA", "authentication"]
date: 2019-08-01T14:25:47Z
draft: true
---

## Introduction

You are building a single page application or mobile app, it has an API, and you are trying to figure out how authentication is supposed to work. You've heard you can't use cookies because REST APIs are supposed to be stateless. Maybe you need JSON Web Tokens, or maybe you need OAuth which is apparently something different but it also uses JSON Web Tokens? It doesn't help that 99% of the articles you can find are written by companies trying to sell you authentication as a service.

Confused? [You're not alone](https://twitter.com/adamwathan/status/1156915359410208768?s=20). I spent way too much time reading all of the IETF standards and I've built a few APIs so I feel like I finally have a pretty good grasp of this stuff. Hopefully I can break this down so it makes sense to other developers.

There are a **lot** of authentication methods being used for APIs. In the old days of server rendered pages everyone used the same thing, but the proliferation of REST APIs changed that. Nowadays it's common to encounter regular sessions, HTTP Basic authentication, API keys, OAuth 1.0, OAuth 2.0 (which actually includes 6 different grant types), and JSON Web Tokens.

Before understanding the different authentication methods, it's important to understand the difference between **first party** and **third party** clients.

## First vs. third party clients

When talking about APIs the first party is whoever owns the API. If your company built an API and now it's building a SPA that uses that API, the SPA is a first party client. If that SPA uses "Login with Google" it's a third party client of Google's API.

If Instagram' asks for your Instagram password it's perfectly normal. If a third party app asks for your Instagram password you probably don't want to give it to them because [who knows what they are going to do with it.](https://mashable.com/2013/11/12/instagram-instlike-scam/).

This is the problem that API keys and OAuth are meant to solve. You don't want to give a third party app your password. You just want to grant them limited access to your account to do what they need to do.

A lot of articles written about "API Authentication" are really talking about "Authentication for APIs meant for third parties".

If your API is going to be used by third parties you have a delegation problem, so a delegation protocol like OAuth makes sense. If your API is only being used internally you probably don't need a delegation protocol. Traditional sessions might be fine.

## Basic authentication

[HTTP is stateless](https://developer.mozilla.org/en-US/docs/Web/HTTP/Overview#HTTP_is_stateless_but_not_sessionless). If I make a request to `POST /login` with my username and password, then make a request to `GET /me`, the server won't remember who I am.

A simple solution is to send the credentials with every single request. You can do this using the [`Authorization` header](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Authorization). Browsers support this out of the box. If you send the [`WWW-Authenticate`](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/WWW-Authenticate) header the browser will ask for a username and password. After you enter your credentials the browser remembers them and sends them with each request. A basic example using PHP looks like this[^1]:

![a basic auth example in PHP](/img/api-auth/php-basic-auth.png)

There are a couple of different [schemes](https://developer.mozilla.org/en-US/docs/Web/HTTP/Authentication#Authentication_schemes). The most common scheme is called "Basic". With the "Basic" authentication scheme you base64 encode the credentials. Base64 is reversible so from a security perspective it's the same as sending the credentials in plain text.

## Sessions and cookies

Sending the credentials with every single request isn't great. If you send N requests the attacker has N chances to steal the credentials.

It would be better if you could send the credentials once, then get a new token that you use for subsequent requests. The server can store a map of tokens -> users, and the token can be revoked when the user logs out. If someone steals the token it's not as big of a deal because the token is only good for that one site (unlike a password which might be reused) and the token stops working when it expires or the user logs out. Since the token grants access to the user's data it needs to be sufficiently random that it's unreleastic to guess.

That's how a session works.

Once you are storing the session on the server you stuff other junk in there too. For traditional server rendered web pages this is handy, since you can store things like what tab is currently active or an error message that needs to be displayed after aredirect. An API should generally be stateless, so an API shouldn't need to store anything other than the user identifier.

When websites first started doing this the only way you could store things on the client was in a [cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies). A cookie is sent just like any other HTTP header, except the browser stores it and sends it automatically with other requests to the same domain.

**Session != Cookie!** You don't have to use cookies to use sessions. You could send the session ID in a custom header if you want and store it in local storage. However you probably _should_ use a cookie because:

- There are lots of [handy flags](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies#Creating_cookies) to limit access to cookies and expire them automatically.
- Cookies are used for sessions in every popular web framework.
- The client doesn't need to do anything to use them. They just work automatically in most browsers and HTTP clients.

## JSON Web Tokens

JSON Web Tokens are a cool solution to problems that most developers don't have. a JWT is made out of two JSON objects (the header and the payload) that look like this:

```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```

```json
{
  "sub": "1234567890",
  "name": "John Doe",
  "iat": 1516239022
}
```

The [specification](https://tools.ietf.org/html/rfc7519) lays out what all of those cryptic keys mean. Next you base64 URL encode the header and the payload, concatenate them with `.`, sign it using the algorithm specified in the header, then finally concatenate the signature onto the end so it looks something like this:

```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.y3kjst36zujMF4HssVk3Uqxf_3bzumNAvOB9N0_uRV4
```

Since the token can't be signed without the secret key, if the signature is valid you know it hasn't been tampered with. This allows you to put the user ID and the expiration time in the token and skip storing sessions on the server entirely.

The JWT can be stored in a cookie or sent in the `Authorization` header.

Imagine you're a giant company like Google. Your email app needs to know who the current user is, but to do that it needs to send the session ID to the accounts server for verification. Maybe it gets back a username and profile photo URL. This needs to happen for every request.

A JWT is a neat solution because it allows you to skip that request. You give the mail server a copy of the public key and it can verify the JWT itself. The JWT contains all of the info it needs.

Most companies don't get to this scale. You can go really far with sticky sessions and a Redis server.

Since you verify the JWT using a signature you can't easily revoke a compromised token. You either need to store a whitelist, in which case you've just reinvented sessions, or you need to store a blacklist, which still has the same problem as a blacklist, it just takes up less space. For this reason it's pretty common to issue JWTs with a really short expiration time and require another request to the authorization server every so often to get a new token.

If you still think you need JWTs read ["Stop using JWT for sessions"](http://cryto.net/~joepie91/blog/2016/06/13/stop-using-jwt-for-sessions/).

## API Keys

API keys are really convenient for developers and really suck for users. The way this works is once the user logs in there is a page on the dashboard where they can generate a new API key. The page for mailchimp looks like this[^2]:

![requesting a mailchimp API key](/img/api-auth/mailchimp-api-key.png)

Note that I said "once the user logs in". The user needs to login using a first party method (typically a username/password flow using cookies & sessions) before they generate the API key that's going to be used by a third party.

The API key should be random and long enough that guessing it is impractical, i.e. 20 bytes from `/dev/urandom`.

Ideally the user will create a new key for each app they use, but since it's up to the user they won't always do that. Sometimes you can grant specific permissions to each key.

The user then manually pastes it into the third party app they want to grant access to the API. Adding your API key to the Mailchimp for Wordpress plugin looks like this:

![adding a mailchimp API key to a third party app](/img/api-auth/mailchimp-wp.png)

The third party app then sends the API along with the request. Most implementations accept the key as the HTTP basic auth username, a [bearer token](https://tools.ietf.org/html/rfc6750) in the `Authorization` header, or sometimes even as an `api_key` query parameter (don't do this).

Since the user has to manually setup the key you can't really use a short expiration time or generate a new key every time they login like you can with sessions or OAuth.

If you don't automatically revoke API keys when the user changes their password you should probably at least prompt them to see if they want to revoke them; most users won't realize that the API keys need to be rolled.

It's pretty common to store the API keys in plaintext in the database. Obviously Mailchimp is storing the keys in plaintext since I can still see them after logging out and back in. It's better to only show the API key to the user once immediately after it's generated, then hash the tokens with SHA256[^3] before storing them. The third party is still going to be storing the token in plain text though and since they can't easily request a new one it's most likely being stored in their database.

## OAuth 2.0

If you try to come up with a way to automate the process of the user generating an API key and giving it to the third party application you will come up with something that looks a lot like OAuth. The current version of OAuth is OAuth 2.0. OAuth 2.0 has a lot of different 'grants' but the most popular one (the authorization code grant) looks like this:

First the developer of the third app creates a new OAuth client. They get an ID (the `client_id`) and a secret (`client_secret`). If it's a mobile app or runs in the browser it can't keep a secret so it doesn't get one.

![Creating a Github OAuth client](/img/api-auth/create-github-oauth-client.png)

When the user goes to use the third party app they get redirected to OAuth server. If the service is also using the OAuth server for login it might be after clicking a 'Login with...' button. Otherwise it might be a button to 'link your ...' account. The OAuth server asks if you want to grant the app permission:

![Granting Github permission](/img/api-auth/github-authorize.png)

After clicking 'Authorize' the user is redirected back to the app with an authorization code.

The client then uses it's client credentials and the authorization code it received to get an access token and a refresh token. The access token lets the third party app access data on the user's behalf. The refresh token lets them get a new access token when the token expires.

In the screenshot above I was already logged in to Github but what happens if I'm not? Github redirects me to the login page, then back to the authorize page. The OAuth spec doesn't care how login actually works on the authorization server. Typically the authorization server will use a username & password login page along with traditional sessions and cookies. OAuth is not a replacement for first party authentication -- it's a protocol for delegating authorization.

## XSS, CSRF, cookies, and local storage

Web applications have to deal with [cross site request forgery](https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)) and [cross site scripting](https://www.owasp.org/index.php/Cross-site_Scripting_(XSS)), and your choice of authenticaton determines how at risk you are.

Cookies support a [HTTPOnly](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies#Secure_and_HttpOnly_cookies) flag which prevents them from being accessible from JavaScript. This prevents a XSS attack from being able to steal the session token.

If you aren't using cookies you need to manually add the token to outgoing requests, which means it has to be accessible to JavaScript. If the token is accessible to JavaScript it can be stolen by a XSS attack. If you need to store the token so it can be re-used when the user reloads the page you either need to put it in local storage or a JavaScript accessible cookie[^4].

Because cookies are sent automatically with all requests to the matching domain and path they make your application vulnerable to CSRF attacks. Cookies support a [SameSite](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies#SameSite_cookies) attribute which helps prevent this but it's a good idea to use a [CSRF token](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#token-based-mitigation) too. Most web frameworks make this really easy.

If you are using an `Authorization` header instead it won't be added automatically to outgoing requests, so you aren't at risk of CSRF attacks.

Any CSRF protection can be disabled by a XSS attack, so if there is a XSS vulnerability you are screwed either way. So does this mean cookies aren't really more secure? It depends.

A CSRF attack requires targeting your application. The attacker needs to craft a request to a specific endpoint with the appropriate parameters. A XSS attack could just grab anything interesting from local storage and fire it off to a remote server for the attacker to look through at their leisure. I'm more concerned about rouge NPM packages and compromised ad network scripts than I am about targeted attacks, so storing the token where it's accessible to JavaScript seems worse to me.

To protect against session hijacking caused by either a XSS or CSRF attack it's a good idea to [require re-authentication](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#require-re-authentication-for-sensitive-features) for sensitive actions.

## Conclusion

When choosing an authentication method for your API it’s important to remember who the client is. If your API is only ever going to be used by a single page app on the same domain, **cookies are perfectly fine.** If you need to support third parties have a look at OAuth 2.0[^5].

[^1]: This example is vulnerable to cross site scriping attacks, don't use it for anything real. I just copied it from the PHP manual.

[^2]: Don't worry, I deleted that key after taking the screenshot.

[^3]: SHA256 [is fine for API keys](https://security.stackexchange.com/questions/151257/what-kind-of-hashing-to-use-for-storing-rest-api-tokens-in-the-database)

[^4]: Ideally you wouldn't store the token at all, and instead request a fresh access token when the page reloads. If the user has already authenticated the app and has an active session on the OAuth server you can do this transparently. Most OAuth servers support a `?prompt=none` query param for this reason.

[^5]: There is a cool alternative I didn't cover called [Oz](https://github.com/outmoded/oz). Oz is written by one of the original authors of the OAuth 2.0 spec that ended up leaving for reasons [explained in this article](https://hueniverse.com/oauth-2-0-and-the-road-to-hell-8eec45921529), and writing Oz instead.