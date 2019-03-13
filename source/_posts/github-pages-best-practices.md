---
title: Github Pages Best Practices
date: 2016-04-12T12:00:00-05:00
extends: _layouts.post
section: content
tags: []
---

# Introduction

[Github Pages](https://pages.github.com/) is a really nice (free) service for hosting static HTML websites.  You can push simple HTML files, but you can also push a [Jekyll](https://jekyllrb.com) site and Github will build it.  This site is built with Jekyll and hosted on Github Pages, and I use it for all of my code projects too.

The Jekyll setup for Github pages is a little weird, and there are a lot of gotchas to making it run correctly.  Hopefully this guide will save you from having to figure all of this out.

## A Caveat About Outdated Information

Github Pages used to be rougher around the edges.  Github Flavored Markdown didn't work, you couldn't do syntax highlighting without special template tags, and you had to do a lot of custom configuration to get things to work properly.

Luckily, those days are behind us!  [Github Pages is on Jekyll 3 now](https://github.com/blog/2100-github-pages-now-faster-and-simpler-with-jekyll-3-0), which fixed everything and added sensible defaults.  If you follow the rest of this guide you shouldn't need to do anything else to have a proper setup.

## Use The Github Pages Gem

Github Pages has a specific set of Jekyll Plugins they use, and they won't run anything else.  To make sure your site uses the right plugins when you run it locally, there is a [Github Pages Ruby Gem](https://github.com/github/pages-gem) you can use.

### WTF Are Gems?

Gems are basically little bundles of code for the Ruby programming language.  If you have Ruby installed you can use Gems.  If you don't have Ruby installed [go download it](https://www.ruby-lang.org/en/downloads/) since you need it to run Jekyll.

### Bundler

The Github Pages Gem wants you to run `bundle install`, but you probably don't have [bundler](bundler.io) installed yet.

Bundler is a gem for managing gems.  I know, very meta.  Anyway, you install it like this:

```bash
gem install bundler
```

Now that you have that installed you can actually add the Github Pages Gem.

### Installing

Ok, so to use the pages gem you need a Gemfile.  If you don't have a file called `Gemfile` (no extension) in the root of your Jekyll site, create one.  A Gemfile is just a list of the Gems your project needs to run correctly.  Once that's done, add this line:

```ruby
gem 'github-pages', group: :jekyll_plugins
```

That line makes your site use the github pages gem, and the `jekyll_plugins` part lets the plugin override some Jekyll settings to make your site act the same locally as it does on Github Pages.  Now type `bundle install` and the Gem is installed.

You should see a new file called `Gemfile.lock`.  You are going to commit that too.  If you don't bundler needs to go through and resolve all of the dependencies anytime someone runs `bundle install`, which is slow.

## Use Bundler to Run Jekyll

You can run Jekyll two ways - `jekyll` or `bundle exec jekyll`.  Always use `bundle exec jekyll`.  Why? Because `bundle exec` will always use the version of Jekyll from your Gemfile.  Just running `jekyll` uses the system wide version, which is usually not correct and you will get weird errors.

### Bin Stubs

Typing `bundle exec` before every command is really annoying.  The best solution is to use binstubs.

Bin Stubs are small ruby scripts you push on to your PATH, so that you use the bundle version instead of the global version by default.  You can follow [this guide](https://github.com/rbenv/rbenv/wiki/Understanding-binstubs#adding-project-specific-binstubs-to-path) to add the binstubs to the path, and then run `bundle binstubs jekyll` to install the jekyll binstub.  Now when you type `jekyll` it will use the bundler version.

## Use Rouge Syntax Highlighting

If you have code on your blog, Jekyll can automatically highlight it.  Using the jekyll highlighting instead of a javascript library like [highlight.js](https://highlightjs.org/) means your syntax highlighting works without javascript and it will load right away since it's just HTML.

### Usage

To make the syntax highlighting work, all you need to do is include a css file with the correct styles. On this site I just added this line to the `<head>` section of the default layout:

```html
<link rel="stylesheet" href="{{ site.baseurl }}assets/css/syntax/monokai.css">
```

Once the css is included, a code block like this:

`````markdown
```php
<?php
class UserController
{
    public function index()
    {
        return User::all();
    }
}
```
`````

...will render like this:

```php
<?php
class UserController
{
    public function index()
    {
        return User::all();
    }
}
```

### Finding Themes

Syntax highlighting uses a library called [rouge](http://rouge.jneen.net/), which uses the css stylesheets from [pygments](http://pygments.org/) from python, which means any pygments themes work with jekyll.

The best resource I found is [this site](http://jwarby.github.io/jekyll-pygments-themes/languages/javascript.html) by jwarby.  You can download existing themes or create your own.

## Pull In Repository Metadata Automatically

The Github Pages gem includes a really cool gem called [github-metadata](https://github.com/jekyll/github-metadata), which adds repository metadata to the `site.github` namespace.  You can pull that information in to your jekyll pages so you don't need to write it manually.

For example, this is the latest build revision of this site:

```
{{ site.github.build_revision }}
```

...which was rendered with this tag:

```liquid
{% raw %}
{{ site.github.build_revision }}
{% endraw %}
```

To use repository metadata, you just need to let Jekyll know which repository this is.  The easiest way is to add this line to `config.yml`:

```yaml
repository: username/repo-name
```

For more information, check out the [Github docs](https://help.github.com/articles/repository-metadata-on-github-pages/) or the [docs for the gem](https://github.com/jekyll/github-metadata).

## Use Jekyll Compose

This isn't really specific to Github pages, but I find it so helpful I had to add it.

In Jekyll you are supposed to have the date in the title, like `2016-04-12-learn-jekyll.md`.  You also need to put the files in a specific place, and add special 'front matter' to the top of each post.

Luckily there is a gem that handles all of that.  [Jekyll compose](https://github.com/jekyll/jekyll-compose) adds commands to create drafts, posts, and pages, and will set the correct filename and front matter.

### Installation

To install jekyll compose you just need to add the gem.  It won't be installed on the Github Pages server but that's fine, since we only need to use it locally.  Once you add the gem to your `Gemfile` it should look like this:

```ruby
source 'https://rubygems.org'

group :jekyll_plugins do
    gem "github-pages"
    gem "jekyll-compose"
end
```

Run `bundle update` and it should be installed.  Now you have some nice commands to create posts.

```bash
# Create a draft
jekyll draft "My new Draft"
# Publish it
jekyll publish _drafts/my-new-draft.md
```

Make sure to [read the documentation](https://github.com/jekyll/jekyll-compose) for more info.
