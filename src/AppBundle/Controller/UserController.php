<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Route("/")
 */
class UserController extends Controller
{

    /**
     * @Route("/settings")
     * @Template()
     */
    public function settingsAction(Request $request)
    {
        $form = $this->createForm('settings');

        $form->handleRequest($request);
        if ($form->isValid()) {
            $data = $form->getData();
            $settings = $this->get('mco.settings');
            $settings->setDefaultPolicy($data['policy']);
            $settings->setDefaultDisplay($data['display']);
            $settings->setDefaultVerbosity($data['verbosity']);

            $this->get('session')->getFlashBag()->add(
                'success',
                'Settings successfully saved'
                );
            return $this->redirect($this->generateUrl('app_user_settings'));
        }

        return array('form' => $form->createView());
    }
}
