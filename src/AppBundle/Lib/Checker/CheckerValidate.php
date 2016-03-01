<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerValidate
{
    protected $response;

    public function __construct(MediaConchServer $mc)
    {
        $this->mc = $mc;
    }

    public function validate($id, $report, $policy = null)
    {
        $this->response = $this->mc->validate($id, $report, $policy);
    }

    public function getResponseAsArray()
    {
        return array('valid' => $this->response->getValid(),
            'error' => $this->response->getError(),
            );
    }
}
