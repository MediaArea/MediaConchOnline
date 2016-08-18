<?php

namespace AppBundle\Lib\MediaConch;

class PolicyEditResponse
{
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    protected function parse($response)
    {
        if (isset($response->nok)) {
            $this->error = $response->nok->error;
        }
        else if (isset($response) && is_object($response)) {
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
