<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetRuleResponse extends MediaConchServerAbstractResponse
{
    protected $rule;

    public function getRule()
    {
        return $this->rule;
    }

    protected function parse($response)
    {
        if (isset($response->rule)) {
            $this->rule = $response->rule;
            $this->status = true;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
