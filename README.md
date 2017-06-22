# ZipStream README

[![Build Status](https://travis-ci.org/maennchen/ZipStream-PHP.svg?branch=master)](https://travis-ci.org/maennchen/ZipStream-PHP)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/?branch=develop)
[![Code Coverage](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/maennchen/ZipStream-PHP/?branch=develop)

Please see the file LICENSE.md for licensing and warranty information.  The
latest version of this software is available at the following URL: https://github.com/maennchen/ZipStream-PHP

## Installation
Simply add a dependency on maennchen/zipstream-php to your project's composer.json file if you use Composer to manage the dependencies of your project. Here is a minimal example of a composer.json file.

```json
{
    "require": {
        "maennchen/zipstream-php": "^0.4.1"
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

You can also add comments, modify file timestamps, and customize (or
disable) the HTTP headers.  See the class file for details.  There are a
couple of additional examples in the initial release announcement at the
following URL: http://pablotron.org/?cid=1535

## Overview for using ZipStream in Symfony 
Using ZipStream in Symfony requires use of Symfony's StreamedResponse (Symfony\Component\HttpFoundation\StreamedResponse). Wrap your call to ZipStream in Symfony's StreamedResponse function passing in any required arguments for your use case.

Example for using ZipStream in a controller action to zip stream files stored in an AWS S3 bucket by key:
```php

use Symfony\Component\HttpFoundation\StreamedResponse;
use Aws\S3\S3Client;
use ZipStream;

//...

/**
 * @Route("/zipstream", name="zipstream")
 */
public function zipStreamAction()
{
    //sample test file on s3
    $s3keys = array(
      "ziptestfolder/file1.txt"
    );

    $s3Client = $this->get('app.amazon.s3'); //s3client service
    $s3Client->registerStreamWrapper(); //required

    //using StreamedResponse to wrap ZipStream functionality for files on AWS s3.
    $response = new StreamedResponse(function() use($s3keys, $s3Client) 
    {

        // Define suitable options for ZipStream Archive.
        $opt = array(
                'comment' => 'test zip file.',
                'content_type' => 'application/octet-stream'
              );
        
        //initialise zipstream with output zip filename and options.
        $zip = new ZipStream\ZipStream('test.zip', $opt);

        //loop keys - useful for multiple files
        foreach ($s3keys as $key) {
            // Get the file name in S3 key so we can save it to the zip 
            //file using the same name.
            $fileName = basename($key);

            //concatenate s3path.
            $bucket = 'bucketname'; //replace with your bucket name or get from parameters file.
            $s3path = "s3://" . $bucket . "/" . $key;        

            //addFileFromStream
            if ($streamRead = fopen($s3path, 'r')) {
              $zip->addFileFromStream($fileName, $streamRead);        
            } else {
              die('Could not open stream for reading');
            }
        }

        $zip->finish();

    });

    return $response;
}
```


## Requirements

  * PHP version 5.6 or newer.

## Contributors
Please take a look at the CONTRIBUTOR-README.md File.

## About the Authors
* Paul Duncan <pabs@pablotron.org> - http://pablotron.org/
* Jonatan Männchen <jonatan@maennchen.ch> - http://commanders.ch
* Jesse G. Donat <donatj@gmail.com> - https://donatstudios.com
