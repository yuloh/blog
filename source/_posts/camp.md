---
title: Camp
date: 2016-01-16T12:00:00-05:00
extends: _layouts.post
section: content
tags: [php]
---

## Introduction

Today I released [camp](https://github.com/matthew-james/camp), a simple installer and development environment for learning modern PHP development.  When I first started learning PHP, trying to get everything installed properly was really frustrating.  Since then it's gotten even more complicated.  Camp is my attempt at making it simple.

## It's 2016. why can't I just double click to install?

Nearly every other languge has a one click installer.  PHP [links you](http://php.net/manual/en/install.macosx.packages.php) to three different osx package managers with no indication as to which one is best, and another site that asks you to [curl pipe sh](http://curlpipesh.tumblr.com/) without ssl and then sudo.

## The Stack

I started thinking about what I would need installed to teach someone modern PHP development.

### PHP

Obviously, you need PHP installed.  It should be a modern version so they can use modern packages and frameworks, and not get frustrated when code from tutorials doesn't work.  Last I checked OSX ships with 5.4, which is too old.

It also needs to be usable from the terminal.  You shouldn't have to learn about `~/.bash_profile`, `$PATH` and everything just to get to hello world.

### REPL

When I was first learning PHP I wrote a lot of `test.php` scripts and wrote little snippets in there.  I would write some code, CTRL-S to save, CTRL-tab over to the browser, and press F5 to refresh.  I got __really__ good at that.

Later I learned what a [REPL](https://en.wikipedia.org/wiki/Read%E2%80%93eval%E2%80%93print_loop) is, and I really wished I used one of those when I was learning.  PHP __sort of__ has one if you type `php -a`.  I think I tried it once and got frustrated.  This guy [bobthecow](https://github.com/bobthecow) thought `php -a` sucked too, so he wrote [psysh](http://psysh.org/).  Psysh is so rad.  Forgot how to use a function? type `doc`:

```
>>> doc array_get
function array_get($array, $key, $default = null)

Description:
  Get an item from an array using "dot" notation.

Param:
  array   $array
  string  $key
  mixed   $default

Return:
  mixed
```

Something threw an exception? Type `wtf`:

```
>>> wtf
ReflectionException with message 'Class Some\Class does not exist'
--
0:  () at vendor/illuminate/container/Container.php:736
>>>
```

### Web Server

You need a way to serve your scripts over HTTP.  When I first started I used Apache.  I spent alot of time editing `.conf` and `.htaccess` files.  While that is good stuff to know, it's just another source of potential issues.

Luckily PHP ships with a built in web server that requires 0 configuration.  Just type `php -S localhost:3000` and you are good to go.

### Database

I didn't get good at SQL for a long time.  I think I could barely use SQL for about 10 years after getting my first PHP book.  I was trying to learn MySQL, but if I was teaching someone I would teach SQLite first.

SQLite doesn't do the whole client/server thing.  You don't have to worry about starting a daemon.  There aren't any scary security configs when you set it up.

More importantly, your database is just a file.  I think this is easier to wrap your head around.  You can just copy and paste your database or delete it.  You don't need to learn `mysqldump` or install `phpmyadmin` to deploy.

I was worried there wouldn't be a good GUI like [sequel pro](http://www.sequelpro.com/), but there is!  [DB Browser](http://sqlitebrowser.org/) is awesome.  You can create data with a spreadsheet style interface and it shows you the cooresponding SQL.  Really nice way to learn, in my opinion.

### Composer

I would consider Composer essential to modern PHP development.  Getting composer installed and into your PATH is pretty straightforward.  You usually end up needing the global `~/.composer/vendor/bin` in your PATH too, so that you can use stuff like Laravel homestead or phpunit.

### Self Contained

When I was learning to program I seriously messed up my computer a few times.  Once I accidently unset my PATH and couldn't figure out how to fix it.  I've screwed up file permissions really bad.  It would be nice if you could wipe out your dev environment and start over.

## Alternatives

### "Just use Vagrant"

When I first started talking about this project, the response I got was 'just use Vagrant'.  I see the same response when beginners ask for help with development environment issues.  Let me try to break down why I think Vagrant is a bad choice when you are just learning.

If I want to use Vagrant for PHP development, I would probably use Laravel's homestead since it's the most popular box for PHP.  Let's say my goal is to be able to use the REPL and load a script in my browser.  I would end up doing all of this just to get to hello world:

- Download Vagrant.
- Download Virtualbox.
- `vagrant box add laravel/homestead`, and wait for a 1GB download to complete.
- Install the osx developer tools so I can use git.  First I need to create an Apple developer account.
- `git clone https://github.com/laravel/homestead.git Homestead`
- `cd Homestead && bash init.sh`
- Edit `/etc/hosts`, which requires sudo.
- `Vagrant up`.  There goes 2GB of my memory.
- `Vagrant ssh`
- `wget psysh.org/psysh`
- `chmod +x psysh`
- `sudo mv ./psysh /usr/local/bin/psysh`

To get started learning PHP, I had to:

- Download ~1.5 GB of files.
- Learn about virtualization, vagrant, sudo, git, the hosts file, and file permissions.
- Follow instructions from Virtualbox, Vagrant, Laravel, and Psysh.
- Try and figure out how to fix anything that goes wrong during any of those steps, with tools I've never used before.

It's hard to put yourself in the shoes of a beginner and realize how hard this stuff can be.  [These](https://laravel.com/docs/master/homestead) are the official homestead docs.  They assume you have used git before.  Imagine never having used a shell before, not knowing what git is, and not understand the PATH or that shell commands are really binaries in your PATH.

If you go to install homestead:

```
➜  ~  git clone https://github.com/laravel/homestead.git Homestead
zsh: command not found: git
```

Now you are googling around, reading stack overflow and trying to find the correct solution.  If you search "command not found: git osx" the [first search result](http://stackoverflow.com/questions/1835837/git-command-not-found-on-os-x-10-5) says:

> Open ~/.profile in your favorite editor and add the line
> export PATH=$PATH:/usr/local/git/bin

Remember, everyone doesn't know that starting the file with a dot makes it hidden.  On OSX you can't even view hidden files by default.  Now let's say you figure out how to show hidden files, and figure out that `~/` means home.  You go to your home folder, and `~/.profile` doesn't exist!  Is it safe to add it?  Does it need a special tag at the top like PHP?  What file format do you save it as?

The point is, Vagrant is terribly complicated for a beginner.  __Yes__, using Vagrant is a best practice, but when you are just starting out it adds way too much friction to the setup process for what you get out of it.

### Mamp

Mamp mostly works, but by default MAMP's PHP doesn't work from the terminal.  I googled how to install Composer using MAMP and the [first result](https://gist.github.com/irazasyed/5987693) says you need to open the `~/.bash_profile` in vim (!!) and add the Mamp PHP version to the PATH.  If they ever make it out of vim they are going to need to learn how to source or restart the terminal.  Then they are going to try and composer install something and it's going to fail because the instructions said to put `php5.4.10` in the PATH.  Sounds like an equally bad experience to me.

### Homebrew

I love homebrew, and I use it to install PHP on all my machines.  It's still a ton of shell commands to run.  You need to install homebrew, then tap versions, dupes, and homebrew-php.  After that you have to edit your bash profile and add brew's PHP to the PATH.  Still not the simple beginner friendly experience I'm hoping for.

## Camp

So that's what I have against the alternatives and why I built something else.

I'm really bad at C.  I am probably missing some important detail but so far everything works on the Macs I've tried it on.  It works like this:

Everything is precompiled to work out of the path `/Applications/Camp`.  Then it's packed up and put into a dmg image.  There is an application 'Camp Terminal', which is really just an applescript that launches the regular terminal.app and overrides the PATH to point to camp.

It turns out Travis CI has osx build servers so I use Travis to build a new dmg everytime I tag a release.  The dmg is uploaded to github and attached to the release.

The result is a 6MB dmg that you drag to install.  Zero configuration and it just works.

Camp isn't for everybody.  It only supports the bare minimum of features required to learn PHP.  Hopefully it will be useful.
