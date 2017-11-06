<?php

namespace AppBundle\Lib\Checker;

class CheckerReport extends CheckerBase
{
    protected $report;
    protected $displayName;
    protected $fullPath = false;
    protected $filename;
    protected $miFormat;

    public function report($id, $report, $displayName, $display = null, $policy = null, $verbosity = -1, $miFormat = '')
    {
        $this->report = $report;
        if ($display && file_exists($display) && is_readable($display)) {
            $this->displayName = null;
        } else {
            $this->displayName = $displayName;
            $display = null;
        }
        $this->miFormat = $miFormat;

        $this->response = $this->mc->report($this->user->getId(), $id, $this->getReportType(), $this->getDisplayName(), $display, $policy, $verbosity, $miFormat);
    }

    public function getResponseAsArray()
    {
        return array('report' => $this->getReport(),
            'isHtmlReport' => $this->isHtmlReport(),
            'error' => $this->response->getError(),
            );
    }

    public function getReport()
    {
        if ($this->fullPath) {
            return $this->response->getReport();
        }

        return str_replace($this->filename, pathinfo($this->filename, PATHINFO_BASENAME), $this->response->getReport());
    }

    public function setFullPath($fullPath, $filename = null)
    {
        if (!$fullPath && null !== $filename) {
            $this->fullPath = false;
            $this->filename = $filename;
        } else {
            $this->fullPath = true;
        }
    }

    public function getDownloadReportName()
    {
        switch ($this->report) {
            case '2':
                return 'ImplementationReport';
                break;
            case 'policy':
                return 'MediaConchReport';
                break;
            case 'mi':
                $name = 'MediaInfo';
                if ($this->miFormat && !in_array($this->miFormat, ['XML', 'MIXML', 'Text', 'HTML'])) {
                    $name .= '.'.$this->miFormat;
                }

                return $name;
                break;
            case 'mt':
                return 'MediaTraceReport';
                break;
            default:
                return 'Report';
        }
    }

    public function getDownloadReportExtension()
    {
        switch ($this->guessReportFormatType()) {
            case 'xml':
            case 'ma':
                return 'xml';
                break;
            case 'json':
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
        switch ($this->guessReportFormatType()) {
            case 'xml':
            case 'ma':
                return 'text/xml';
                break;
            case 'json':
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

    protected function isHtmlReport()
    {
        return preg_match('/^<!doctype|^<html>.*<\/html>$/is', $this->response->getReport());
    }

    protected function isXmlReport()
    {
        return preg_match('/<\?xml/i', $this->response->getReport());
    }

    protected function isJsonReport()
    {
        return preg_match('/^{.*}$/is', $this->response->getReport());
    }

    protected function guessReportFormatType()
    {
        if (null !== $this->displayName) {
            return $this->displayName;
        }

        if ($this->isHtmlReport()) {
            return 'html';
        } elseif ($this->isXmlReport()) {
            return 'xml';
        } elseif ($this->isJsonReport()) {
            return 'json';
        }

        return null;
    }

    protected function getReportType()
    {
        switch ($this->report) {
            case '2':
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
            case '5':
                return 'VERAPDF';
                break;
            case '6':
                return 'DPFMANAGER';
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
