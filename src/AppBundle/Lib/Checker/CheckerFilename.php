<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerFilename extends CheckerBase
{
    public function fileFromId($id)
    {
        $this->response = $this->mc->fileFromId($this->user->getId(), $id);
    }

    public function getResponseAsArray()
    {
        return array('file' => $this->response->getFile());
    }

    public function getFilename($full = false)
    {
        if ($full) {
            return $this->response->getFile();
        }

        return pathinfo($this->response->getFile(), PATHINFO_BASENAME);
    }
}
