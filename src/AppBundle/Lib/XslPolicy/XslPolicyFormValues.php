<?php

namespace AppBundle\Lib\XslPolicy;

use AppBundle\Lib\MediaConch\MediaConchServer;

class XslPolicyFormValues
{
    protected $response;

    public function __construct(MediaConchServer $mc)
    {
        $this->mc = $mc;
    }

    public function getValues($trackType, $field, $value = null)
    {
        $this->value = $value;
        $this->response = $this->mc->valuesFromType($trackType, $field);
    }

    public function getServerResponse()
    {
        return $this->response;
    }

    public function getResponseAsArray()
    {
        return array('values' => $this->getDefaultValues(),
            'error' => $this->response->getError(),
            );
    }

    protected function getDefaultValues() {
        if (null !== $this->value) {
            $values = $this->response->getValues();
            if (is_array($values) && 1 <= count($values)) {
                if (!in_array($this->value, $values)) {
                    $values[] = $this->value;
                }
            }
            else {
                $values = array($this->value);
            }

            return $values;
        }
        else {
            return $this->response->getValues();
        }
    }
}
