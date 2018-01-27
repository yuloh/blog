---
title: JSON Schema References
date: 2016-05-05T12:00:00-05:00
---

## Introduction

I recently [wrote a validator](http://json-guard.thephpleague.com/) for JSON Schema.  One of the hardest parts to figure out was how to resolve references, so I figured  I would write a quick post explaining what I learned.

## References

A JSON reference is a way to reference another part of the document, or another schema.  There is actually a [IETF draft](https://tools.ietf.org/html/draft-pbryan-zyp-json-ref-03) for it, so it isn't specific to JSON Schema.

A simple example would look like this:

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "definitions": {
    "pet": {
      "type": "object",
      "properties": {
        "name":  { "type": "string" },
        "breed": { "type": "string" },
        "age":  { "type": "string" }
      },
      "required": ["name", "breed", "age"]
    }
  },
  "type": "object",
  "properties": {
    "cat": { "$ref": "#/definitions/pet" },
    "dog": { "$ref": "#/definitions/pet" }
  }
}
```

The reference is a [JSON pointer](https://tools.ietf.org/html/rfc6901) to another part of the document.  To validate the schema the reference needs to be resolved.  The naive way to do this is to inline the reference, so that the document looks like this:

```json
{
  "$schema": "http://json-schema.org/draft-04/schema#",
  "definitions": {
    // The definition is probably still here, but not actually used anymore.
  },
  "type": "object",
  "properties": {
    "cat": {
      "type": "object",
      "properties": {
        "name":  { "type": "string" },
        "breed": { "type": "string" },
        "age":  { "type": "string" }
      },
      "required": ["name", "breed", "age"]
    },
    "dog": {
      "type": "object",
      "properties": {
        "name":  { "type": "string" },
        "breed": { "type": "string" },
        "age":  { "type": "string" }
      },
      "required": ["name", "breed", "age"]
    }
  }
}
```

That works, but it breaks down because JSON Schema allows circular references.  A circular reference looks like this:

```json
{
  "person": {
    "properties": {
        "name": {
          "type": "string"
        },
        "spouse": {
          "type": {
            "$ref": "#/person"        // circular reference
          }
        }
    }
  }
}
```

If you try to inline that reference, you are going to end up with infinite recursion.

![Droste](/images/posts/json-schema-references/Droste.jpg)

## Inlining

The library I was using solved this by keeping track of how many levels of recursion took place, and throwing an exception if you exceeded them (they have since fixed this in v2.0.0).  In practice this meant that you had to re-adjust the depth depending on how nested your schema was.  It would also silently pass validation for any schemas with root pointer references, which is a pointer that starts with `#`.  This meant that you couldn't validate using the JSON Meta Schema, so it was impossible to verify your schema was correct.

## Lazy Resolving

The solution is to not inline the schema, but to replace circular references with a proxy object that is only resolved when accessed.  [Here is a link to the gist I wrote when I was figuring it out](https://3v4l.org/MA7ND).  The proxy object is only resolved as long as there is data to validate.  You can still use a max depth to prevent deeply nested payloads from killing your server.

If you do that, your data won't re-encode as JSON since it has circular references.  Instead you can put the `$ref` object back and everything looks like it did before you dereferenced it.
