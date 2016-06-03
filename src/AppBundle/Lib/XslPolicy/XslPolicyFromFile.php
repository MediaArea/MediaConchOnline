<?php

namespace AppBundle\Lib\XslPolicy;

use AppBundle\Lib\MediaConch\MediaConchServer;

class XslPolicyFromFile
{
    protected $response;

    public function __construct(MediaConchServer $mc)
    {
        $this->mc = $mc;
    }

    public function getPolicy($id)
    {
        $this->response = $this->mc->policyFromFile($id);
    }

    public function getPolicyContent()
    {
        return $this->response->getPolicy();
    }

    public function getResponseAsArray()
    {
        return array('policy' => $this->response->getPolicy(),
            'error' => $this->response->getError(),
            );
    }
}
