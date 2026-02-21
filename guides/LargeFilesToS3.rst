Compressing large files to S3 compatible storages
=============

S3 compatible storages usually have a limitation of 5 GiB when using single upload. When generating larger zips, the
solution is to use a `multi-part upload`_.

.. _multi-part upload: <https://github.com/awsdocs/aws-doc-sdk-examples/blob/75a8bf8536d436db91ec5de1ba0ed80fd258e904/php/example_code/s3/s3-multipart-upload-using-lowlevel-php-sdk-api.php>`

We can implement a `PSR-7 stream <https://www.php-fig.org/psr/psr-7/>`_ that buffers ZipStream's output and uploads
to S3 in chunks.

MultipartUploadBufferStream example
---------------

.. code-block:: php
    <?php

    declare(strict_types=1);

    use Aws\S3\S3ClientInterface;
    use Psr\Http\Message\StreamInterface;

    use function strlen;
    use function substr;

    final class MultipartUploadBufferStream implements StreamInterface
    {
        private const int PART_SIZE = 5242880; // 5 MiB in bytes

        private string $buffer = '';
        private int $bufferSize = 0;
        private int $partNumber = 1;
        private array $parts = [];

        private string $uploadId;

        public function __construct(
            private readonly string $destinationFileName,
            private readonly string $bucket,
            private readonly S3ClientInterface $client
        ) {
            $result = $this->client->createMultipartUpload(
                [
                    'Bucket'       => $bucket,
                    'Key'          => $destinationFileName,
                    'StorageClass' => 'REDUCED_REDUNDANCY',
                ]
            );

            $this->uploadId = $result['UploadId'];

            $this->parts['Parts'] = [];
        }

        public function write($string): int
        {
            $chunkSize           = strlen($string);

            $this->buffer     .= $string;
            $this->bufferSize += $chunkSize;

            if ($this->bufferSize >= self::PART_SIZE) {
                $this->uploadPart();
            }

            return $chunkSize;
        }

        public function close(): void
        {
            // Upload remaining closing bytes from zip
            $this->uploadPart();

            $this->client->completeMultipartUpload([
                'Bucket'          => $this->bucket,
                'Key'             => $this->destinationFileName,
                'UploadId'        => $this->uploadId,
                'MultipartUpload' => $this->parts,
            ]);

            $this->buffer = '';
            $this->bufferSize = 0;
        }

        private function uploadPart(): void
        {
            $result = $this->client->uploadPart([
                'Bucket'     => $this->bucket,
                'Key'        => $this->destinationFileName,
                'UploadId'   => $this->uploadId,
                'PartNumber' => $this->partNumber,
                'Body'       => $this->buffer,
            ]);

            $this->buffer = '';
            $this->bufferSize = 0;

            $this->parts['Parts'][$this->partNumber] = [
                'PartNumber' => $this->partNumber,
                'ETag' => $result['ETag'],
            ];

            $this->partNumber++;

            $result = null;
            gc_collect_cycles(); // To avoid memory leaks. @see github.com/aws/aws-sdk-php/issues/1273
        }

        public function __toString(): string
        {
            return $this->getContents();
        }

        public function getContents(): string
        {
            $buffer = $this->buffer;
            $this->buffer = '';
            $this->bufferSize = 0;

            return $buffer;
        }

        public function detach(): null
        {
            $this->close();

            return null;
        }

        public function getSize(): int
        {
            return $this->bufferSize;
        }

        public function isReadable(): bool
        {
            return true;
        }

        public function isWritable(): bool
        {
            return true;
        }

        public function isSeekable(): bool
        {
            return false;
        }

        public function rewind(): void
        {
            $this->seek(0);
        }

        public function seek($offset, $whence = SEEK_SET): void
        {
            throw new \RuntimeException('Cannot seek a BufferStream');
        }

        public function eof(): bool
        {
            return $this->bufferSize === 0;
        }

        public function tell(): int
        {
            throw new \RuntimeException('Cannot determine the position of a BufferStream');
        }

        public function read($length): string
        {
            $currentLength = $this->bufferSize;

            if ($length >= $currentLength) {
                // No need to slice the buffer because we don't have enough data.
                $result = $this->buffer;
                $this->buffer = '';
                $this->bufferSize = 0;
            } else {
                // Slice up the result to provide a subset of the buffer.
                $result = substr($this->buffer, 0, $length);
                $this->buffer = substr($this->buffer, $length);
                $this->bufferSize -= $length;
            }

            return $result;
        }

        public function getMetadata($key = null)
        {
            return $key ? null : [];
        }
    }

Then, we can use it with ZipStream to compress the files and upload the parts to the storage.

ZipStream usage
---------------

.. code-block:: php
    <?php

    declare(strict_types=1);

    use Aws\S3\S3Client;
    use Aws\Credentials\CredentialProvider;
    use MultipartUploadBufferStream;
    use ZipStream\ZipStream;

    $bucket = 'your bucket name';
    $client = new S3Client([
        'region' => 'your region',
        'version' => 'latest',
        'bucketName' => $bucket,
        'credentials' => CredentialProvider::defaultProvider(),
    ]);

    $bufferStream = new MultipartUploadBufferStream(
        'destination-file.zip',
        $bucket,
        $client,
    );

    $zip = new ZipStream(
        outputStream: $destination,
        defaultCompressionMethod: CompressionMethod::STORE,
        defaultEnableZeroHeader: true,
        sendHttpHeaders: false,
    );

    $zip->addFile(
        fileName: 'big-file-1.txt',
        data: 'File1 data',
    );
    $zip->addFile(
        fileName: 'big-file-2.txt',
        data: 'File2 data',
    );

    $zip->finish();
    $destination->close(); // Needed after $zip->finish() to upload the last remaining bytes to S3

You can read more about the logic behind this implementation in the `discussion`_.

.. _discussion: https://github.com/maennchen/ZipStream-PHP/discussions/402
