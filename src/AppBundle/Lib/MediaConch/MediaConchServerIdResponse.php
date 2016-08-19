<?php

namespace AppBundle\Lib\MediaConch;

class MediaConchServerIdResponse extends MediaConchServerAbstractResponse
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    protected function parse($response)
    {
        if (isset($response->id)) {
            $this->id = $response->id;
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
