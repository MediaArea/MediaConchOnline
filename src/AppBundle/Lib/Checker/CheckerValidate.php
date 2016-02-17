<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerValidate
{
    protected $response;

    public function __construct()
    {

    }

    public function validate($id, $report, $policy = null)
    {
        $mc = new MediaConchServer;
        $this->response = $mc->validate($id, $report, $policy);
    }

    public function getResponseAsArray()
    {
        return array('valid' => $this->response->getValid(),
            'error' => $this->response->getError(),
            );
    }
}
