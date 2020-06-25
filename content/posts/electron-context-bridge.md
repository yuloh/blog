---
title: "Safer Electron apps with ContextBridge"
description: "Build safer Electron apps with ContextBridge"
date: 2020-06-24
draft: false
---

## Introduction

Lately I've been building [a desktop app](https://github.com/matt-allan/expanse/) with [Electron](https://www.electronjs.org/) that helps you avoid repetitive strain injuries. Electron lets you build native desktop apps using web technologies. Combining native and web development creates unique security risks that weren't readily apparent to me when I started.

The Electron docs have [a great section on security](https://www.electronjs.org/docs/tutorial/security), but there aren't a lot of examples showing how an Electron app built following those best practices actually works. I'm going to attempt to fill that gap with this article.

## Background 

Every Electron app has two processes - the 'main' and 'renderer' processes. The main process, as the name implies, is the first process that boots when your app starts. If the main process opens a browser window, that browser window is a renderer process.

The main process can access any [Node.JS](https://nodejs.org/en/) API. It can read and delete files, create notifications, spawn web servers, execute shell commands, etc. The renderer can't access native APIs by default, but it's common to need native capabilities in the renderer. For example, you might need to read settings from a SQLite database stored on the filesystem.

The problem with exposing native capabilities to the renderer is any untrusted code executing in the renderer process has access to those native capabilities too. If you expose the filesystem module, untrusted code could read SSH keys, write malware, or wipe the filesystem.

Even if you don't _intentionally_ run untrusted code, your app might be vulnerable to [cross-site scripting attacks](https://owasp.org/www-community/attacks/xss/). If an NPM module you depend on has a XSS vulnerability or gets compromised your app is going to run untrusted code. I checked my `node_modules` folder for React's [`dangerouslySetInnerHTML`](https://reactjs.org/docs/dom-elements.html#dangerouslysetinnerhtml) and found 218 usages. The risk is real. So how do you minimize the risks?

## The remote module

The simplest way to expose native capabilities is the [remote module](https://www.electronjs.org/docs/api/remote). It's enabled by default.

Unfortunately the remote module [has a lot of issues](https://medium.com/@nornagon/electrons-remote-module-considered-harmful-70d69500f31) and isn't very secure. You should always disable the remote module when creating a `BrowserWindow`.

## IPC 

If you don't use the remote module you have to use [IPC](https://www.electronjs.org/docs/api/ipc-main) to communicate between the main and renderer processes.

After reading the docs it might seem like this is secure since you're only exposing IPC methods that you've explicitly written, but there's a problem with this approach too.

To import the `ipcRenderer` module in the renderer, you have to enable node integration, and enabling node integration gives the renderer access to *any* node module, which is exactly what we're trying to avoid.

Luckily there is a solution: using a 'preload script'.

## Preload scripts

A preload script has access to Node APIs, even if node integration is disabled. You can use this functionality to expose IPC methods to the renderer.

A common way to do this is to add methods to the `window` object, i.e.

```js
import { ipcRenderer } from "electron";

window.app = {
  setFullscreen: (flag) => ipcRenderer.invoke("setFullscreen", flag),
};
```

This pattern has downsides too. If you do this, you can't enable [context isolation](https://www.electronjs.org/docs/tutorial/context-isolation). And if you don't enable context isolation, it's easy to [accidentally leak native APIs to the renderer](https://blog.doyensec.com/2019/04/03/subverting-electron-apps-via-insecure-preload.html).

This brings us to our final Electron API, the [context bridge](https://www.electronjs.org/docs/tutorial/context-isolation).

## Context Bridge

The context bridge allows you to *safely* expose native APIs to the renderer from the preload script. Our prior example can be written like this with the context bridge:

```js
import { ipcRenderer, contextBridge } from "electron";

contextBridge.exposeInMainWorld("app", {}
  setFullscreen: (flag) => ipcRenderer.invoke("setFullscreen", flag),
);
```

Using the context bridge, you can disable the remote module, disable node integration, enable context isolation, and in many cases enable sandboxing. At the time of this writing [these are the options I'm using in my own app](https://github.com/matt-allan/expanse/blob/d96055c92d9f4cba7845d1d755e2f54a83423643/src/window.ts#L23):

```js
mainWindow = new BrowserWindow({
  // ...
  webPreferences: {
    preload: MAIN_WINDOW_PRELOAD_WEBPACK_ENTRY,
    allowRunningInsecureContent: false,
    contextIsolation: true,
    enableRemoteModule: false,
    nodeIntegration: false,
    sandbox: true,
  },
});
```

## Closing thoughts

Hopefully that makes sense and saves you some time researching. From what I can tell this is the recommended approach for Electron apps going forward. I put together an [Electron Fiddle](https://www.electronjs.org/fiddle) demonstrating the technique; you can find it [here](https://gist.github.com/matt-allan/f2ba61de30cfde2aa1f90d44177d68cf).