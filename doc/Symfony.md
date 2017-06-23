## Overview for using ZipStream in Symfony 
Using ZipStream in Symfony requires use of Symfony's StreamedResponse when used in controller actions. 

Wrap your call to the relevant ZipStream stream method (i.e. addFile, AddFileFromPath, addFileFromStream) in Symfony's StreamedResponse function passing in any required arguments for your use case.

Using Symfony's StreamResponse will allow Symfony to stream output from ZipStream correctly to users' browsers and avoid a corrupted final zip landing on the users' end.

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
In the above example, files on AWS S3 are being streamed from S3 to the Symfony application via fopen call when the s3Client has registerStreamWrapper applied.  This stream is then passed to ZipStream via the addFileFromStream() function, which ZipStream then streams as a zip to the client browser via Symfony's StreamedResponse. No Zip is created server side, which makes this approach a more efficient solution for streaming zips to the client browser especially for larger files.

For the above use case you will need to have installed [aws/aws-sdk-php-symfony](https://github.com/aws/aws-sdk-php-symfony) to support accessing S3 objects in your Symfony web application. This is not required for locally stored files on you server you intend to stream via ZipStream.

See official Symfony documentation for details on [Symfony's StreamedResponse (Symfony\Component\HttpFoundation\StreamedResponse)](https://symfony.com/doc/current/components/http_foundation.html#streaming-a-response) 
