---
title: "RFC3339 Date-Time Validation"
date: 2018-01-29T15:16:02-05:00
draft: false
---

# Introduction

I recently needed to validate RFC3339 date-time formatted dates.  RFC3339 format is used for just about everything on the Internet.  Most APIs use it and it's supported by OpenAPI (Swagger) and JSON Schema. It looks like this: `2018-01-29T20:36:30+00:00`.  The format is defined [here](https://tools.ietf.org/html/rfc3339#section-5.6).

Despite being used everywhere I couldn't find a good way to validate date-times in the programming language I was working in.  The built in DateTime object didn't support all valid RFC3339 dates and every regular expression I found was incorrect in some way or another.

I ended up sitting down with the IETF memo and writing my own regex, so here it is.

# The Regular Expression

Here is the regex in PCRE format:

```
/^(?<fullyear>\d{4})-(?<month>0[1-9]|1[0-2])-(?<mday>0[1-9]|[12][0-9]|3[01])T(?<hour>[01][0-9]|2[0-3]):(?<minute>[0-5][0-9]):(?<second>[0-5][0-9]|60)(?<secfrac>\.[0-9]+)?(Z|(\+|-)(?<offset_hour>[01][0-9]|2[0-3]):(?<offset_minute>[0-5][0-9]))$/i
```

For an explanation of what everything means you can view it on [regex101](https://regex101.com/r/H2n38Z/1/tests).  I also added some test cases illustrating the problems I found with existing implementations.  The regex uses named capture groups in an attempt to make it easier to read.  You might have to remove them if you port it to a language like Javascript.

# Limitations

The regex only validates the format as outlined in [section 5.6](https://tools.ietf.org/html/rfc3339#section-5.6).  It doesn't validate the restrictions outlined in [section 5.7](https://tools.ietf.org/html/rfc3339#section-5.7).