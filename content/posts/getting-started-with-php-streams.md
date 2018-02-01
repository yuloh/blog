---
title: Getting Started With PHP Streams
date: 2015-11-04T12:00:00-05:00
aliases: [/2015/getting-started-with-php-streams/]
---
# Streams

## Why Use Streams

Most of the time, you open files as strings, using something like `file_get_contents()`.  This works, but PHP will load the entire file in to memory as a string.  If you open a 20MB file, it's going to consume 20MB of memory.  With small files, this is convenient and it's OK.  Most PHP installations are set to use a really small amount of memory, like 64MB [^n].  If you wanted to work with a really big file, you are going to run in to problems.

```php
<?php
$iso = file_get_contents(__DIR__ . '/ubuntu-14.04.3-desktop-amd64.iso');
```

```
> PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted (tried to allocate 1480316816 bytes)
```

The solution is to use streams.  A stream isn't a string; it's a special data type.  A stream is a 'resource' that can be streamed.  Instead of loading the entire file in to memory, you get a 'handle'.

```php
<?php
$handle = fopen(__DIR__ . '/ubuntu-14.04.3-desktop-amd64.iso', 'rb');
```

`fopen` just means 'file open' and get a stream.  I passed it two mode flags, which tell PHP how I want to open the file.  The 'r' means read only, and that will make sure it reads from the beginning of the file.  The 'b' means binary safe, since I was opening a binary file.

So if we were trying to copy the stream we just opened, we could do this:

```php
<?php
// Open a (w)rite stream to pointing to 'new-file.iso'
$dest = fopen(__DIR__ . '/new-file.iso', 'w');
// Copy our read stream to the write stream
stream_copy_to_stream($handle, $dest);
```

The computer reads one byte at a time, instead of trying to read it all at once.  It's a lot like the old Windows folder animation of one piece of paper at a time being copied from the source to the destination.

## Stream Basics

### Opening

Ok, so let's go over some stream basics.  We already saw how to open a stream:

```php
<?php
// Open some-file.txt in (r)ead mode
$handle = fopen('some-file.txt', 'r');
```

The first parameter is the filename, and the second parameter is the mode.  The handle that the function returned lets you reference the stream later.  If you need to use a different mode, check the [documentation](http://php.net/manual/en/function.fopen.php), there are alot of them.

### Reading

So if the point of a stream is to limit how much you read in to memory at once, how do you read a little bit at a time?

Lets go over a somewhat realistic scenario.  Your manager emails you a giant list of phone numbers:

```
1-598-555-4384
1-840-555-1259
1-413-555-0418
1-836-555-6894
1-166-555-2257
1-472-555-7151
1-497-555-2215
1-564-555-5668
1-110-555-0622
1-701-555-6237
1-822-555-6872
1-604-555-5119
1-794-555-9796
1-578-555-2250
1-401-555-1756
```

So theres a billion phone numbers, but there's one per line.  So you fire up google and search 'get line stream php'.  And since it's PHP there are two functions that do almost the same thing.  Awesome.  lets just use `fgets` since it only requires one parameter.

Open a terminal in the same directory as your phone number file, then boot up [psysh](http://psysh.org/).


```php
>>> $numbers = fopen(__DIR__ . '/numbers.txt', 'r');
=> stream resource #311
>>> $line = fgets($numbers)
=> "1-598-555-4384\n"
```

Awesome, we read one line. Let's run it again:

```php
>>> $line = fgets($numbers)
=> "1-840-555-1259\n"
```

Yay, it read the next line.  But wait, does that mean it knows where to start reading from?  BAM, IT DOES.  It's called a file pointer.  And if you ask, it will ftell you where it is.

```php
>>> ftell($numbers);
=> 30
```

So, we are 30 bytes in to the file. Count em up.  Lets start over.

```php
>>> rewind($numbers)
=> true
>>> ftell($numbers);
=> 0
```

Now we are back at the beginning.  Radical.  Now let's write that phone number parser.


```php
<?php

// Open the stream
$numbers = fopen(__DIR__ . '/numbers.txt', 'r');
// Wrap our parser in an infinite loop, so it won't stop until we say so
while (true) {
	// Read a line
	$buffer = fgets($numbers);
	// The docs say it will return false when there arent any bytes left,
	// so we break out of our loop and let the sript die when that happens.
	if (!$buffer) {
		break;
	}
	// Here you would upload it to the 'system', whatever that is.
	// Lets just echo it to make sure our script works.
	echo $buffer;
}
// Close the stream.  PHP will do this anyway when the script ends,
// but it's good practice to always do it.
fclose($numbers);
```

[^n]: You can check your memory limit by running `php -i | grep memory_limit` from the terminal.
