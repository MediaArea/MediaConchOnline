<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerFilename
{
    protected $response;

    public function __construct()
    {

    }

    public function fileFromId($id)
    {
        $mc = new MediaConchServer;
        $this->response = $mc->fileFromId($id);
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

    public function getFilename()
    {
        return pathinfo($this->response->getFile(), PATHINFO_BASENAME);
    }
}
