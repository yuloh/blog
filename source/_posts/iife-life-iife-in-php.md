---
title: IIFE LIFE - IIFE In PHP
date: 2016-04-12T12:00:00-05:00
extends: _layouts.post
section: content
tags: [php]
---

## Introduction

An IIFE (pronounced iffy) is an immediately invoked functional expression.  It's a popular pattern in Javascript, but not widely used in PHP.

A Javascript IIFE looks like this:

```js
(function () {
    // code
})();
```

As of PHP7, a PHP IIFE looks like this:

```php
<?php
(function () {
   // code
})();
```

Exactly the same!  [Here's a running example.](https://3v4l.org/drJCV)

## What Changed

The change is in nikic's [Uniform Variable Syntax RFC](https://wiki.php.net/rfc/uniform_variable_syntax).  I didn't see this RFC prominently mentioned in any of the new features posts, but it's pretty great.

Another nice change is you can invoke closures assigned to object properties like `($this->closure)()`.  [Here's an example](https://3v4l.org/DVXE4).

## IIFEs In Older Versions

You can still use IIFEs in older verions of PHP. The syntax is just a little grosser.  Instead of putting `()` at the end, use `call_user_func`.  It looks like this:

```php
<?php
call_user_func(function () {
   // code
});
```

[Here's a running example.](https://3v4l.org/7LXhL)

## Why Use IIFEs

An IIFE is just another way to scope variables.  Since you can set [property and method visibility](http://php.net/manual/en/language.oop5.visibility.php), you should normally just do that.

That being said, the pattern can still be useful.  The first time I saw an IIFE being used was to scope some bootstrap code in a legacy codebase.  I think that is probably the best use case for an IIFE; variable scoping where a class doesn't make sense.

## PHP IIFES In The Wild

This is the only IIFE I've seen out there in open source PHP code:

[https://github.com/bobthecow/psysh/blob/master/bin/psysh#L15](https://github.com/bobthecow/psysh/blob/master/bin/psysh#L15)

The [Psysh](http://psysh.org) REPL uses an IIFE to keep all the variables in the bootstrap script from leaking.  Let me know if you have any more examples, I will add them to the list!
