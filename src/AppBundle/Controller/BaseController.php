<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class BaseController extends Controller
{
    protected function addFlashBag($type, $message) {
        $this->addFlash(
            $type,
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $message
            );

        return $this;
    }

    /**
     * Make the content-disposition string to download a file
     * Handle unauthorized and non ASCII characters
     * @param Response response the response object
     * @param string filename the name of the file to download
     *
     * @return string
     */
    protected function downloadFileDisposition($response, $filename) {
        // Store current locale and set locale temporary to en_US
        $locale = setlocale(LC_ALL, 0);
        setlocale(LC_ALL, 'en_US.UTF8');

        // $filename can not contain '/' and '\\'
        $filename = str_replace(array('/', '\\'), '-', $filename);

        // $filenameFallback should be ASCII only and can not contain '%', '/' and '\\' (already stripped in $filename)
        if (function_exists('iconv')) {
            $filenameFallback = iconv('UTF-8', 'ASCII//TRANSLIT', $filename);
        }
        else {
            $filenameFallback = preg_replace('/[^\x20-\x7E]/', '-', $filename);
        }
        $filenameFallback = str_replace('%', '-', $filenameFallback);

        // Restore locale
        setlocale(LC_ALL, $locale);

        return $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            $filenameFallback
        );
    }
}
