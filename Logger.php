<?php
class Logger
{
    public $data = '';

    public function log($data)
    {
        $this->data .= "\n" . $data;

        /*file_put_contents(
            'tkdz-viewer-log',
            "\n" . date('Y-m-d H:i:s') . ' ' . $data,
            FILE_APPEND
        );*/
    }
}