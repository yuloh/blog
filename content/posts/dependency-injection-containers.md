---
title: Understanding Dependency Injection Containers
date: 2016-07-15T12:00:00-05:00
---

# Introduction

If you are writing modern PHP, you will run across dependency injection a lot. Basically all dependency injection means is that if an object needs something, you pass it in.  So if you have a class like this:

```php
<?php

class UsersController
{
    public function index()
    {
        $repository = new UserRepository(
            new DbAdapter(
                'mysql:dbname=testdb;host=127.0.0.1',
                'dbuser',
                'dbpass'
            )
        );
        $users = $repository->all();
        return json_encode($users);
    }
}
```

```php
<?php

$controller = new UsersController();
```

...you would pass in (inject) the object it needs (the dependency) instead of instantiating it in the class.

```php
<?php

class UsersController
{
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index()
    {
        $users = $this->userRepository->all();
        return json_encode($users);
    }
}
```

```php
<?php

$dbAdapter = new DbAdapter(
    'mysql:dbname=testdb;host=127.0.0.1',
    'dbuser',
    'dbpass'
);
$userRepository = new UserRepository($dbAdapter);
$usersController = new UsersController($userRepository);
```

Dependency injection makes your code more flexible and easier to test.  If you want to learn more about dependency injection in general, check out [this summary in the PHP The Right Way guide](http://www.phptherightway.com/#dependency_injection).

# Containers

Once you are doing this all over the place, you end up writing a lot of boilerplate code to create each object.  Now when we create the controller we need to create it's dependency (the user repository), and that dependencies dependency (the db adapter) etc.  It's a pain.  To make that easier we use containers.

You can tell the container how to make your object, and use the container each time you need it.  That way you only have to write the code once.

Since we need to be able to get our objects back out of the container, we need to name them.  You can use a short name like 'db', but I like to use the class name.

```php
<?php

$container->set(DbAdapter::class, function () {
    return new DbAdapter(
        'mysql:dbname=testdb;host=127.0.0.1',
        'dbuser',
        'dbpass'
    );
});
```

## Writing a Simple Container

### Setting Entries

To understand how this works, lets write a simple container.  The first thing we need is a `set` method.  The `set` method will accept a string name for the entry and an [anonymous function](http://php.net/manual/en/functions.anonymous.php) for the definition.  We are type hinting the [Closure](http://php.net/manual/en/class.closure.php) class to make sure they actually give us an anonymous function.

When we set an entry we add it to an array, indexed by name.

```php
<?php

class Container
{
    private $entries = [];

    /**
     * Adds an entry to the container.
     *
     * @param string   $id       Identifier of the entry.
     * @param \Closure $value    The closure to invoke when this entry is resolved.
     */
    public function set($id, \Closure $value)
    {
        $this->entries[$id] = $value;
    }
}
```

### Getting Entries

Now we need to be able to get an entry back.  To get something out of the container, we just need to check our array of entries for the id.  If it's there we need to invoke the closure and return it's value.

```php
<?php


class NotFoundException extends \RuntimeException {}

class Container
{
    // existing code...

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     *
     * @return mixed The resolved value for the entry.
     */
    public function get($id)
    {
        if (!array_key_exists($id, $this->entries)) {
            throw new NotFoundException(sprintf('The entry for %s was not found.', $id));
        }

        return $this->entries[$id]();
    }
}
```

It should be clear why containers use closures now.  The code inside of the closure isn't actually ran until you try to resolve the entry, so you aren't creating the objects until you need them.

### Getting Entries That Need Other Entries

Now we have a simple container that works.  We can add entries and resolve them.  But what about entries that depend on other entries?  All you need to do is pass in the container as the first argument to the closure.

```php
<?php

class Container
{
    // existing code...

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     *
     * @return mixed The resolved value for the entry.
     */
    public function get($id)
    {
        if (!array_key_exists($id, $this->entries)) {
            throw new NotFoundException(sprintf('The entry for %s was not found.', $id));
        }

        // Pass in the container as the only argument to the closure when we invoke it.
        return $this->entries[$id]($this);
    }
}
```

Now when we set our entry, we can use the container instance that gets passed in to resolve our entry's dependencies.

```php
<?php

$container->set(DbAdapter::class, function () {
    return new DbAdapter('mysql:dbname=testdb;host=127.0.0.1', 'dbuser', 'dbpass');
});

$container->set(UserRepository::class, function (Container $container) {
    $dbAdapter = $container->get(DbAdapter::class);
    return new UserRepository($dbAdapter);
});
```

Since the closure isn't invoked until you try to resolve something, you can register your entries in whatever order you like and it will work.

### Binding Singletons

Right now `$container->get()` will invoke the closure each time it's called and create a brand new object.  Sometimes you want to bind a [singleton](https://en.wikipedia.org/wiki/Singleton_pattern) entry, where each call to `get` returns the same instance.

For example, you usually want a singleton for the database adapter, since it's going to try and create a new connection each time you make one.  Make sense?

Lets add a `share` method which returns the same entry each time.  To make a shared instance, we wrap the original closure in our own closure. The first time our closure is invoked we will invoke the original closure and save it's return value to a static variable.  Each time it's invoked after that we just return the resolved value.

```php
<?php

class Container
{
    // existing code...

    /**
     * Adds a shared (singleton) entry to the container.
     *
     * @param string   $id       Identifier of the entry.
     * @param \Closure $value    The closure to invoke when this entry is resolved.
     */
    public function share($id, \Closure $value)
    {
        $this->entries[$id] = function ($container) use ($value) {

            static $resolvedValue;

            if (is_null($resolvedValue)) {
                $resolvedValue = $value($container);
            }

            return $resolvedValue;
        };
    }
}
```

### Wrapping Up

Awesome!  Now you have a container that has enough features to be useful.  If you would like to see an example of what everything looks like together, checkout [the source code of yuloh/container](https://github.com/yuloh/container/blob/master/src/Container.php), a simple container built just like this.
