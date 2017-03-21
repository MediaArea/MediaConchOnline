<?php

namespace AppBundle\Lib\Checker;

use AppBundle\Lib\MediaConch\MediaConchServer;

class CheckerStatus extends CheckerBase
{
    public function getStatus($id)
    {
        $this->response = $this->mc->status($this->user->getId(), $id);
    }

    public function getResponse()
    {
        $response = $this->response->getResponse();
        foreach ($response as $key => $file) {
            if (true === $file['finish'] && isset($file['associatedFiles'])) {
                $associatedFiles = array();
                foreach ($file['associatedFiles'] as $associatedFileId) {
                    $filename = $this->mc->fileFromId($this->user->getId(), $associatedFileId);
                    $associatedFiles[$associatedFileId] = pathinfo($filename->getFile(), PATHINFO_BASENAME);
                }
                $response[$key]['associatedFiles'] = $associatedFiles;
            }
        }

        return $response;
    }
}
