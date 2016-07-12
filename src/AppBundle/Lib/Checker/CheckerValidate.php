<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerValidate
{
    protected $response;
    protected $fileId;

    public function __construct(MediaConchServer $mc)
    {
        $this->mc = $mc;
    }

    public function validate($id, $report, $policy = null)
    {
        $this->fileId = $id;
        $this->response = $this->mc->validate($id, $this->getReportType($report), $policy);
    }

    public function getResponseAsArray()
    {
        return array('valid' => $this->response->getValid(),
            'fileId' => $this->fileId,
            'error' => $this->response->getError(),
            );
    }

    protected function getReportType($report)
    {
        switch ($report) {
            case '5':
                return 'VERAPDF';
                break;
            case '6':
                return 'DPFMANAGER';
                break;
            case '1':
                return 'POLICY';
                break;
            case '2':
            default:
                return 'IMPLEMENTATION';
        }
    }
}
