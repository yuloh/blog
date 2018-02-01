---
title: Why you should be using annotations (with Doctrine)
date: 2016-02-19T12:00:00-05:00
aliases: [2016/why-you-should-be-using-annotations/]
---

# Introduction

I do a lot of PHP development with [Doctrine ORM](http://www.doctrine-project.org/).  If you aren't familiar with it, it's a library for making your PHP objects talk to the database.  So if you have a class like this:

```php
<?php


class Article
{
    protected $id;

    protected $title;
}
```

...and a table like this:

```bash
mysql> describe articles;
+-------+--------------+------+-----+---------+-------+
| Field | Type         | Null | Key | Default | Extra |
+-------+--------------+------+-----+---------+-------+
| id    | int(11)      | NO   |     | NULL    |       |
| title | varchar(255) | NO   |     | NULL    |       |
+-------+--------------+------+-----+---------+-------+
```

...then you need a way to map them to each other, and say that the table `articles` cooresponds to the class `Article`, the `id` field cooresponds to the `id` property, etc.

A common way to do this is to use annotations:

```php
<?php

/**
 * @ORM\Entity
 * @ORM\Table(name="articles")
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $title;

    // ...
}
```

Annotations are special comments that doctrine scans and uses to generate metadata.  But a lot of developers see this and immediately reject it.

<iframe src="//giphy.com/embed/5xtDarC0XyqmUhD5eDK?html5=true" width="480" height="266" frameBorder="0" class="giphy-embed" allowFullScreen></iframe>
<br>

If you would like to learn more about why people hate this, you can read [this article](http://theunraveler.com/blog/2012/php-annotations-are-a-horrible-idea/).  The general idea is that annotations are just comments, and comments shouldn't affect how your code functions.

# Alternatives

So, you don't have to use annotations with doctrine.  A lot of developers prefer to use XML, YAML, or plain PHP mapping files.  If we were mapping the above class in YAML, it would look like this:

```yaml
App\Article:
  type: entity
  table: articles
  id:
    id:
      type: integer
      generator:
        strategy: AUTO
  fields:
    title:
      type: string
```

I initially didn't like annotations either, and I worked with a ton of entities mapped in XML.  I felt like the author of the aformentioned article - I had an "icky feeling" and "it just feels wrong to put application logic inside comments".  After doing that for awhile, I think it's all dogma.

## Problems

### Brittle Code

The mapping file needs to say this property => this column name for every single property, of every single entity in your application.  For relationships add another string for each relationship that points to that.  That is a **lot** of strings which depend on your properties and classes being named exactly the same.

If you decide that post 'body' should be called 'content', you need to search through the XML files and find every single instance of 'body' and update it.

The big objection against annotations is that your code becomes coupled to the comments and requires them to run.  Instead I ended up having to maintain a replica of my codebase in XML, down to each individual property name, which is still required for the code to run.

I would do a refactor and all of the code would be 100% correct, but an xml file would still have an old class name or property name and everything would break.  If you don't have tests that touch that property, you may not even notice it happened.

Annotations aren't nearly as brittle.  Remember, with annotations you don't have to write the properties or class names; you just annotate them.  The only time you need to put a property name in an annotation is for a relationship.

### Cognitive Load

It's nice to pretend that your PHP objects are the entire world and the database doesn't exist most of the time, but sometimes you need to know what property maps to what.  I realized I was constantly split paneing my editor so I could see where a property was saving in the database.

Another downside of having the configuration so far from the code is that I would forget to update the mapping.  I would remove a bunch of deprecated properties but forget to remove them from the mapping, or vice versa.  Then to get them in sync I would split pane and go down the line, comparing XML to PHP code.  Ughh.

# Annotations FTW

At this point I have an "icky feeling" about mapping every entity's classname and property names in an external config file.  Having to maintain an XML version of every entity in your application really sucks, and the other mapping formats aren't any better.

Annotations are a lot better.  Real language support for annotations would be nice, but "when in Rome".
