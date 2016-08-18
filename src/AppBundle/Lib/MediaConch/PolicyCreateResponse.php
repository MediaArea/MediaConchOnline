<?php

namespace AppBundle\Lib\MediaConch;

class PolicyCreateResponse
{
    private $id;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getId()
    {
        return $this->id;
    }

    protected function parse($response)
    {
        if (isset($response->id)) {
            $this->id = $response->id;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
