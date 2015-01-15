# ZipStream README

[![Build Status](https://travis-ci.org/maennchen/ZipStream-PHP.svg?branch=master)](https://travis-ci.org/maennchen/ZipStream-PHP)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/?branch=develop)
[![Code Coverage](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/?branch=develop)

Please see the file LICENSE.md for licensing and warranty information.  The
latest version of this software is available at the following URL: https://github.com/maennchen/ZipStream-PHP

## Instalation
Simply add a dependency on maennchen/zipstream-php to your project's composer.json file if you use Composer to manage the dependencies of your project. Here is a minimal example of a composer.json file.

```json
{
    "require": {
        "maennchen/zipstream-php": "0.3.*"
    }
}
```

## Overview
A fast and simple streaming zip file downloader for PHP.  Here's a
simple example:
```php
# Autoload the dependencies
require 'vendor/autoload.php';

# create a new zipstream object
$zip = new ZipStream\ZipStream('example.zip');

# create a file named 'hello.txt' 
$zip->addFile('some_image.jpg', 'This is the contents of hello.txt');

# add a file named 'image.jpg' from a local file 'path/to/image.jpg'
$zip->addFileFromPath('some_image.jpg', 'path/to/image.jpg');

# add a file named 'goodbye.txt' from an open stream resource
$fp = tmpfile();
fwrite($fp, 'The quick brown fox jumped over the lazy dog.');
$zip->addFileFromStream('goodbye.txt', $fp);
fclose($fp);

# finish the zip stream
$zip->finish();
```

You can also add comments, modify file timestamps, and customize (or
disable) the HTTP headers.  See the class file for details.  There are a
couple of additional examples in the initial release announcement at the
following URL: http://pablotron.org/?cid=1535

## Requirements

  * PHP version 5.3 or newer.

## Contributors
Please take a look at the CONTRIBUTOR-README.md File.

## About the Authors
* Paul Duncan <pabs@pablotron.org> - http://pablotron.org/
* Jonatan Männchen <jonatan@maennchen.ch> - http://commanders.ch
* Jesse G. Donat <donatj@gmail.com> - https://donatstudios.com
