<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseController extends Controller
{
    protected function addFlashBag($type, $message) {
        $this->get('session')->getFlashBag()->add(
            $type,
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . $message
            );

        return $this;
    }
}
