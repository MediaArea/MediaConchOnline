<?php

namespace AppBundle\Lib\MediaConch;

class ReportResponse
{
    private $report;
    private $valid;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getReport()
    {
        return $this->report;
    }

    public function getValid()
    {
        return $this->report;
    }

    public function getError()
    {
        return $this->error;
    }

    private function parse($response)
    {
        if (isset($response->ok->report)) {
            $this->report = $response->ok->report;
            if (isset($response->ok->valid))
                $this->valid = $response->ok->valid;
        }
        else if (is_array($response->nok) && isset($response->nok[0])) {
            $this->error = $response->nok[0]->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
