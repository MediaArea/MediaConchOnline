<?php

namespace AppBundle\Lib\MediaConch;

class MediaInfoOutputListResponse extends MediaConchServerAbstractResponse
{
    protected $list = array();

    public function getList()
    {
        return $this->list;
    }

    protected function parse($response)
    {
        $this->list = json_decode($response->outputs);
    }
}
