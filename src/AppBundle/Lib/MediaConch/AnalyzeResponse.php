<?php

namespace AppBundle\Lib\MediaConch;

class AnalyzeResponse extends MediaConchServerAbstractResponse
{
    protected $transactionId;
    protected $created;

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    protected function parse($response)
    {
        if (is_array($response->ok) && isset($response->ok[0])) {
            $this->status = true;
            $this->transactionId = $response->ok[0]->outId;
            $this->created = $response->ok[0]->create;
        }
        else if (is_array($response->nok) && isset($response->nok[0])) {
            throw new MediaConchServerException($response->nok[0]->error);
        }
        else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
