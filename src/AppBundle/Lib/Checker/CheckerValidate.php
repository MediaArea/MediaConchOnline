<?php

namespace AppBundle\Lib\Checker;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerValidate
{
    protected $response;
    protected $user;
    protected $fileId;

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

    public function validate($id, $report, $policy = null)
    {
        $this->fileId = $id;
        $this->response = $this->mc->validate($this->user->getId(), $id, $this->getReportType($report), $policy);
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
