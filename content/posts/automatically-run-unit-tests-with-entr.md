---
title: Automatically Run Unit Tests With entr
date: 2016-03-31T12:00:00-05:00
---

# Introduction

I do a lot of test driven development, and generally use tests a lot when I'm writing code.  It's nice to have your tests automatically run every time you save a file, so you know if you broke something.

This guide will show you how to make that happen.  Since I mostly do PHP development, this guide will focus on using PHPUnit, but this would work for any test runner.

# entr

[Entr](http://entrproject.org/) is a great little unix utility that does one thing well - automatically execute a task when something changes.  There are other utilities like [watchman](https://facebook.github.io/watchman/) plus a million node projects, but I like entr because it's simple.

## Installation

Mac:

```sh
brew install entr
```

Ubuntu/Debian:

```sh
sudo apt install entr
```

For other platforms refer to the [docs](https://bitbucket.org/eradman/entr/).  it looks like entr doesn't seem to work on Windows.  Sorry!

## Usage

Now that entr is installed, lets use it to run phpunit!

```sh
find src/ | entr -c phpunit
```

Ok so here is what's happening.

```sh
find src/
```

This command will list all the files in my `src` directory.  You can change this to include whatever directories you want to watch.

```sh
|
```

This is a [unix pipe](https://en.wikipedia.org/wiki/Pipeline_(Unix)) to pipe the output of find in to entr, so entr knows what files to watch.

```sh
entr -c
```

The `-c` flag tells entr to clear the terminal before invoking the command you specified.  For more flags, checkout `man entr`.

```sh
phpunit
```

The command to run.

## In Action

This is what it looks like:

<script type="text/javascript" src="https://asciinema.org/a/c7s87g8ar1yva0uotmmphgi4s.js" id="asciicast-c7s87g8ar1yva0uotmmphgi4s" async></script>

Pretty cool huh?

## More Complicated Commands

If you need to run a more complicated command, you can use `sh` and put the command in quotes.  Here's an example that uses osx [say](https://developer.apple.com/library/mac/documentation/Darwin/Reference/ManPages/man1/say.1.html) to let you know out loud if you break something.

```sh
find src/ | entr sh -c "phpunit && say 'good work' || say 'oh no'"
```

## File Limits

You might try to run entr and get a message like this:

```shell
entr: Too many files listed; the hard limit for your login class is 2560. Please consult http://entrproject.org/limits.html
```

That means you ran out of [file descriptors](https://en.wikipedia.org/wiki/File_descriptor).  Go read the [limit docs](http://entrproject.org/limits.html) and bump your file descriptor limit up and it should work fine (hint: you can get your osx version with `sw_vers`).
