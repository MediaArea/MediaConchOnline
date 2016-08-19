<?php

namespace AppBundle\Lib\MediaConch;

class PolicyExportResponse extends MediaConchServerAbstractResponse
{
    protected $xml;

    public function getxml()
    {
        return $this->xml;
    }

    protected function parse($response)
    {
        if (isset($response->xml)) {
            $this->xml = $response->xml;
            $this->status = true;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
