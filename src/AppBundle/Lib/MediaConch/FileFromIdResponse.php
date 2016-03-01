<?php

namespace AppBundle\Lib\MediaConch;

class FileFromIdResponse
{
    private $file;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getFile()
    {
        return $this->file;
    }

    private function parse($response)
    {
        if (isset($response->file)) {
            $this->file = $response->file;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
