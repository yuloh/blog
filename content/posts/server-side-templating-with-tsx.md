---
title: "Server Side Templating With JSX"
date: 2018-11-14T12:28:21-05:00
draft: false
---

## Introduction

TypeScript is great for writing typesafe backends in Node.js but what about the templates?  The commonly used template engines for Node leave a lot to be desired in the type safety department.

You won't get an error if you mess up variable name:

```typescript
import * as handlebars from 'handlebars';

// outputs '<div>Hello </div>'
console.log(handlebars.compile('<div>Hello {{ firstName }}</div>')({firstname: 'Matt'}));
```

...or pass an unexpected type:


```typescript
// outputs '<div>Hello [object Object]</div>'
console.log(handlebars.compile('<div>Hello {{ name }}</div>')({name:  {firstname: 'Matt', lastname: 'Allan'}}));
```

...or forget to pass a variable at all when it should be required:

```typescript
// outputs '<div>Hello </div>'
console.log(handlebars.compile('<div>Hello {{ firstName }}</div>')({}));
```

And you definitely won't get an error for invalid HTML.

But it turns out TypeScript already has a typechecked templating language built in already - JSX.

## JSX

[JSX](https://facebook.github.io/jsx/) is an XML like syntax that can be embedded in JavaScript.  By saving your file with a `.tsx` extension and setting the [appropriate compiler options](https://www.typescriptlang.org/docs/handbook/jsx.html) it will be typechecked and transformed by the TypeScript compiler.  It's normally used with frontend frameworks like React.  It turns out it works pretty well as a templating engine too.

### How It Works

When you set the compiler option `jsx` to `react` the TypeScript compiler will transform JSX into function calls.  By default the function used is `React.createElement`, but that can easily be changed using the `jsxFactory` compiler option.  For example with `jsxFactory` set to `h`, this JSX:

```typescript
const tpl = <div>Hello</div>;
```

...becomes this JavaScript:

```javascript
var tpl = h("div", null, "Hello");
```

Now you just need to define this function.  The original implementation is called [HyperScript](https://github.com/hyperhype/hyperscript).  It works pretty well but doesn't support components.

If you want to use components you can use [vhtml](https://github.com/developit/vhtml).  Using vhtml you can easily write functional components like you would in React, giving you a great alterative to template partials.  The rest of this guide uses vhtml since components are really useful.

Both HyperScript and vhtml define a `h` function (the h stands for 'hyperscript').  Once you import this function into your `.tsx` file you can write JSX.

```typescript
import * as h from 'vhtml';

const name = 'Matt';

console.log(<div>Hello {name}</div>);
```

### Components

To Reuse logic you can create a component.  A component is just a function that returns JSX.

```typescript
import * as h from 'vhtml';

function Hello () {
  return <div>Hello!</div>;
}

console.log(<Hello/>);
```

The component can accept props, which will be typechecked by the compiler.

```typescript
import * as h from 'vhtml';

function Hello (props: {name: string}) {
  return <div>Hello {props.name}!</div>;
}

console.log(<Hello name='Matt'/>);

// All of these raise a compiler error
console.log(<Hello />);
console.log(<Hello firstName='Matt'/>);
console.log(<Hello name={{first: 'Matt', last: 'Allan'}}/>);
```

If the component accepts children the children will be included in the props as `children`.  the children prop is an Array of already-serialized HTML strings.  This allows you to easily compose components, similar to how you would use partials in handlebars.

```typescript
import * as h from 'vhtml';

function TodoList ({children}: {children?: string[]}) {
  return <ul>{children}</ul>;
}

function TodoItem ({text}: {text: string}) {
   return <li>{text}</li>;
}

const tpl = (
  <TodoList>
    <TodoItem text='Learn TypeScript'/>
    <TodoItem text='Learn JSX'/>
  </TodoList>
);

console.log(tpl);
```

## Conclusion

I'm pretty excited about this idea and I plan to try it for my next backend Node.JS project.  There aren't very many options for building strongly typed templates (The [Lucky Framework](https://luckyframework.org) for Crystal is the only other option I can think of right now).  JSX is really powerful and vhtml seems to offer all of the functionality I would need.

If you would like to try it yourself I put together a small sample project which you can download [here](https://github.com/yuloh/server-side-tsx-example).  The example uses vhtml along with functional components.  If you try it let me know what you think.