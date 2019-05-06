---
title: Automate Code Formatting For Laravel Projects
description: Announcing a new laravel package that automates code formatting
images: ["img/laravel-code-style.png"]
tags: ["laravel", "php"]
date: 2019-05-06T18:53:00Z
---

## Introduction

I can't stand inconsistent formatting of source code.  If I open a file and I can tell who wrote it just by looking at it something is wrong.

In my ideal world every file in the project looks exactly the same.  The braces are always in the same place, the number of spaces never changes, and the code is always organized the same way.

I don't even have to like the standard.  When I write code in the Go programming language I use the included `gofmt` tool to format my code.  I _really_ dislike some of the choices made by `gofmt`, but I happily follow the conventions because I never have to think about it.

Since I primarily write PHP applications with Laravel I have been on a quest to find a tool like `gofmt` for my everyday work.  The existing solutions are lacking in some way or another so I just released [a new package](https://github.com/matt-allan/laravel-code-style).  This article covers why it exists, what it  does differently, and how it works.

## Why enforce consistent code formatting?

Enforcing a consistent code style has a lot of benefits.  As the bulk of my programming career has involved working on 15+ year old code bases maintained by generations of programmers I've painfully felt the lack of each of these.  A consistent code style makes your code:

**Easier to read.**  You can focus on the code, not the formatting.  You don't have to mentally parse the code into something you understand.

**Easier to write.**  The fewer questions you have to answer while writing code the better.  If you aren't sure how to format something your IDE can tell you or you can take your best guess and let your tooling automatically fix it later.

**Easier to diff.**  The history in your version control system is a lot more useful when it isn't cluttered with minor formatting changes.  Using a consistent code style can reduce merge conflicts.

**Easier to refactor.**  Find and replace is easier when you don't have to write fancy regex to deal with inconsistent whitespace.

**More productive.**  If I had a nickel for every time I debated  white space usage, trailing commas, or the order of class elements...

## What's wrong with PSR-2?

In the PHP world we have the [PSR-1](https://github.com/php-fig/fig-standards/blob/4a10f033b4e5690ad90d656281e6e72b82c0626e/accepted/PSR-1-basic-coding-standard.md) and [PSR-2](https://github.com/php-fig/fig-standards/blob/4a10f033b4e5690ad90d656281e6e72b82c0626e/accepted/PSR-2-coding-style-guide.md) standards.  These standards are great and did a lot to improve the consistency within the PHP Ecosystem.  However, they are not a panacea.  The PSRs leave a lot unspecified, resulting in wildly different code styles that still follow PSR-2.  The authors are currently working on a [new standard](https://github.com/php-fig/fig-standards/blob/4a10f033b4e5690ad90d656281e6e72b82c0626e/proposed/extended-coding-style-guide.md) to address some of these shortcomings.

As an example of what I am talking about, consider these two versions of the same code:

```php
<?php declare(strict_types=1);
namespace Vendor\Package;

use BarClass as Bar;
use FooInterface;
use OtherVendor\OtherPackage\BazClass;

class foo extends Bar implements FooInterface
{
    /**
     * Do something
     * @param string $a
     * @param int|null $b
     * @return void
     */
    public function sample_method(string $a, ?int $b = null) : void
    {
        if ($a === $b) {
            bar();
        } elseif (!$a > $b) {
            $foo->bar($arg1);
        } else {
            BazClass::bar($arg2, $arg3);
        }
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Vendor\Package;

use FooInterface;
use BarClass as Bar;
use OtherVendor\OtherPackage\BazClass;

class Foo extends Bar implements FooInterface
{
    /**
     * Do something.
     *
     * @param  string $a
     * @param  int|null $b
     *
     * @return void
     */
    public function sampleMethod(string $a, ?int $b = null): void
    {
        if ($a === $b) {
            bar();
        } elseif (! $a > $b) {
            $foo->bar($arg1);
        } else {
            BazClass::bar($arg2, $arg3);
        }
    }
}
```

Both of these files are valid PSR-2.  However there are a lot of differences between the two:

- blank line after opening tag
- full stop at the end of the phpdoc summary
- class and method name case
- trailing whitespace after the not operator
- blank line before the namespace
- import order
- ...etc.

Clearly there is a lot left undefined by PSR-2.

To keep the framework's code consistent the [Symfony coding standard](https://symfony.com/doc/current/contributing/code/standards.html) augments PSR-2 with over 100 additional rules.  The [Laravel coding style](https://laravel.com/docs/5.8/contributions#coding-style) comes close with just under 100 rules.

## Laravel's code style

The canonical definition of Laravel's code style is StyleCI's Laravel preset.  [StyleCI](https://styleci.io) is a service that automatically fixes code style when someone opens a pull request or makes a commit.  Laravel uses StyleCI across all of its repos.

Outside of the StyleCI preset there isn't much documentation telling you what the rules are.  To get an idea of what the rules are [have a read through the StyleCI docs](https://docs.styleci.io/presets#laravel) or [check out an example](https://github.com/matt-allan/laravel-code-style/blob/020f56a420e674f71c50d15f201bb80fe36a1ea7/examples/User.php).

## Why use Laravel's standard?

You don't have to follow Laravel's code style just because you're writing Laravel apps.  If you have a standard that works for you that's awesome, continue using it.

If you _don't_ have a standard you have to decide how you are going to format your code.  As explained above, the PSR standards aren't specific enough.  The Symfony coding standard doesn't match any of the code that is included in a new Laravel app, doesn't match any of the code generated with the `make` methods, and includes some controversial opinions like requiring [yoda conditions](https://en.wikipedia.org/wiki/Yoda_conditions), so that isn't a good solution either.  You could write your own standard but you need to maintain ~100 lines of rules and you need to convince everyone to agree with those rules.


## What about StyleCI?

You might be thinking, since StyleCI already has a Laravel preset why don't I just use StyleCI?

StyleCI is really helpful.  I've been using it for 3 years and it's been invaluable on large projects with lots of developers.  Sometimes I wish I had a local tool though.

When I contribute to a project using StyleCI I have to make the PR, wait for StyleCI, download the diff, apply it, amend the commit, and force push [^1].  It's tiring.

With a local tool I can format the code _before_ I commit, which makes my life a lot easier.

## Building a Laravel package

So I put together a simple tool.  The bulk of the work is done by [PHP CS Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer).  The [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) is another popular option, but I went with PHP CS Fixer because it supports all of the rules I needed.

After installing PHP CS Fixer I started figuring out the configuration.  Some of the PHP CS Fixer rules were an exact match for the StyleCI rule which made things easy.  A lot of the rules have different names though.  For example, the StyleCI rule `length_ordered_imports` corresponds to the `ordered_imports` PHP CS Fixer rule with the configuration option `'sort_algorithm' => 'length`.  In some cases PHP CS Fixer _used to have_ a matching rule but it had since been renamed.

To ensure the rules stay in sync I added a PHPUnit test that formats the entire Laravel framework and compares the results. If an existing Laravel file does not match our rule set the build is failed.

## Usage

I tried to make setting up the package as simple as possible.  Once you require the package with composer you publish the PHP CS Fixer config.  The configuration is already setup for a Laravel application.  You don't need to waste any time setting paths or deciding which rules to use.

<script id="asciicast-244685" src="https://asciinema.org/a/244685.js" async></script>

With a brand new Laravel application a few files will be updated because of the `no_unused_imports` rule.  Laravel includes the unused imports so you know they are available, but you don't really want to keep unused imports around once you start developing.

If you wanted to disable this rule it's easy enough.  Just open up the `.php_cs` file and add `'no_unused_imports' => false` to the rules array.

  ```php
 <?php

// existing code...

->setRules([
    '@Laravel' => true,
    'no_unused_imports' => falsem
]);
```

 A few of the rules are considered 'risky' so they aren't enabled by default.  Risky rules are rules that might break something.  As an example, the `psr4` rule would change the class name to match the file name and any code using the old name would break.  To enable risky rules you need to add the `@Laravel:risky` preset and set `isRiskyEnabled` to true like this:

 ```php
 <?php

// existing code...

->setRules([
    '@Laravel' => true,
    '@Laravel:risky' => true,
])
->setRiskyAllowed(true);
```

## Integrating code formatting with your workflow

You probably don't want to manually run the `fix` command every time you edit a line of code.  Luckily this is easy to automate.

Git supports a feature called 'hooks' that lets you run scripts before committing or pushing.  There is a nifty composer plugin called [GrumPHP](https://github.com/phpro/grumphp) that will setup the hooks for you.  Just use the [PHP-Cs-Fixer 2 task](https://github.com/phpro/grumphp/blob/362a7394a3f766c374b9062b6d393dd0d61ca87a/doc/tasks/phpcsfixer2.md) to check your code style.

It's easy to add support to your editor too.  I setup PHPStorm to run php-cs-fixer when I use the 'reformat code' keyboard shortcut.  Check the [PHP CS Fixer docs](https://github.com/FriendsOfPHP/PHP-CS-Fixer#helpers) for more info.

## Conclusion

The package is available [here](https://github.com/matt-allan/laravel-code-style).  Give it a try and let me know what you think.

[^1]: I can have StyleCI commit the fix automatically instead, but I always forget and then my push fails because I'm missing upstream changes in my local branch.