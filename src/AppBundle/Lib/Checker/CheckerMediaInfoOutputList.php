<?php

namespace AppBundle\Lib\Checker;

class CheckerMediaInfoOutputList extends CheckerBase
{
    public function getList()
    {
        $this->response = $this->mc->mediaInfoOutputList();
    }

    public function getResponseAsArray()
    {
        return array('list' => $this->response->getList());
    }
}
