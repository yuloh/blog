---
title: Using composer with legacy PHP applications
date: 2015-02-14T12:00:00-05:00
extends: _layouts.post
section: content
tags: [php]
---

## Introduction

[Composer](https://getcomposer.org/) is a dependency manager and autoloader for PHP.

## The Issue

Most legacy applications do not follow a consistent standard like [PSR-0](http://www.php-fig.org/psr/psr-0/) or [psr-4](http://www.php-fig.org/psr/psr-4/).

Normally composer takes a classname like this:

    Acme\Foo\Bar

And checks the autoload section of ````composer.json````.  If you had something like this:

    {
        "autoload": {
            "psr-4": { "Acme\\": ["src/"] }
        }
    }

It would check the ````src```` folder for a file at ````src/Foo/Bar.php````.  The legacy application I work on uses folders for organization, yet namespaces are virtually nonexistent.  PSR-4 autoloading is not going to work without renaming, reorganizing, & namespacing a few hundred files.  After this I would be  fixing all of the manual includes developers used because the autoloader was unreliable, and spot testing a ton of files.

## Using a classmap

I thought this would be a problem but composer works fine with any naming convention.   The trick is to setup your ````composer.json```` with a [classmap](https://getcomposer.org/doc/04-schema.md#classmap) key.

On production, composer recommends you use the ````--optimize-autoloader (-o)```` flag.  This takes PSR-0 / PSR-4 autoloading and generates a classmap.

A classmap is a big array of class names and their cooresponding paths.  You can register your classes folder under a classmap key and it will always generate a classmap instead of trying to autoload using a PSR standard.

Here is the example from Composer's website:

    {
        "autoload": {
            "classmap": ["src/", "lib/", "Something.php"]
        }
    }

Now running ````composer dump-autoload```` will generate an associative array of all your class names and their cooresponding files.

The first time you dump-autoload you may have some class conflicts.  In my situation developers somehow (using includes?) managed to have multiple classes with the same non-namespaced names in our autoloaded folder.

You may have to manually namespace a couple files to prevent conflicts, than dump-autoload again.  It still beats namespacing and renaming __every__ file.

## Autoloading functions

Paul M. Jones' book [Modernizing Legacy Applications in PHP](https://leanpub.com/mlaphp) recommends moving all functions to static methods on classes to allow autoloading as a first step towards modernizing legacy code.

This is a great first step as you can now move global functions into namespaced classes.  Legacy applications seem to like including page specific functions on those pages only, and as a result have conflicting names with other functions.

The other benefit is functions are only loaded when you need them now, instead of every function being included on every request, regardless of being used.

If you can't do this yet, you can use composer to load functions instead of includes.  Just add a [files](https://getcomposer.org/doc/04-schema.md#files) key to your autoload section.

    {
        "autoload": {
            "files": ["src/MyLibrary/functions.php"]
        }
    }

## Benefits

In my unscientific tests, composer was able to load classes twice as fast on average, and sometimes 10x faster than the legacy autoloader we were using.  This is due to using a classmap instead of recursively scanning directories for a file.  Files that were nested deeply loaded incredibly slow, as the legacy autoloader was doing file_exists over and over.

When refactoring legacy code, its really helpful to get vendor code out of the source tree so you can see what is going on.  Using Composer for it's primary purpose, as a dependency manager, allows moving other people's code out of your repository. It makes project wide search easier when refactoring, and also helps discourage modifying vendor files without making it explicit.