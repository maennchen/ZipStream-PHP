Stream Output
===============

Stream to S3 Bucket
---------------

.. code-block:: php

    use Aws\S3\S3Client;
    use Aws\Credentials\CredentialProvider;
    use ZipStream\ZipStream;

    $bucket = 'your bucket name';
    $client = new S3Client([
        'region' => 'your region',
        'version' => 'latest',
        'bucketName' => $bucket,
        'credentials' => CredentialProvider::defaultProvider(),
    ]);
    $client->registerStreamWrapper();

    $zipFile = fopen("s3://$bucket/example.zip", 'w');

    $zip = new ZipStream(
        enableZip64: false,
        outputStream: $zipFile,
    );

    $zip->addFile(
        fileName: 'file1.txt',
        data: 'File1 data',
    );
    $zip->addFile(
        fileName: 'file2.txt',
        data: 'File2 data',
    );
    $zip->finish();

    fclose($zipFile);

Stream to Callback Function
---------------------------

The CallbackStreamWrapper allows you to stream ZIP data to a custom callback function,
enabling flexible output handling such as streaming to multiple destinations,
progress tracking, or data transformation.

.. code-block:: php

    use ZipStream\ZipStream;
    use ZipStream\Stream\CallbackStreamWrapper;

    // Example 1: Stream to multiple destinations with proper file handling
    $backupFile = fopen('backup.zip', 'wb');
    $logFile = fopen('transfer.log', 'ab');
    
    $zip = new ZipStream(
        outputStream: CallbackStreamWrapper::open(function (string $data) use ($backupFile, $logFile) {
            // Send to browser
            echo $data;
            
            // Save to file efficiently
            fwrite($backupFile, $data);
            
            // Log transfer progress
            fwrite($logFile, "Transferred " . strlen($data) . " bytes\n");
        }),
        sendHttpHeaders: false,
    );

    $zip->addFile('hello.txt', 'Hello World!');
    $zip->finish();
    
    // Clean up resources
    fclose($backupFile);
    fclose($logFile);

.. code-block:: php

    // Example 2: Progress tracking
    $totalBytes = 0;
    $zip = new ZipStream(
        outputStream: CallbackStreamWrapper::open(function (string $data) use (&$totalBytes) {
            $totalBytes += strlen($data);
            reportProgress($totalBytes); // Report progress to your tracking system
            
            // Your actual output handling
            echo $data;
        }),
        sendHttpHeaders: false,
    );

    $zip->addFile('large_file.txt', str_repeat('A', 10000));
    $zip->finish();

.. code-block:: php

    // Example 3: Data transformation using PHP stream filters
    // For data transformations, prefer PHP's built-in stream filters
    $outputStream = fopen('php://output', 'w');
    stream_filter_append($outputStream, 'convert.base64-encode');
    
    $zip = new ZipStream(
        outputStream: $outputStream,
        sendHttpHeaders: false,
    );

    $zip->addFile('secret.txt', 'Confidential data');
    $zip->finish();
    fclose($outputStream);

.. note::
   For data transformations, PHP's built-in stream filters are preferred over callback transformations. Stream filters operate at the stream level and maintain data integrity. You can register custom filters using ``stream_filter_register()`` for specialized transformations.
