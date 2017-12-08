<?php

namespace AppBundle\Lib\MediaConch;

use Symfony\Component\Process\Process;

class MediaConchTrackTypes extends MediaConch
{
    public function __construct()
    {
    }

    public function run()
    {
        $process = new Process($this->MediaConch.' --MAXML_StreamKinds');

        $process->run();

        if ($process->isSuccessful()) {
            $this->success = true;
            $this->output = trim($process->getOutput());
        }

        $this->output = trim($process->getOutput());

        return $this;
    }
}
