<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerStatus
{
    protected $response;

    public function __construct()
    {

    }

    public function getStatus($id)
    {
        $mc = new MediaConchServer;
        $this->response = $mc->status($id);
    }

    public function getResponseAsArray()
    {
        return array('finish' => $this->response->getFinish(),
            'percent' => $this->response->getPercent(),
            'error' => $this->response->getError(),
            );
    }
}
