<?php

namespace MediaConchOnline\Bundle\UserBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Controller\ProfileController as BaseController;
use FOS\UserBundle\Model\UserInterface;

class ProfileController extends BaseController
{
    /**
     * Show the user.
     */
    public function showAction()
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $quotas = $this->container->get('mediaconch_user.quotas')->getQuotasForProfile();

        return $this->container->get('templating')->renderResponse(
            'FOSUserBundle:Profile:show.html.twig',
            array('user' => $user, 'quotas' => $quotas)
        );
    }
}
