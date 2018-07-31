# ZipStream README

[![Build Status](https://travis-ci.org/maennchen/ZipStream-PHP.svg?branch=master)](https://travis-ci.org/maennchen/ZipStream-PHP)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/?branch=develop)
[![Code Coverage](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/?branch=develop)

Please see the file LICENSE.md for licensing and warranty information.  The
latest version of this software is available at the following URL: https://github.com/maennchen/ZipStream-PHP

## Upgrade to V1

* All options parameters to all function have been moved from an `array` to structured option objects. Please adjust them accordingly.
* The whole library has been refactored. The minimal PHP requirement has been raised to PHP 7.1.

## Installation
Simply add a dependency on maennchen/zipstream-php to your project's composer.json file if you use Composer to manage the dependencies of your project. Use following command to add the package to your project's dependencies:

```bash
composer require maennchen/zipstream-php
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
$zip->addFile('hello.txt', 'This is the contents of hello.txt');

# add a file named 'some_image.jpg' from a local file 'path/to/image.jpg'
$zip->addFileFromPath('some_image.jpg', 'path/to/image.jpg');

# add a file named 'goodbye.txt' from an open stream resource
$fp = tmpfile();
fwrite($fp, 'The quick brown fox jumped over the lazy dog.');
$zip->addFileFromStream('goodbye.txt', $fp);
fclose($fp);

# finish the zip stream
$zip->finish();
```

## Usage and options

You can also add comments, modify file timestamps, and customize (or
disable) the HTTP headers. It is also possible to specify the storage method when adding files,
the current default storage method is 'deflate' i.e files are stored with Compression mode 0x08.

See the [Wiki](https://github.com/maennchen/ZipStream-PHP/wiki) for details.

## Usage with Symfony and S3

You can find example code on [the wiki](https://github.com/maennchen/ZipStream-PHP/wiki/Symfony-example).

## Contributing
ZipStream-PHP is a collaborative project. Please take a look at the [CONTRIBUTING.md](CONTRIBUTING.md) file.

## About the Authors
* Paul Duncan <pabs@pablotron.org> - http://pablotron.org/
* Jonatan Männchen <jonatan@maennchen.ch> - http://commanders.ch
* Jesse G. Donat <donatj@gmail.com> - https://donatstudios.com
* Nicolas CARPi <nicolas.carpi@curie.fr> - https://www.elabftw.net
