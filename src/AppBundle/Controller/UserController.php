<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Controller\BaseController;

/**
 * @Route("/")
 */
class UserController extends BaseController
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

            $this->addFlashBag('success', 'Settings successfully saved');

            return $this->redirect($this->generateUrl('app_user_settings'));
        }

        return array('form' => $form->createView());
    }
}
