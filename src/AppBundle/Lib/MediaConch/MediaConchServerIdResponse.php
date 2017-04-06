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
        } elseif (isset($response->nok)) {
            throw new MediaConchServerException($response->nok->error);
        } else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
