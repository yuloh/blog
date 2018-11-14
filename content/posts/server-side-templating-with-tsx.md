---
title: "Server Side Templating With JSX"
date: 2018-11-14T12:28:21-05:00
draft: false
---

# Introduction

TypeScript is great for writing typesafe backends in Node.js but what about the templates?  The commonly used template engines for Node leave a lot to be desired in the type safety department.

You won't get an error if you mess up variable name:

```typescript
import * as handlebars from 'handlebars';

const source   = '<div>Hello {{ firstName }}</div>';
const template = handlebars.compile(source);
const html     = template({firstname: 'Matt'});

console.log(html); // outputs '<div>Hello </div>'
```

Or pass an unexpected type:


```typescript
import * as handlebars from 'handlebars';

const source   = '<div>Hello {{ name }}</div>';
const template = handlebars.compile(source);
const html     = template({name:  {firstname: 'Matt', lastname: 'Allan'}});

console.log(html); // outputs '<div>Hello [object Object]</div>'
```

Or forget to pass a variable at all when it should be required:

```typescript
import * as handlebars from 'handlebars';

const source   = '<div>Hello {{ firstName }}</div>';
const template = handlebars.compile(source);
const html     = template({});

console.log(html); // outputs '<div>Hello </div>'
```

And you definitely won't get an error for invalid HTML.

But it turns out TypeScript already has a typechecked templating language built in already - JSX.

# JSX

[JSX](https://facebook.github.io/jsx/) is an XML like syntax that can be embedded in JavaScript.  By saving your file with a `.tsx` extension and setting the [appropriate compiler options](https://www.typescriptlang.org/docs/handbook/jsx.html) it will be typechecked and transformed by the TypeScript compiler.  It's normally used with frontend frameworks like React.  It turns out it works pretty well as a templating engine too.

If you are using TSX misspelling a variable name results in an error:

```typescript
import * as h from 'vhtml';

function Hello ({firstName}: {firstName: string}) {
  return <div>Hello {firstName}</div>;
}

// causes error:
// src/components/Hello.tsx(8,14): error TS2322: Type '{ firstname: string; }' is not assignable to type '{ firstName: string; }'.
//  Property 'firstname' does not exist on type '{ firstName: string; }'.
console.log(<Hello firstname='Matt' />);
```

Passing an unexpected type, forgetting to pass required types, and even incorrectly nesting elements will cause a type error.

```typescript
import * as h from 'vhtml';

function Hello ({name}: {name: string}) {
  return <div>Hello {name}</div>;
}

// causes error:
// src/components/Hello.tsx(7,20): error TS2322: Type '{ firstName: string; lastName: string; }' is not assignable to type 'string'.
console.log(<Hello name={{firstName: 'Matt', lastName: 'Allan'}} />);
```

```typescript
import * as h from 'vhtml';

function Hello ({name}: {name: string}) {
  return <div>Hello {name}</div>;
}

// causes error:
// src/components/Hello.tsx(7,14): error TS2322: Type '{}' is not assignable to type '{ name: string; }'.
// Property 'name' is missing in type '{}'.
console.log(<Hello />);
```

```typescript
import * as h from 'vhtml';

function Hello ({name}: {name: string}) {
  // causes error:
  // src/components/Hello.tsx(4,30): error TS17002: Expected corresponding JSX closing tag for 'p'.
  // src/components/Hello.tsx(4,36): error TS17002: Expected corresponding JSX closing tag for 'div'.
  return <div><p>Hello {name}</div></p>;
}

console.log(<Hello name='Matt'/>);
```

# How It Works

When you set the compiler option `jsx` to `react` the TypeScript compiler will transform JSX into function calls.  By default the function used is `React.createElement`, but that can easily be changed using the `jsxFactory` compiler option.  For example, this JSX:

```typescript
const tpl = <div>Hello</div>;
```

...becomes this JavaScript:

```javascript
var tpl = h("div", null, "Hello");
```

Now you just need to define this function.  The original implementation is called [HyperScript](https://github.com/hyperhype/hyperscript).  It works pretty well but doesn't support components.

If you want to use components you can use [vhtml](https://github.com/developit/vhtml).  Using vhtml you can easily write functional components like you would in React, giving you a great alterative to template partials.

I put together a small example which you can view [here](https://github.com/yuloh/server-side-tsx-example).  The example uses vhtml along with functional components.