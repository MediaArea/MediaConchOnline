<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerFilename
{
    protected $response;

    public function __construct(MediaConchServer $mc)
    {
        $this->mc = $mc;
    }

    public function fileFromId($id)
    {
        $this->response = $this->mc->fileFromId($id);
    }

    public function getServerResponse()
    {
        return $this->response;
    }

    public function getResponseAsArray()
    {
        return array('file' => $this->response->getFile(),
            );
    }

    public function getFilename($full = false)
    {
        if ($full) {
            return $this->response->getFile();
        }
        else {
            return pathinfo($this->response->getFile(), PATHINFO_BASENAME);
        }
    }
}
