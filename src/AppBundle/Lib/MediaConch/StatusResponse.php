<?php

namespace AppBundle\Lib\MediaConch;

class StatusResponse extends MediaConchServerAbstractResponse
{
    protected $response = array();

    public function getResponse()
    {
        return $this->response;
    }

    protected function parse($response)
    {
        if (is_array($response->ok)) {
            foreach ($response->ok as $ok) {
                $this->response[$ok->id] = array('finish' => $ok->finished);
                if ($this->response[$ok->id]['finish']) {
                    if (isset($ok->tool)) {
                        $this->response[$ok->id]['tool'] = $ok->tool;
                    }
                    else {
                        $this->response[$ok->id]['tool'] = 2;
                    }
                }
                else {
                    $this->response[$ok->id]['percent'] = $ok->done;
                }
            }
        }
        else if (is_array($response->nok)) {
            foreach ($response->nok as $nok) {
                $this->response[$nok->id] = array('error' => $nok->error);
            }
        }
        else {
            throw new \Exception('Unknown response');
        }
    }
}
