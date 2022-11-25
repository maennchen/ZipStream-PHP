Adding Content-Length header
=============

Adding a ``Content-Length`` header for ``ZipStream`` is not trivial since the
size is not known beforehand.

The following workaround adds an approximated header:

.. code-block:: php

    use ZipStream\CompressionMethod;
    use ZipStream\ZipStream;

    class Zip
        {
        private $files = [];

        public function __construct(
            private readonly string $name
        ) { }

        public function addFile(
            string $name,
            string $data,
        ): void {
            $this->files[] = ['type' => 'addFile', 'name' => $name, 'data' => $data];
        }

        public function addFileFromPath(
            string $name,
            string $path,
        ): void {
            $this->files[] = ['type' => 'addFileFromPath', 'name' => $name, 'path' => $path];
        }

        public function getEstimate(): int {
            $estimate = 22;
            foreach ($this->files as $file) {
            $estimate += 76 + 2 * strlen($file['name']);
            if ($file['type'] === 'addFile') {
                $estimate += strlen($file['data']);
            }
            if ($file['type'] === 'addFileFromPath') {
                $estimate += filesize($file['path']);
            }
            }
            return $estimate;
        }

        public function finish()
        {
            header('Content-Length: ' . $this->getEstimate());
            $zip = new ZipStream(
                outputName: $this->name,
                SendHttpHeaders: true,
                enableZip64: false,
                defaultCompressionMethod: CompressionMethod::STORE,
            );

            foreach ($this->files as $file) {
                if ($file['type'] === 'addFile') {
                    $zip->addFile(
                        fileName: $file['name'],
                        data: $file['data'],
                    );
                }
                if ($file['type'] === 'addFileFromPath') {
                    $zip->addFileFromPath(
                        fileName: $file['name'],
                        path: $file['path'],
                    );
                }
            }
            $zip->finish();
        }
    }

It only works with the following constraints:

- All file content is known beforehand.
- Content Deflation is disabled

Thanks to
`partiellkorrekt <https://github.com/maennchen/ZipStream-PHP/issues/89#issuecomment-1047949274>`_
for this workaround.
