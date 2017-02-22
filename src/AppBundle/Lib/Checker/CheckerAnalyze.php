<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerAnalyze extends CheckerBase
{
    protected $files;
    protected $fullPath = false;

    public function analyse(array $files, $force = false)
    {
        $this->files = $files;
        $this->response = $this->mc->analyse($this->user->getId(), $files, $force);
    }

    public function getResponseAsArray()
    {
        $response = array();
        $analyzedFiles = $this->response->getAnalyze();
        foreach ($analyzedFiles as $key => $file) {
            $response[] = array('success' => $file['status'],
                'transactionId' => $file['transactionId'],
                'filename' => $this->getFilename($this->files[$key]),
                );
        }

        return $response;
    }

    public function setFullPath($fullPath)
    {
        $this->fullPath = $fullPath;
    }

    protected function getFilename($file)
    {
        if ($this->fullPath) {
            return $file;
        }

        return pathinfo($file, PATHINFO_BASENAME);
    }
}
