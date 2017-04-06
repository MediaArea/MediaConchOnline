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
        } elseif (isset($response->nok)) {
            throw new MediaConchServerException($response->nok->error);
        } else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
