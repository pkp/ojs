<?php

    // A class used to stream large multipart files
    class StreamingClass {
        var $data;

        function stream_function($handle, $fd, $length) {
            return fread($this->data, $length);
        }
    }

?>