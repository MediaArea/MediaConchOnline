<?php

namespace AppBundle\Lib\MediaConch;

class PolicyExportResponse
{
    private $xml;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getxml()
    {
        return $this->xml;
    }

    protected function parse($response)
    {
        if (isset($response->xml)) {
            $this->xml = $response->xml;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
