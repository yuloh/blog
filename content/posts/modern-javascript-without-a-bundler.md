---
title: "Modern Javascript Without a Bundler"
date: 2018-11-01T14:59:42-05:00
draft: false
---

# Introduction

Lately I've been learning [TypeScript](https://www.typescriptlang.org/), a typed superset of JavaScript that compiles down to plain JavaScript.  It's easy enough to run my scripts with node, but running them in the browser can be complex.

By default TypeScript generates a corresponding .js file for every .ts file.  If you have the file `index.ts` and run `tsc` the TypeScript compiler will generate `index.ts` and place it alongside the TypeScript file.  Loading this in the browser is easy enough.  By default the compiler generates ES3 JavaScript which should run in any browser.

Things get more complicated once you start using [modules](https://www.typescriptlang.org/docs/handbook/modules.html).  For example, you might have a module `greeter` which exports a single function:

```javascript
// ./src/greeter.ts
export default function greeter(person: string) {
    return "Hello, " + person;
}
```

You could then use the `greeter` module like this:

```javascript
// ./src/index.ts
import greeter from "greeter"

let user = "Jane User";

document.body.innerHTML = greeter(user);
```

Modules were added natively to JavaScript in ES6, but most browsers don't support them yet.  To get around this you can transpile the ES6 module to another module format.  There are a lot of different formats that were popularized before ES6 to choose from.  By default TypeScript will transpile your modules into the CommonJS format, which you might know from node.

Browers can't load the CommonJS modules either, so the typical solution is to run your code through _another_ tool, called a bundler.  The most popular tool for CommonJS modules is probably [Browserify](http://browserify.org/).  Since it's usually more efficient to put all of the modules in a single file ('bundle') these tools will do that too.

The bundlers are not simple.  They each have their own API for registering plugins and config file format.  Putting together a working setup can be pretty challenging, especially if you are still learning.

# Bundling With The TypeSript Compiler

It turns out the TypeScript compiler can bundle your code, so you don't need a separate bundler like Browserify or webpack.

TypeScript supporting bundling for only two of the five module formats, AMD and System.  To create a bundle you specify the `outFile` parameter.  Assuming the entry point to your app is located in `index.ts` you can invoke the TypeScript compiler like this:

```bash
tsc --outfile ./dist/bundle.js --module system ./src/index.ts
```

...and it will generate a single bundle of SystemJs modules.  Alternatively you can add the arguments to your `tsconfig.json`.

```json
{
  "compilerOptions": {
    "module": "system",
    "outFile": "dist/bundle.js"
  },
  "include": [
    "src/**/*"
  ]
}
```

## Loading System.js Modules

Once you have a bundle of modules you need a way to load them.  [SystemJs](https://github.com/systemjs/systemjs) offers a minimal 1.5KB loader which is able to load the TypeScript bundle.  The TypeScript bundle is still using the `System.register('name', ...)` format to register modules which was deprecated in SystemJS 2.0.  You will need to include the `named-register` plugin as well for module registration to work correctly.

After you include SystemJs you just need to call `System.import` to load the entry point.  By default the module names correspond to the file names.


```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Typescript SystemJS Demo</title>
</head>
<body>
  <script src="https://unpkg.com/systemjs@2.1.1/dist/s.js"></script>
  <script src="https://unpkg.com/systemjs@2.1.1/dist/extras/named-register.js"></script>
  <script src="bundle.js"></script>
  <script>
    System.import('index');
  </script>
</body>
</html>
```

## Loading AMD Modules

It's also possible to generate a bundle using the AMD module format.


```bash
tsc --outfile ./dist/bundle.js --module amd ./src/index.ts
```

[RequireJS](https://requirejs.org/) is probably the most popular loader for AMD modules and works fine.  For this example I'm going to use [Almond](https://github.com/requirejs/almond) instead, which drops some features we don't need to cut the filesize down to 1KB.

```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Typescript AMD Demo</title>
</head>
<body>
  <script src="https://unpkg.com/almond@0.3.3/almond.js"></script>
  <script src="bundle.js"></script>
  <script>
    require('index');
  </script>
</body>
</html>
```

## File Watching

It's common for bundlers to recompile your code automatically when a file updates.  The TypeScript compiler can do this too using the `--watch` flag.

## ES6 Support

You probably don't need to setup [Babel](https://babeljs.io/).  The `--target` option of the TypeScript compiler determines which JavaScript version you target.  If you keep the default of ES3 or specify ES5 it will take care of transforming ES6 features into something the browser can understand.  You can use the `--allowJs` flag to transform non TypeScript files too.

## JSX

If you are using React, Preact, Mithril, or a similar framework you might want to write JSX.  The TypeScript compiler can optionally transform JSX too.  You just need to save the source file with a `.tsx` extension and set the `--jsx` flag to `React`.  If you are using a framework like Preact you also need to specify the `--jsxFactory` option, i.e. `--jsxFactory h` for Preact.

```bash
tsc --jsx React --jsxFactory h ./src/App.tsx
```

# Conclusion

You might still end up using a bundler eventually but it's nice to be able to write modern JavaScript without 300MB of dependencies in `node_modules`.