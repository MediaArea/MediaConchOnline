<?php

namespace AppBundle\Lib\MediaConch;

class StatusResponse
{
    private $finish = false;
    private $percent = 0;
    private $tool = 2;
    private $error;

    public function __construct($response)
    {
        $this->parse($response);
    }

    public function getFinish()
    {
        return $this->finish;
    }

    public function getPercent()
    {
        return $this->percent;
    }

    public function getTool()
    {
        return $this->tool;
    }

    public function getError()
    {
        return $this->error;
    }

    private function parse($response)
    {
        if (is_array($response->ok) && isset($response->ok[0])) {
            $this->finish = $response->ok[0]->finished;
            if ($this->finish == false) {
                $this->percent = $response->ok[0]->done;
            }
            else {
                if (isset($response->ok[0]->tool)) {
                    $this->tool = $response->ok[0]->tool;
                }
            }
        }
        else if (is_array($response->nok) && isset($response->nok[0])) {
            $this->error = $response->nok[0]->error;
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
