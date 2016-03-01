<?php

namespace AppBundle\Lib\MediaConch;

class ValidateResponse
{
    private $valid;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getValid()
    {
        return $this->valid;
    }

    public function getError()
    {
        return $this->error;
    }

    private function parse($response)
    {
        if (is_array($response->ok) && isset($response->ok[0])) {
            $this->valid = $response->ok[0]->valid;
        }
        else if (is_array($response->nok) && isset($response->nok[0])) {
            $this->valid = false;
            $this->error = $response->nok[0]->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
