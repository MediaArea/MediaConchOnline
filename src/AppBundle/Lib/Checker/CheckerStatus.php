<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerStatus extends CheckerBase
{
    public function getStatus($id)
    {
        $this->response = $this->mc->status($this->user->getId(), $id);
    }
}
