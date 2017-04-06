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
        } elseif (isset($response->nok)) {
            throw new MediaConchServerException($response->nok->error);
        } else {
            throw new MediaConchServerException('Unknown response');
        }
    }
}
