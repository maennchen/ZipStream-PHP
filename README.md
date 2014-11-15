# ZipStream 0.2.2 README

Please see the file LICENSE for licensing and warranty information.  The
latest version of this software is available at the following URL: https://github.com/maennchen/ZipStream-PHP

## Instalation
Simply add a dependency on maennchen/zipstream-php to your project's composer.json file if you use Composer to manage the dependencies of your project. Here is a minimal example of a composer.json file.

    {
        "require": {
            "maennchen/zipstream-php": "4.3.*"
        }
    }

## Overview
A fast and simple streaming zip file downloader for PHP.  Here's a
simple example:
    # Autoload the dependencies
    require 'vendor/autoload.php'
    
    # create a new zipstream object
    $zip = new ZipStream\ZipStream('example.zip');

    # create a file named 'hello.txt' 
    $zip->add_file('some_image.jpg', 'This is the contents of hello.txt');

    # add a file named 'image.jpg' from a local file 'path/to/image.jpg'
    $zip->add_file_from_path('some_image.jpg', 'path/to/image.jpg');

    # add a file named 'goodbye.txt' from an open stream resource
    $fp = tmpfile();
    fwrite($fp, 'The quick brown fox jumped over the lazy dog.');
    $zip->add_file_from_stream('goodbye.txt', $fp);
    fclose($fp);

    # finish the zip stream
    $zip->finish();

You can also add comments, modify file timestamps, and customize (or
disable) the HTTP headers.  See the class file for details.  There are a
couple of additional examples in the initial release announcement at the
following URL: http://pablotron.org/?cid=1535

## Requirements

  * PHP version 5.3 or newer (specifically, the hash_init and
    hash_file functions).

## About the Authors
* Paul Duncan <pabs@pablotron.org> - http://pablotron.org/
* Jonatan Männchen <jonatan@maennchen.ch> - http://commanders.ch
* Jesse Donat <donatj@gmail.com> - https://donatstudios.com
