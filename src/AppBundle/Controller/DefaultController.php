<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Controller\BaseController;

/**
 * @Route("/")
 */
class DefaultController extends BaseController
{
    /**
     * @Route("/")
     * @Template()
     */
    public function homepageAction()
    {
        return array();
    }
}
