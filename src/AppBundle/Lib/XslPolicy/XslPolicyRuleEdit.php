<?php

namespace AppBundle\Lib\XslPolicy;

class XslPolicyRuleEdit extends XslPolicyBase
{
    public function edit($id, $policyId, $ruleData)
    {
        $data = array('id' => (int) $id,
            'name' => null == $ruleData['title'] ? '' : $ruleData['title'],
            'tracktype' => isset($ruleData['trackType']) ? $ruleData['trackType'] : '',
            'field' => $ruleData['field'],
            'occurrence' => (!isset($ruleData['occurrence']) || null == $ruleData['occurrence'] || '*' == $ruleData['occurrence']) ? -1 : (int) $ruleData['occurrence'],
            'ope' => $ruleData['validator'],
            'value' =>  null == $ruleData['value'] ? '': $ruleData['value'],
            'scope' => $ruleData['scope'],
            );

        $this->response = $this->mc->policyRuleEdit($this->user->getId(), $data, $policyId);
    }
}
