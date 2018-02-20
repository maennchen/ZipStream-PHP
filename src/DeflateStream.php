<?php

namespace ZipStream;

class DeflateStream extends Stream
{
    protected $filter;
    protected $options;

    public function addDeflateFilter($options=null) {
        $this->options = $options;
        $this->filter = stream_filter_append(
            $this->stream,
            'zlib.deflate',
            STREAM_FILTER_READ,
            $this->options
        );
    }

    public function removeDeflateFilter() {
        if (!$this->filter) return;
        stream_filter_remove($this->filter);
        $this->filter = null;
    }

    public function rewind() {
        // deflate filter needs to be removed before rewind
        if ($this->filter) {
            $this->removeDeflateFilter();
            $this->seek(0);
            $this->addDeflateFilter($this->options);
        } else {
            rewind($this->stream);
        }
    }
}
