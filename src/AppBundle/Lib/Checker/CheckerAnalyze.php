<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerAnalyze
{
    protected $response;
    protected $source;

    public function __construct(MediaConchServer $mc)
    {
        $this->mc = $mc;
    }

    public function analyse($file)
    {
        $this->source = $file;
        $this->response = $this->mc->analyse($file);
    }

    public function getServerResponse()
    {
        return $this->response;
    }

    public function getResponseAsArray()
    {
        return array('success' => $this->response->getSuccess(),
            'transactionId' => $this->response->getTransactionId(),
            'error' => $this->response->getError(),
            'filename' => $this->getFilename(),
            );
    }

    public function getFilename()
    {
        return pathinfo($this->source, PATHINFO_BASENAME);
    }
}
