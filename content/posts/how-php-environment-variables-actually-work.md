---
title: How PHP Environment Variables Actually work
date: 2019-03-26T12:00:00-05:00
---

## Introduction

Laravel, Symfony, and other modern PHP frameworks use [environment variables](https://en.wikipedia.org/wiki/Environment_variable) to store security credentials and configuration that changes from one machine to the next.

The latest Laravel release made a [small change](https://github.com/laravel/framework/pull/27462) to the way environment variables are loaded.  This change ended up breaking [third party libraries](https://github.com/laravel/framework/issues/27949) and [Laravel itself](https://github.com/laravel/framework/issues/27828).

The ensuing discussion made it clear that many developers (including myself) don't realize how complex environment variables in PHP actually are. There are as many ways to read them as there are to write them and none of the options are foolproof.

Let's break down what environment variables are, how they work, and how to correctly use them in your code.

## What's an Environment Variable?

Every popular operating system since the 1980's has supported variables, just like a programming language.  When a process starts it inherits the variables from the parent process.  The process uses these variables to discover things about the environment it's running in, like the preferred place to save temporary files or the location of the user's home directory.

If you are using a Unix operating system like MacOS or Linux you can open up a terminal and see the value of the `$HOME` environment variable like this:

```bash
» echo $HOME
/Users/matt
```

If you are using Windows you can open Powershell and type this instead:

```
Write-Output $env:HOMEPATH
```

Normally environment variables are written in uppercase with underscores separating the words `LIKE_THIS`.

## Using Environment Variables For Application Config

The [Twelve Factor App Methodology](https://www.12factor.net) popularized the idea of using environment variables to store configuration for software.  Since then it's become the de facto standard, with first class support from web frameworks, cloud providers, and anything else you use to build software.

There are some [major downsides](http://movingfast.io/articles/environment-variables-considered-harmful/), so please do your research before adopting them if you haven't already.  If you are already using them read on to learn how to use them safely.

## Setting Environment Variables

Let's discuss how to set an environment variable so it's accessible to your application.

### CLI

Any environment variable that is set in the shell is available to any process you start. For example, you can already access the `HOME` variable we discovered above:

```bash
» php -r 'var_dump(getenv("HOME"));'
string(11) "/Users/matt"
```

However, you probably want to add your own variables. There are a lot of ways to do this.  The simplest way is to declare the environment variable right before you run the command:

```bash
» APP_ENV=local php -r 'var_dump(getenv("APP_ENV"));'
string(5) "local"
```

It won't persist, so you need to add it every time you run the command.  This quickly gets annoying as you add more environment variables.  You don't really want to use this technique in production but it's handy for quickly testing something.

Another useful trick for for unix systems is to use the `export` command.  Once you export an environment variable it's available in all subsequent commands until you exit the shell.

```bash
» export APP_ENV=local
» php -r 'var_dump(getenv("APP_ENV"));'
```

There are a lot of other options for [permanently setting environment variables](https://unix.stackexchange.com/questions/117467/how-to-permanently-set-environmental-variables) but they aren't really meant for secrets and typically require storing the environment variables in plain text.

### Web

When our web server handles a request we don't launch the process ourselves.  Instead [PHP-FPM](https://php-fpm.org) spawns the process.

The first option is to pass environment variables [from PHP-FPM](https://secure.php.net/manual/en/install.fpm.configuration.php).  By default PHP-FPM clears the existing environment variables before starting the PHP process.  You can disable this with the `clear_env` configuration directive.  After the environment is cleaned you can add your own variables with the `env[name] = value` syntax:

```ini
; somewhere in the pool configuration file (www.conf by default)
; declare a new environment variable
env[APP_ENV] = production
; reference an existing environment variable
env[DB_NAME] = $DB_NAME
```

The second option is to pass environment variables from the webserver.  You can configure this in Caddy using the [`env`](https://caddyserver.com/docs/fastcgi) parameter, in NGINX using [`fastcgi_param`](https://nginx.org/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_param), and in apache using [`PassEnv` or `SetEnv`](https://httpd.apache.org/docs/2.4/mod/mod_env.html).

**Confirm PHP-FPM is not accessible from the internet!** Otherwise anyone can inject environment variables using the same mechanism the web server uses to pass environment variables to your application.  Check the [listen.allowed_clients](https://secure.php.net/manual/en/install.fpm.configuration.php#listen-allowed-clients) setting.

### .env

Because setting environment variables is cumbersome the Ruby community came up with the `.env` file convention.  You declare you environment variables in a file called `.env` at the root of your project, and a library loads all of the environment variables into your application when it boots.

Originally .env files [weren't meant for production](https://github.com/bkeepers/dotenv#can-i-use-dotenv-in-production).  It's not very secure to leave all of your secrets in plain text and parsing the file is slow.  However, it seems to be fairly common.

Laravel gets around the parsing overhead by only accessing environment variables in config files and then [caching the config](https://laravel.com/docs/5.8/configuration#configuration-caching).  Symfony [recommends against](https://symfony.com/doc/current/components/dotenv.html#usage) using their DotEnv Component in production.

### Cloud Providers

It's common for cloud providers to make this easy, so check there first.  Heroku has a [`config:set` command](https://devcenter.heroku.com/articles/config-vars).  Laravel Forge allows adding environment variables from the control panel.  So does [Fortrabbit](https://help.fortrabbit.com/env-vars).

If you are provisioning your own servers the options listed above will work but they aren't very secure.  Kubernetes supports [defining environment variables](https://kubernetes.io/docs/tasks/inject-data-application/define-environment-variable-container/) and [using secrets for environment variables](https://kubernetes.io/docs/concepts/configuration/secret/#using-secrets-as-environment-variables).  If you are using Hashicorp's [Vault](https://www.vaultproject.io) and [Consul](https://www.consul.io) you can use [envconsul](https://github.com/hashicorp/envconsul) to launch your process with the environment variables populated.

## Reading Environment Variables

There are 3 different ways to read environment variables in PHP.  If you are setting the environment variables within PHP, which is how the .env libraries work, there are also 3 ways to set them.

It's important to understand the differences because each approach can return different data depending on the way your server is configured.

### $_SERVER and $_ENV

The [`$_SERVER` superglobal](https://secure.php.net/manual/en/reserved.variables.server.php) contain environment variables in addition to anything the web server passes along.

If the 'S' is removed from the [`variables_order`](https://secure.php.net/manual/en/ini.core.php#ini.variables-order) directive `$_SERVER` will not be populated.

```bash
» APP_ENV=local php -d variables_order=EGPC -r 'var_dump($_SERVER["APP_ENV"] ?? false);'
bool(false)
```

There is also an `$_ENV` superglobal.  Just like `$_SERVER` it can be disabled by removing `E` from the `variables_order` directive.  The default value for [development](https://github.com/php/php-src/blob/master/php.ini-development#L627) and [production](https://github.com/php/php-src/blob/b167dbe3ae9201e725d9f02817849e65bbb50c02/php.ini-production#L627) is `GPCS`, meaning `$_ENV` is most likely empty on your server.

So what's the difference between `$_ENV` and `$_SERVER`?  In CGI mode, [nothing](https://github.com/php/php-src/blob/6d24b92315697b4da46df3bdcccef1c29c3d0fa4/sapi/cgi/cgi_main.c#L699).  When using the built in webserver only `$_ENV` contains environment variables and only `$_SERVER` contains server variables such as headers, paths, and script locations.  When running a CLI script both `$_SERVER` and `$_ENV` contain environment variables, but `$_SERVER` also contains request information and CLI arguments.

Ultimately it's up to the [SAPI](https://stackoverflow.com/questions/9948008/what-is-sapi-and-when-would-you-use-it) to populate each superglobal.

`$_ENV` and `$_SERVER` are two distinct variables - altering one will not alter the other.  `$_ENV` and `$_SERVER` are populated the first time they are accessed.  If the [`auto_globals_jit`](https://www.php.net/manual/en/ini.core.php#ini.auto-globals-jit) directive is disabled they will be populated when the script starts.  If the environment is changed after the variables are populated (i.e. by calling `putenv`) the superglobals will not be updated.  Likewise updating `$_ENV` or `$_SERVER` will not alter the actual environment.  If you want to alter the actual environment you have to call [`putenv`](https://php.net/manual/en/function.putenv.php).

### getenv

The `getenv` function serves a similar purpose as the `$_ENV` superglobal.  However unlike the superglobals, `getenv` cannot be disabled with the `variables_order` directive.

```bash
» APP_ENV=local php -d variables_order= -r 'var_dump(getenv("APP_ENV"));'
string(5) "local"
```

So what happens when you call `getenv('APP_ENV')`?  Let's look at the [source code](https://github.com/php/php-src/blob/6d24b92315697b4da46df3bdcccef1c29c3d0fa4/ext/standard/basic_functions.c#L4064) to understand how it works.


```c
PHP_FUNCTION(getenv)
{
  // ...
  if (!local_only) {
    ptr = sapi_getenv(str, str_len);
    if (ptr) {
      RETVAL_STRING(ptr);
      efree(ptr);
      return;
    }
  }

  // ...
}
```

First we call `sapi_getenv` if the `local_only` parameter is false.  This function is a hook for the SAPI to load variables that don't exist in the normal environment.  It's the reason `getenv` can return HTTP headers.

```c
PHP_FUNCTION(getenv)
{
  // ...

  /* system method returns a const */
  ptr = getenv(str);
  if (ptr) {
    RETURN_STRING(ptr);
  }

  RETURN_FALSE;
}
```

Next we call `getenv` the c function (on Unix; Windows calls `GetEnvironmentVariableW`).  This is really important.  The superglobals will only read the system environment variables when they are first initialized.  `getenv` will read the system environment variables every time it's called.  This becomes a problem if you use threads.

# Thread Safety

The c function `getenv` is not required to be thread safe.  If you call `getenv` while another thread is calling `putenv` it can cause a segmentation fault.

This is easy to illustrate with the following code.  You will need PHP compiled with `zts` and the `pthreads` extension enabled to run this.

```php
<?php

$worker = new class() extends \Thread {
    function run()
    {
        while (true) {
            putenv('RAND' . rand() . '=value');
        }
    }
};

$worker->start();

while (true) {
    getenv('FOO');
}
```

If you run this from the command line you should see a segfault after about 30 seconds.

```bash
» php env_crash.php
Segmentation fault
```

This [excellent article](https://rachelbythebay.com/w/2017/01/30/env/) explains the issue in depth and includes an example c program you can run if you don't have `pthreads` installed.

How do we avoid the segmentation fault?  Some developers have begun suggesting that you use `$_SERVER` or `$_ENV` instead of `getenv` to read environment variables.  This certainly avoids the problem but it's not as easy as you might think.

As mentioned above, if you don't control the server you can't guarantee `$_SERVER` and `$_ENV` will be enabled.  `$_ENV` is disabled by default.  It's unlikely that `$_SERVER` is disabled but if you use `$_SERVER` your app won't work with PHP's built in web server.

Secondly, it's very difficult to guarantee that all the C libraries you depend on will avoid `getenv`.  For example, the `finfo_open` calls `getenv`  if you don't specify the `$magic_file`.  Even `$_SERVER` and `$_ENV` call `getenv` [when they are initialized](https://github.com/php/php-src/blob/2fd930d839c65c570bd37e5a964332fd85024ac8/main/php_variables.c#L799).  You would need to audit all of libc, PHP, every PHP library, and every extension to guarantee `getenv` wasn't being called.

Thirdly, if you are using pthreads the super globals are empty in the worker thread.  The only way to access the main thread's environment variables is to call `getenv`.

A much simpler solution is to avoid calling `putenv` in worker threads.  If you are using `putenv` to populate environment variables you only need to do that once.  Each worker thread will inherit the environment variables of the parent thread, so you don't need to populate them again.  Get the bootstrapping out of the way before you create a thread and you won't have any issues.

Some developers are using a threaded web server so they can't actually execute their code outside of the worker thread.  PHP [shouldn't be used with a threaded server](https://www.php.net/manual/en/faq.installation.php#faq.installation.apache2) anyway, but if you insist on doing this you can avoid the segmentation fault by only calling `putenv` if `getenv` returns false and wrapping the whole thing in a mutex.  Since environment variables are shared between threads only the first request will call `putenv`.

# Spawning Processes

Environment variables aren't only shared between threads, they are shared with child processes too.  When you spawn a process with `exec`, `passthru`, `system`, `shell_exec`, `proc_open`, or the backtick operator the child process inherits the parent's environment.

```bash
» APP_ENV=local php -r 'passthru("env");'
TERM_PROGRAM=Apple_Terminal
SHELL=/bin/zsh
TERM=xterm-256color
TMPDIR=/var/folders/9_/wn_qf7x97tl1l86lfxl1shg00000gn/T/
USER=matt
APP_ENV=local
```

This can be a security issue if you use environment variables for secrets and spawn untrusted sub processes.

As mentioned above, Adding a variable to `$_ENV` or `$_SERVER` does not add it to the actual environment.  Only environment variables returned from `getenv` will be passed to the child process.  Any variable added with `putenv` will be passed to the  child process because `putenv` modifies the environment.

`proc_open` allows you to specify the environment variables that should be passed to the sub process.  You can use `proc_open` for situations where you do not want to pass your application secrets to a sub process.

The [Symfony process component](https://symfony.com/doc/current/components/process.html) _does_ [pass `$_SERVER` and `$_ENV` to the child process by default](https://github.com/symfony/process/blob/e9f208633ac7ef167801cf4da916e07a6149fa33/Process.php#L1634).  To prevent that you can [explicitly set the environment variables](https://symfony.com/doc/current/components/process.html#setting-environment-variables-for-processes).


# Watch Out For HTTP Headers

I alluded to this above, but it's important enough to merit it's own section.  **Every method of accessing environment variables can return HTTP headers, including `getenv`.**

When a header is included with the environment in a CGI application it's prefixed with `HTTP_`.  Since the ["httpoxy" vulnerability](https://httpoxy.org) was announced PHP won't let the `Proxy` header override `HTTP_PROXY`, but any other environment variable starting with `HTTP_` (i.e. `HTTP_PROXIES`) is still affected.  In summary, never use environment variables that start with `HTTP_`.

`getenv` allows you to pass a second parameter, [`local_only`](https://www.php.net/manual/en/function.getenv.php).  If `true` [the SAPI will not be checked](https://github.com/php/php-src/blob/d49371fbd489b6767acf09afa7903e0a0558b5b4/ext/standard/basic_functions.c#L4082).  if `local_only` is true HTTP headers, variables set in fpm.conf, and variables set in the webserver configuration will be excluded.  It isn't possible to use `local_only` when returning all environment variables - `getenv(null, true)` will return `false`.

# Keep Secrets Secret

It's a lot easier to leak an environment variable than it is to leak a PHP variable.  It's important to understand all of the ways you can leak an environment variable if you are using them for secrets.

Environment variables from `$_ENV`, `$_SERVER`, and `getenv` are visible in the `phpinfo` output.

Environment variables passed on the command line can show up in the shell history.

You can [view the environment variables of a running process](https://serverfault.com/questions/66363/environment-variables-of-a-running-process-on-unix), but only if [the process is yours or you are root](https://security.stackexchange.com/questions/14000/environment-variable-accessibility-in-linux).

If you are using a `.env` file and it's in a public directory the web server will serve it as plaintext.  Watch out for [path traversal attacks](https://www.owasp.org/index.php/Path_Traversal).  If you are tricked into requiring or including the `.env` file the secrets will be rendered in the PHP script's output.

Environment variables are global.  Any PHP or C code can access them without having to know anything about your application.

Environment variables are passed to child processes, threads, and forks.

It's common for error handlers to record `$_SERVER`.  Both [Sentry](https://github.com/getsentry/sentry-php/pull/326) and [Airbrake](https://github.com/airbrake/phpbrake/issues/51) did this.

# getenv Doesn't Always Work

While researching this article I ran into some `getenv` bugs I hadn't seen before.

<blockquote class="twitter-tweet" data-lang="en" data-align="center"><p lang="en" dir="ltr">Anyone else run into this very strange <a href="https://twitter.com/hashtag/php?src=hash&amp;ref_src=twsrc%5Etfw">#php</a> behavior when using `getenv` to receive a `fastcgi_param`? I can access it with `getenv` but not with `getenv($key)`. <a href="https://t.co/98ah3yJl1n">pic.twitter.com/98ah3yJl1n</a></p>&mdash; Matt Allan (@__mattallan) <a href="https://twitter.com/__mattallan/status/1109182270873788416?ref_src=twsrc%5Etfw">March 22, 2019</a></blockquote>

<blockquote class="twitter-tweet" data-lang="en" data-align="center"><p lang="en" dir="ltr">Another weird `getenv` thing: If you have auto_globals_jit enabled you have to access a global somewhere in the script (even after!) for getenv to work. 🤯 <a href="https://t.co/Fvhkn3Zk8s">pic.twitter.com/Fvhkn3Zk8s</a></p>&mdash; Matt Allan (@__mattallan) <a href="https://twitter.com/__mattallan/status/1109203405753499654?ref_src=twsrc%5Etfw">March 22, 2019</a></blockquote>


This first issue only happens with variables set by the web server, i.e. NGINX's `fastcgi_param`.  The second issue happens with variables set by the web server or set by PHP-FPM.

The second issue is pretty amazing.  It makes sense when you consider how `auto_globals_jit` works ("Usage of SERVER, REQUEST, and ENV variables is checked during the compile time") but I don't think it was intentional.

# Conclusion

Environment variables in PHP are confusing, inconsistent, and sometimes dangerous.  CGI makes the problem worse by merging user input into the environment variables.  If you have to use them prefer `getenv`, avoid calling `putenv` in threads, never trust a variable starting with `HTTP_`, and verify you aren't leaking secrets to other processes or services.  If you are writing software that is going to be configured by inexperienced system administrators it's probably best to avoid environment variables entirely.

**Disclaimer:** I am not a security expert and this article does not cover every possible security risk.  It's up to you to determine what is secure for your situation.

<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>