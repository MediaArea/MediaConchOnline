<?php

namespace AppBundle\Lib\MediaConch;

use Symfony\Component\Process\Process;

class MediaConchTrackTypeFields extends MediaConch
{
    public function __construct()
    {
    }

    public function run($trackType)
    {
        $process = new Process($this->MediaConch.' --MAXML_Fields='.$trackType);

        $process->run();

        if ($process->isSuccessful()) {
            $this->success = true;
            $this->output = trim($process->getOutput());
        }

        $this->output = trim($process->getOutput());

        return $this;
    }
}
