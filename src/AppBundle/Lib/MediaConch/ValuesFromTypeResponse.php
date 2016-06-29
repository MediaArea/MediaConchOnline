<?php

namespace AppBundle\Lib\MediaConch;

class ValuesFromTypeResponse
{
    private $values;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getError()
    {
        return $this->error;
    }

    private function parse($response)
    {
        if (isset($response->values)) {
            $this->values = $response->values;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
