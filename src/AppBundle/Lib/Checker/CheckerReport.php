<?php

namespace AppBundle\Lib\Checker;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerReport
{
    protected $response;
    protected $user;
    protected $report;
    protected $displayName;
    protected $policy;
    protected $fullPath = false;
    protected $filename;

    public function __construct(MediaConchServer $mc, TokenStorageInterface $tokenStorage)
    {
        $this->mc = $mc;

        $token = $tokenStorage->getToken();
        if ($token !== null && $token->getUser() instanceof \AppBundle\Entity\User) {
            $this->user = $token->getUser();
        }
        else {
            throw new \Exception('Invalid User');
        }
    }

    public function report($id, $report, $displayName, $display = null, $policy = null, $verbosity = -1)
    {
        $this->report = $report;
        if ($display && file_exists($display) && is_readable($display)) {
            $this->displayName = null;
        }
        else {
            $this->displayName = $displayName;
            $display = null;
        }

        // Force XML report for VERAPDF and DPFMANAGER
        if (in_array($this->report, array(5, 6))) {
            $this->displayName = 'xml';
            $display = null;
        }

        $this->response = $this->mc->report($this->user->getId(), $id, $this->getReportType(), $this->getDisplayName(), $display, $policy, $verbosity);
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
        else {
            return str_replace($this->filename, pathinfo($this->filename, PATHINFO_BASENAME), $this->response->getReport());
        }
    }

    public function setFullPath($fullPath, $filename = null)
    {
        if (!$fullPath && $filename !== null) {
            $this->fullPath = false;
            $this->filename = $filename;
        }
        else {
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
                return 'MediaInfoReport';
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
        return preg_match('/<!doctype/i', $this->response->getReport());
    }

    protected function isXmlReport()
    {
        return preg_match('/<?xml/i', $this->response->getReport());
    }

    protected function guessReportFormatType()
    {
        if (null !== $this->displayName) {
            return $this->displayName;
        }

        if ($this->isHtmlReport()) {
            return 'html';
        }
        else if ($this->isXmlReport()) {
            return 'xml';
        }
        else {
            return null;
        }

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
