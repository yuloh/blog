---
title: "[meta] Moving From Jekyll to Hugo"
date: 2018-01-27T16:10:45-05:00
---

# Why

This site is hosted on github pages and until today was built using Jekyll.  I would push commits and github would rebuild the site for me.  Locally I pulled in the github-pages gem so I could preview my site (you can read more about that [here](http://mattallan.org/posts/github-pages-best-practices/)).  Last week I got a notification that one of my gems had a security vulnerability so I updated everything.  I had to update my ruby version which made bundler happy but broke vim.  Then I updated vim which caused vim to be incompatible with my python version.  After I finally got bundler and vim working and committed my changes the next github pages build broke my site and spewed a bunch of broken HTML.

I had been wanting to try [hugo](https://gohugo.io/) anyway so rather then spend my Saturday debugging Jekyll I made the switch.

# Setting Up Hugo

Since github doesn't build hugo sites automatically the setup is a little different.  You need to create a new repo to hold the source (mine is [yuloh/blog](https://github.com/yuloh/blog)) and push the generated code to your pages repo.  These are the basic steps I followed:

1. Create a new hugo site following the [quick start guide](https://gohugo.io/getting-started/quick-start/)
2. Copy all the posts from Jekyll's `_posts` directory to Hugo's `content/posts` directory and update the front matter.
3. Copy all the drafts from Jekyll's `_drafts` directory to Hugo's `content/posts` directory and update the front matter, adding `draft: true`.
4. Copy all the assets from Jekyll's `assets` directory to Hugo's `static` directory.
5. Setup [syntax highlighting with Chroma](https://gohugo.io/content-management/syntax-highlighting/) and copy over my Rouge theme.
6. Delete everything but the CNAME from my github pages repo.
7. Follow [this guide](https://gohugo.io/hosting-and-deployment/hosting-on-github/#github-user-or-organization-pages) to add my github pages branch as a submodule.

# First Impressions

## Speed

Everything is really fast.  The hugo server takes around 200 milliseconds to boot.  Jekyll takes about 3 seconds.  Livereload takes 20 milliseconds with Hugo; Jekyll takes 2 seconds.

## Everything is Simpler

This is a big one for me.  Installation is simple.  I just have to download the hugo binary and that's it.  No ruby version requirements, no bundler, nothing.  It's great.  Instead of using liquid tags to link to assets you just use the path.  You don't need to include the date in the file name.  You can easily publish a post by deleting `draft: true` from the frontmatter.  You can override theme files by creating a file at the same path in `/static`.  This is subjective but I much prefer `toml` to `yaml` for config files.

## Deployment

I originally used Jekyll because I didn't have to build and deploy myself but it's pretty easy with hugo.  The published site is just a submodule and the example script from the hugo docs makes publishing a single command.

# Conclusion

I'm enjoying Hugo so far.  If you were thinking about making the switch I would suggest giving it a try.