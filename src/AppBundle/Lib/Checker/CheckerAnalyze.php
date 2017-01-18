<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerAnalyze extends CheckerBase
{
    protected $source;
    protected $fullPath = false;

    public function analyse($file, $force = false)
    {
        $this->source = $file;
        $this->response = $this->mc->analyse($this->user->getId(), $file, $force);
    }

    public function getResponseAsArray()
    {
        if ($this->response->getStatus()) {
            return array('success' => true,
                'transactionId' => $this->response->getTransactionId(),
                'error' => null,
                'filename' => $this->getFilename(),
                );
        }
        else {
            return array('success' => false,
                'transactionId' => null,
                'error' => $this->response->getError(),
                'filename' => $this->getFilename(),
                );
        }
    }

    public function setFullPath($fullPath)
    {
        $this->fullPath = $fullPath;
    }

    protected function getFilename()
    {
        if ($this->fullPath) {
            return $this->source;
        }
        else {
            return pathinfo($this->source, PATHINFO_BASENAME);
        }
    }
}
