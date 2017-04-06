<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use AppBundle\Controller\BaseController;
use AppBundle\Entity\DisplayFile;

/**
 * @Route("/")
 */
class DisplayController extends BaseController
{
    /**
     * @Route("/display/")
     * @Template()
     */
    public function displayAction(Request $request)
    {
        $displayList = $this->getDoctrine()
            ->getRepository('AppBundle:DisplayFile')
            ->findByUser($this->getUser());

        $displaySystemList = $this->getDoctrine()
            ->getRepository('AppBundle:DisplayFile')
            ->findByUser(null);

        if ($this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            $display = new DisplayFile();
            $importDisplayForm = $this->createForm('displayImport', $display);
            $importDisplayForm->handleRequest($request);
            if ($importDisplayForm->isValid()) {
                $em = $this->getDoctrine()->getManager();

                // Set user at the creation of the policy
                if (null === $display->getUser()) {
                    $display->setUser($this->getUser());
                }

                $em->persist($display);
                $em->flush();

                $this->addFlashBag('success', 'Display successfully added');

                return $this->redirect($this->generateUrl('app_display_display'));
            }
        }

        return array('importDisplayForm' => isset($importDisplayForm) ? $importDisplayForm->createView() : false,
                     'displayList' => $displayList,
                     'displaySystemList' => $displaySystemList,
                     );
    }



    /**
     * @Route("/display/delete/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function displayDeleteAction($id)
    {
        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:DisplayFile')
            ->findOneBy(array('id' => $id, 'user' => $this->getUser()));

        if (!$policy) {
            $this->addFlashBag('danger', 'Policy display not found');
        } else {
            $em = $this->getDoctrine()->getManager();
            $em->remove($policy);
            $em->flush();

            $this->addFlashBag('success', 'Policy display successfully removed');
        }

        return $this->redirect($this->generateUrl('app_display_display'));
    }

    /**
     * @Route("/display/export/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function displayExportAction($id)
    {
        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:DisplayFile')
            ->findOneBy(array('id' => $id, 'user' => $this->getUser()));

        if (!$policy) {
            $this->addFlashBag('danger', 'Policy display not found');

            return $this->redirect($this->generateUrl('app_display_display'));
        }

        $handler = $this->container->get('vich_uploader.download_handler');
        return $handler->downloadObject($policy, 'displayFile');
    }

    /**
     * @Route("/display/system/export/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function displaySystemExportAction($id)
    {
        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:DisplayFile')
            ->findOneBy(array('id' => $id, 'user' => null));

        if (!$policy) {
            $this->addFlashBag('danger', 'Policy display not found');

            return $this->redirect($this->generateUrl('app_display_display'));
        }

        $handler = $this->container->get('vich_uploader.download_handler');
        return $handler->downloadObject($policy, 'displayFile');
    }
}
