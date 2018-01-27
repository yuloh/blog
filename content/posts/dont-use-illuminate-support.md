---
title: Don't Use Illuminate Support
date: 2016-04-21T12:00:00-05:00
---

**tl;dr**: If you are writing a framework agnostic package, don't use [illuminate/support](https://github.com/illuminate/support).

A lot of framework agnostic Composer packages (PHP) pull in [illuminate/support](https://github.com/illuminate/support), which contains helper functions and general purpose code used by the Laravel framework.  Usually it's because the support package has [nice helper functions](https://laravel.com/docs/5.2/helpers) like `array_get`, or because of the nice [collection class](https://laravel.com/docs/5.2/collections).

The helpers functions are nice, but I don't think developers appreciate the ramifications of choosing to pull that package in.  Everyone is afraid to get criticized for reinventing the wheel, so packages are pulling in [6000+ lines of code](https://gist.github.com/anonymous/8b85bc6b3858f4959011c38e2fa750bd) to avoid writing `isset($arr[$k]) ? $arr[$k] : null` themselves.


## Dependency Hell

Using illuminate/support (as of 5.2) makes the package dependent on illuminate/contracts, doctrine/inflector, a polyfill for random_bytes, and mb_string.  Luckily the dependency tree stops there.

mb_string is a [non default extension](http://php.net/manual/en/mbstring.installation.php), so it might not be installed on your user's machine.  If you aren't working with strings, you shouldn't force your users to recompile PHP just to use your package.  If you are, use [stringy which can use a polyfill](https://github.com/danielstjules/Stringy#installation).

## Version Conflicts

There are [over 6000 packages](https://packagist.org/packages/illuminate/support/dependents) dependent on illuminate/support.  If your user installs your package and another package dependent on illuminate/support, they need to depend on the same version or there will be a conflict.  A cursory glance shows that a lot of the framework agnostic dependents are stuck on 4.1.*.

The dependencies are even worse if your package is used with Laravel or Lumen.  The user can't upgrade to the next version until you (and every other package using illuminate/support) release a new version.  Now your framework agnostic package is preventing the user from updating their framework!  The only alternative is to have unbounded ranges like `>5.2`, but that's a really bad idea.

## Global Namespace

The illuminate/support package pulls [52 functions](https://github.com/illuminate/support/blob/master/helpers.php) in to the global namespace.  It's nice when using the framework to not have to use namespaces, but a dependency of a dependency shouldn't pollute the global namespace.

But the helpers are great, why wouldn't you use them?  The case transform libraries don't work [like you would expect them to](https://github.com/yuloh/case-transform-tests) and `dd` [is useless from the terminal](https://asciinema.org/a/5nbhmqvd6lfpyz4ereihvzifa).  Not everyone wants those 52 functions to behave like you expect them to, so let your users keep their global namespace.

## Type Hint Pollution

This is admittedly a minor problem, but it gets irritating when writing code 40+ hours a week.  The support package has a lot of often used class names like Collection, Request, Response, and App.  Every time I import a namespace my IDE tries to import the wrong collection or a useless Laravel facade instead of the actual class I need.  You are pulling in [a whole directory](https://github.com/illuminate/support/tree/master/Facades) of classes that don't even work outside of Laravel!  I guarantee you aren't using the facades in your framework agnostic package, so please stop pulling them in to my project.

## Failure Modes

Now your package depends on 3 different organizations not violating SemVer.  Is it really worth risking a [left-pad disaster](http://blog.npmjs.org/post/141577284765/kik-left-pad-and-npm) to avoid writing a few lines of code?

## Aim For Few Dependencies

Try to use as few dependencies as possible.  If you look at the [framework agnostic packages by the League](https://packagist.org/packages/league/) you will notice they all have few or no dependencies.  It's OK to write a small helper class for the 2 or 3 functions you actually need.  If you don't want to write your own, copy and paste the function you need and properly attribute it as per the license.

If you are [testing all possible versions of dependencies](https://blog.wyrihaximus.net/2015/06/test-lowest-current-and-highest-possible-on-travis/), you will waste more time setting up a build matrix on your CI server than you will writing your own code and tests for a couple of helper functions.

## Single Purpose Replacements

If you need a ton of functionality, you probably should use a package instead of writing everything yourself.  Since illuminate/support covers a ton of use cases, here are some single purpose libraries that you can use instead.

### [doctrine/inflector](https://github.com/doctrine/inflector)

Make words singular and plural.  This is actually a dependency of illuminate/support.

### [danielstjules/Stringy](https://github.com/danielstjules/Stringy)

Covers all of the string transformation functions.  This was a dependency of illuminate/support until 5.2.

### [dusank/knapsack](http://dusankasan.github.io/Knapsack/)

Collection pipeline.  This is the only package I've found that looks comparable to the Laravel collections.

### [anahkiasen/underscore-php](http://anahkiasen.github.io/underscore-php)

Replacements for the array functions if you use dot notation.  I don't know of a good alternative that supports dot notation with zero dependencies.  I just write vanilla PHP for this.  You can use the [null coalesce operator](https://wiki.php.net/rfc/isset_ternary) in PHP7+ instead of using `array_get`.
