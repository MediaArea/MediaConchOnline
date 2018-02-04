<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\DisplayFile;
use AppBundle\Form\Type\DisplayImportFormType;
use AppBundle\Lib\Quotas\Quotas;

/**
 * @Route("/")
 */
class DisplayController extends BaseController
{
    /**
     * @Route("/display/")
     * @Template()
     */
    public function displayAction(Request $request, Quotas $quotas)
    {
        $displayList = $this->getDoctrine()
            ->getRepository('AppBundle:DisplayFile')
            ->findByUser($this->getUser());

        $displaySystemList = $this->getDoctrine()
            ->getRepository('AppBundle:DisplayFile')
            ->findByUser(null);

        if ($quotas->hasPolicyCreationRights()) {
            $display = new DisplayFile();
            $importDisplayForm = $this->createForm(DisplayImportFormType::class, $display);
            $importDisplayForm->handleRequest($request);
            if ($importDisplayForm->isSubmitted() && $importDisplayForm->isValid()) {
                $em = $this->getDoctrine()->getManager();

                // Set user at the creation of the policy
                if (null === $display->getUser()) {
                    $display->setUser($this->getUser());
                }

                $em->persist($display);
                $em->flush();

                $this->addFlashBag('success', 'Display successfully added');

                return $this->redirectToRoute('app_display_display');
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
            $this->addFlashBag('danger', 'Display not found');
        } else {
            $em = $this->getDoctrine()->getManager();
            $em->remove($policy);
            $em->flush();

            $this->addFlashBag('success', 'Display successfully removed');
        }

        return $this->redirectToRoute('app_display_display');
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
            $this->addFlashBag('danger', 'Display not found');

            return $this->redirectToRoute('app_display_display');
        }

        $handler = $this->get('vich_uploader.download_handler');

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
            $this->addFlashBag('danger', 'Display not found');

            return $this->redirectToRoute('app_display_display');
        }

        $handler = $this->get('vich_uploader.download_handler');

        return $handler->downloadObject($policy, 'displayFile');
    }
}
