<?php

namespace AppBundle\Lib\MediaConch;

class AnalyzeResponse extends MediaConchServerAbstractResponse
{
    protected $analyze = array();

    public function getAnalyze()
    {
        return $this->analyze;
    }

    protected function parse($response)
    {
        if (is_array($response->ok) && 0 < count($response->ok)) {
            foreach ($response->ok as $analyze) {
                $this->analyze[$analyze->inId] = array('status' => true, 'transactionId' => $analyze->outId);
            }
        }

        if (is_array($response->nok) && 0 < count($response->nok)) {
            foreach ($response->nok as $analyze) {
                $this->analyze[$analyze->inId] = array('status' => false, 'transactionId' => $analyze->outId);
            }
        }
    }
}
