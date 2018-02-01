---
title: Understanding Dependency Injection Containers Part II - Autowiring
date: 2016-09-01T12:00:00-05:00
aliases: [/2016/dependency-injection-containers-part-II-autowiring/]
---

# Introduction

This article is a follow up to [Understanding Dependency Injection Containers]({{ site.baseurl }}2016/dependency-injection-containers/).  In the first part we learned how a basic dependency injection (DI) container works, and wrote our own closure based container.  This part is going to focus on autowiring.

# Autowiring

Our container is pretty helpful, but it's still a bit annoying to use.  Having to write `$container->set(...)` for every dependency is tiring.  Wouldn't it be nice if the container could just figure out what you wanted?

After all, I already have a type hint:

```php
<?php

class UsersController
{
    public function __construct(UserRepository $userRepository)
    { //                           ^ type hint
        $this->userRepository = $userRepository;
    }
}
```

Why can't the container just make a `UserRepository` for me?  That's what autowiring is.  The container will inspect the dependencies and try and resolve them for you.  Since the container works recursively, it can inject the dependencies of your dependency all the way down.  Using autowiring you can avoid writing 80% of your app's container bindings.

To resolve a class using autowiring, you would just call `get` with the class name like this:

```php
<?php

$controller = $container->get('UsersController');
```

If you are on PHP 5.5 or newer (which you really should be), you can use the `::class` constant instead.  The `::class` constant just returns the string like above.

```php
<?php

$controller = $container->get(UsersController::class);
```

# Writing The Code

## Getting Ready

Right now our `get` method looks like this:

```php
<?php

public function get($id)
{
    if (!$this->has($id)) {
        throw NotFoundException::create($id);
    }

    return $this->definitions[$id]($this);
}
```

If we add autowiring, we wont have a binding for the class name.  Let's rewrite the method to only invoke the closure if we have a binding, and not throw an exception yet.

```php
<?php

public function get($id)
{
    if ($this->has($id)) {
        return $this->definitions[$id]($this);
    }

    // Our autowiring code is going to go here.
}
```


## Finding Dependencies

To make this work, we need a way to figure out what the dependencies are of a class.  Luckily PHP has a complete [Reflection API](http://php.net/manual/en/book.reflection.php).  Reflection is a way to reverse engineer classes.  You can use reflection to see what methods exist, if they are public, if they are static, what the docblocks are, etc.  Most importantly, you can use reflection to see what a method's parameters are.

To get started, we make a new instance of [`ReflectionClass`](http://php.net/manual/en/class.reflectionclass.php), passing in the name of the class.

```php
<?php

// at this point $id will equal 'UsersController'.
$reflector = new ReflectionClass($id);
```

If the class doesn't exist we will get a `ReflectionException`, so let's make sure it exists first.

```php
<?php

if (!class_exists($id)) {
    throw NotFoundException::create($id);
}

$reflector = new ReflectionClass($id);
```

Now that we have our reflection class, we need to get the constructor.  If we call `getConstructor` we should get an instance of [`ReflectionMethod`](http://php.net/manual/en/class.reflectionmethod.php).

```php
<?php

/** @var \ReflectionMethod|null */
$constructor = $reflector->getConstructor();
```

If `getConstructor` returned null, there isn't a constructor.  That's pretty easy for us, since we can just instantiate the class and return it.

```php
<?php

if (is_null($constructor)) {
    return new $id();
}
```

Otherwise we need to figure out what the dependencies are.  There's a method called `getParameters` on `ReflectionMethod` that returns all of the method's parameters as instances of `ReflectionParameter`.

```php
<?php

/** @var \ReflectionParameter[] */
$dependencies = $constructor->getParameters();
```

## Instantiating Dependencies

We need an array of instantiated dependencies, so let's iterate over these and try to instantiate them.  If the dependency isn't a class we should throw, since we can't really do anything about it†.

If it is a class we just call `getClass()` to get the `ReflectionClass`, then access the name using `getName()`.  We can then use the class name to resolve the class from the container.

Since we are resolving the class by calling `get` again, the dependency could get autowired or resolve an entry in the container.

```php
<?php

$dependencies = array_map(function (ReflectionParameter $dependency) use ($id) {

    if (is_null($dependency->getClass())) {
        throw NotFoundException::create($id);
    }

    return $this->get($dependency->getClass()->getName());

}, $dependencies);
```

## Building Our Class

Finally we get to build our class!  The Reflection API has a method that lets us pass in an array of dependencies and it calls the constructor for us.  Just pass in our array of instantiated classes and we get our fully built object back.

```php
<?php

return $reflector->newInstanceArgs($dependencies);
```

## All Together Now

If you followed along, your `get` method should look something like this.  I added an extra check to make sure the class isn't abstract or an interface, since we can't instantiate that.  It would probably help to add some more checks, like if the constructor is private, if it allows null or default values, etc.

```php
<?php

public function get($id)
{
    // If we have a binding for it, then it's a closure.
    // We can just invoke it and return the resolved instance.
    if ($this->has($id)) {
        return $this->definitions[$id]($this);
    }

    // Otherwise we are going to try and use reflection to "autowire"
    // the dependencies and instantiate this entry if it's a class.
    if (!class_exists($id)) {
        throw NotFoundException::create($id);
    }

    $reflector = new ReflectionClass($id);

    if (!$reflector->isInstantiable()) {
        throw NotFoundException::create($id);
    }

    /** @var \ReflectionMethod|null */
    $constructor = $reflector->getConstructor();

    if (is_null($constructor)) {
        return new $id();
    }

    $dependencies = array_map(
        function (ReflectionParameter $dependency) use ($id) {

            if (is_null($dependency->getClass())) {
                throw NotFoundException::create($id);
            }

            return $this->get($dependency->getClass()->getName());

        },
        $constructor->getParameters()
    );

    return $reflector->newInstanceArgs($dependencies);
}
```

I pushed a git branch [over here](https://github.com/yuloh/container/tree/autowiring) with a basic test, so take a look if you want to see how it works.

# Summary

Hopefully that helps explain WTF is happening when your framework magically injects everything.

## Performance

A lot of people like to complain that reflection is an expensive operation and you shouldn't use it.  I like autowiring and use it all the time.  If I notice my app getting slow I can just add explicit entries in the container for everything and it won't autowire anymore.  Or, since I usually use PHP-DI I can just [cache the entries](http://php-di.org/doc/performances.html).

## Autowiring Containers

Sold on autowiring? These are the containers I know of that support it:

- [PHP-DI](http://php-di.org)
- [The Laravel Container](http://laravel.com/docs/5.3/container)
- [The Symfony Container](http://symfony.com/doc/current/components/dependency_injection/autowiring.html)
- [league/container](http://container.thephpleague.com)
- [Aura.Di](https://github.com/auraphp/Aura.Di)

Another option: if you are using a container that implements [container-interop](https://github.com/container-interop/container-interop)'s [Delegate Lookup](https://github.com/container-interop/container-interop/blob/master/docs/Delegate-lookup.md) feature, you can just delegate to league/container's [`ReflectionContainer`](http://container.thephpleague.com/auto-wiring/) for autowiring and keep using your existing container for everything else.

† We *could* check for the dependency's variable name if it isn't a class.  For Example, we could look for an entry named `dbPassword` if the variable is called `$dbPassword`.  That seems really sketchy and likely to cause bugs though.  Instead I would prefer to just add a binding with `set` if I need any non-class dependencies.
