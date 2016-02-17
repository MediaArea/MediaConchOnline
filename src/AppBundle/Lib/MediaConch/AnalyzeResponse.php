<?php

namespace AppBundle\Lib\MediaConch;

class AnalyzeResponse
{
    private $success;
    private $transactionId;
    private $created;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getSuccess()
    {
        return $this->success;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function getError()
    {
        return $this->error;
    }

    private function parse($response)
    {
        if (is_array($response->ok) && isset($response->ok[0])) {
            $this->success = true;
            $this->transactionId = $response->ok[0]->outId;
            $this->created = $response->ok[0]->create;
        }
        else if (is_array($response->nok) && isset($response->nok[0])) {
            $this->success = false;
            $this->error = $response->nok[0]->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
