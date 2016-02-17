<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerReport
{
    protected $response;
    protected $report;
    protected $displayName;
    protected $policy;

    public function __construct()
    {

    }

    public function report($id, $report, $displayName, $display = null, $policy = null)
    {
        $this->report = $report;
        if ($display && file_exists($display) && is_readable($display)) {
            $this->displayName = null;
        }
        else {
            $this->displayName = $displayName;
        }

        $mc = new MediaConchServer;
        $this->response = $mc->report($id, $this->getReportType(), $this->getDisplayName(), $display, $policy);
    }

    public function getServerResponse()
    {
        return $this->response;
    }

    public function getResponseAsArray()
    {
        return array('report' => $this->response->getReport(),
            'error' => $this->response->getError(),
            );
    }

    public function getDownloadReportName()
    {
        switch ($this->report) {
            case 'implem':
                return 'ImplementationReport';
                break;
            case 'policy':
                return 'MediaConchReport';
                break;
            case 'mi':
                return 'MediaInfo';
                break;
            case 'mt':
                return 'MediaTrace';
                break;
            default:
                return 'Report';
        }
    }

    public function getDownloadReportExtension()
    {
        switch ($this->displayName) {
            case 'xml':
            case 'ma':
                return 'xml';
                break;
            case 'jstree':
                return 'json';
                break;
            case 'html':
                return 'html';
                break;
            case 'txt':
            default:
                return 'txt';
        }
    }

    public function getDownloadReportMimeType()
    {
        switch ($this->displayName) {
            case 'xml':
            case 'ma':
                return 'text/xml';
                break;
            case 'jstree':
                return 'application/json';
                break;
            case 'html':
                return 'text/html';
                break;
            case 'txt':
            default:
                return 'text/plain';
        }
    }

    protected function getReportType()
    {
        switch ($this->report) {
            case 'implem':
                return 'IMPLEMENTATION';
                break;
            case 'policy':
                return 'POLICY';
                break;
            case 'mi':
                return 'MEDIAINFO';
                break;
            case 'mt':
                return 'MEDIATRACE';
                break;
            default:
                return 'NO_REPORT';
        }
    }

    protected function getDisplayName()
    {
        if (null === $this->displayName) {
            return null;
        }

        switch ($this->displayName) {
            case 'txt':
                return 'TEXT';
                break;
            case 'xml':
                return 'XML';
                break;
            case 'ma':
                return 'MAXML';
                break;
            case 'jstree':
                return 'JSTREE';
                break;
            case 'html':
            default:
                return 'HTML';
        }
    }
}
