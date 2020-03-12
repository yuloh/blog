---
title: Coding on Android
date: 2018-01-01T12:00:00-05:00
draft: true
---

## Introduction

I live off-grid and work as a web application developer.  Since I need to use my computer for ~8 hours a day and all of my power comes from the sun, I've gotten a lot more conscious of how much power I consume while writing code.  The most radical idea I've been experimenting with is doing all my work on an android tablet.

I normally use a Macbook Air, which takes 45 watts to charge.  My tablet can charge with a standard 2 amp USB charger, so the tablet only takes 10 watts to charge.  Since the battery life is about the same, I can run the tablet on 1/4 of the power.  That's a pretty big difference, so I wanted to see how much I could actually get done on a tablet.

## Working Locally

I looked around and found a few other examples of using a tablet as a development environment, but they all focused on using the tablet as a thin client for a remote server.  I pay for every GB of data I use, and using WIFI or 4G is going to reduce the battery life.  To actually replace my laptop, I needed to do as much work as possible locally.

## Setting Up Termux

I thought I would need to have a rooted tablet to get anything done, but everything actually works great without root.  The first step is to install [Termux](https://termux.com) from the Play Store.  You will need at least Android 6.0; Termux isn't going to support older versions anytime soon.

## Installing Packages

Termux looks like a simple terminal app, but it's actually a full Linux environment.  Termux is so powerful because it comes with `apt`, the package manager from Debian and Ubuntu.  The first thing we need to do is update the package list.

```bash
$ apt update
```

Once we update the package list, we can install some packages.  The default Termux install is really minimal, and it doesn't include programs you are probably used to like `rm` and `mv` by default.  To get a usable system you need to install the coreutils package.

```bash
$ apt install coreutils
```

### Finding Packages

Now you can go ahead and install any other packages you think you might need.  To get an idea of what's available, you can use `apt list`.  To find a specific package, use `apt search`.

Since the Termux enivronment is so different from a standard Linux environment, Termux uses it's own package repository.  You can see the build scripts Termux uses at the [termux/termux-packages](https://github.com/termux/termux-packages) github repository.  It's a great place to start if you want to add something that isn't available yet.

## Editing Text

### Graphical Text Editors

If you are used to using an editor like Sublime Text or TextMate, you will probably want to use a graphical text editor.  It looks like [Quoda](http://www.getquoda.com/) is the most powerful graphical editor available at the moment, but let me know if you find something better!

### Terminal Editors

Termux comes with every major unix text editor, so go ahead and `apt install` the one you want.  Even neovim is available if that's your thing.

<!-- vim screenshot -->

## Sharing Files

If you want to use share code between Termux and another app like Quoda, you will need to take a few additional steps.  First, you will need to grant permission to Termux to access shared storage.  Go ahead and run this command:

```bash
$ termux-setup-storage
```

Once you grant permission, there should be a new folder in home.

<!-- screenshot here -->

Each folder is a symlink to an android shared folder.  You can [read the  docs](https://termux.com/storage.html) to learn more about what each folder is.  To make it easy to share code, let's make a `code` folder in `~/storage/shared`.

```bash
$ mkdir ~/storage/shared/code
```

If you put your code here, it will show up in Quoda under `/sdcard/code`.  In the cyanogenmod file manager it shows up as `/storage/emulated/0/code`, so I guess the path can vary.

<!-- Quoda screenshot -->

## Setting Up Git


