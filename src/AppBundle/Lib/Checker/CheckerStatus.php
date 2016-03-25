<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerStatus
{
    protected $response;

    public function __construct(MediaConchServer $mc)
    {
        $this->mc = $mc;
    }

    public function getStatus($id)
    {
        $this->response = $this->mc->status($id);
    }

    public function getResponseAsArray()
    {
        return array('finish' => $this->response->getFinish(),
            'percent' => $this->response->getPercent(),
            'tool' => $this->response->getTool(),
            'error' => $this->response->getError(),
            );
    }
}
