---
title: Organizing Business Logic With Synchronous Jobs
description: organizing business logic in Laravel applications with synchronous jobs
images: ["img/sync-jobs.png"]
tags: ["laravel", "php"]
date: 2019-07-31T12:55:46Z
draft: true
---

## Introduction

<!-- It's not always obvious where business logic goes -->
<!-- approaches like 'services', actions', everything in the model, everything in the controller -->
<!-- problems: inconsistent, break DI,  -->

## Synchronous Jobs

<!--
Benefits:
- consistency
- use generators
- keeps controllers light
- forces SRP
- forces names that explain what it does, not how
- easier to test
- easier to learn
- middleware!
- lazy resolution
- really simple to make it async later
-->

## Middleware

## Return Values

## Testing

<!-- using fakes -->
<!-- have to use shouldReceive if you return a value -->