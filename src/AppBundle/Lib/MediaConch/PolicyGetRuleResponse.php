<?php

namespace AppBundle\Lib\MediaConch;

class PolicyGetRuleResponse
{
    private $rule;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getRule()
    {
        return $this->rule;
    }

    protected function parse($response)
    {
        if (isset($response->rule)) {
            $this->rule = $response->rule;
        }
        else if (isset($response->nok)) {
            $this->error = $response->nok->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
