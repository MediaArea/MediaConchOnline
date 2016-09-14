<?php

namespace AppBundle\Lib\MediaConch;

class ReportResponse extends MediaConchServerAbstractResponse
{
    protected $report;
    protected $valid;

    public function getReport()
    {
        return $this->report;
    }

    public function getValid()
    {
        return $this->report;
    }

    protected function parse($response)
    {
        if (isset($response->ok->report)) {
            $this->report = $response->ok->report;
            $this->status = true;
            if (isset($response->ok->valid))
                $this->valid = $response->ok->valid;
        }
        else if (is_array($response->nok) && isset($response->nok[0])) {
            throw new MediaConchServerException($response->nok[0]->error);
        }
        else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
