<?php

namespace AppBundle\Lib\XslPolicy;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use AppBundle\Lib\MediaConch\MediaConchServer;

class XslPolicyRuleEdit
{
    protected $response;
    protected $user;

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

    public function edit($id, $policyId, $ruleData)
    {
        $data = array('id' => (int) $id,
            'name' => null == $ruleData['title'] ? '' : $ruleData['title'],
            'tracktype' => $ruleData['trackType'],
            'field' => $ruleData['field'],
            'occurrence' => null == $ruleData['occurrence'] || '*' == $ruleData['occurrence'] ? -1 : (int) $ruleData['occurrence'],
            'ope' => $ruleData['validator'],
            'value' =>  null == $ruleData['value'] ? '': $ruleData['value'],
            );

        $this->response = $this->mc->policyRuleEdit($this->user->getId(), $data, $policyId);
    }
}
