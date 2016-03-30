<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Finder\Finder;

/**
 * @Route("/")
 */
class CheckerController extends Controller
{
    /**
     * @Route("/checker")
     * @Template()
     */
    public function checkerAction(Request $request)
    {
        $selectForm = false;

        if ($this->get('mediaconch_user.quotas')->hasUploadsRights()) {
            $formUpload = $this->createForm('checkerUpload');
        }

        if ($this->get('mediaconch_user.quotas')->hasUrlsRights()) {
            $formOnline = $this->createForm('checkerOnline');
        }

        if (null != $this->container->getParameter('mco_check_folder') && file_exists($this->container->getParameter('mco_check_folder'))) {
            $repositoryEnable = true;
            if ($this->get('mediaconch_user.quotas')->hasPolicyChecksRights()) {
                $formRepository = $this->createForm('checkerRepository');
            }
        }
        else {
            $repositoryEnable = false;
        }

        return array('formUpload' => isset($formUpload) ? $formUpload->createView() : false,
            'formOnline' => isset($formOnline) ? $formOnline->createView() : false,
            'formRepository' => isset($formRepository) ? $formRepository->createView() : false,
            'repositoryEnable' => $repositoryEnable,
            'selectForm' => $selectForm);
    }

    /**
     * @Route("/checkerStatus/{id}", requirements={"id": "\d+"})
     */
    public function checkerStatusAction($id, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $status = $this->get('mco.checker.status');
        $status->getStatus($id);

        return new JsonResponse($status->getResponseAsArray());
    }

    /**
     * @Route("/checkerReportStatus/{id}/{reportType}", requirements={"id": "\d+", "reportType"})
     */
    public function checkerReportStatusAction($id, $reportType, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $validate = $this->get('mco.checker.validate');
        $validate->validate($id, $reportType);

        return new JsonResponse($validate->getResponseAsArray());
    }

    /**
     * @Route("/checkerPolicyStatus/{id}", requirements={"id": "\d+"})
     */
    public function checkerPolicyStatusAction($id, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $policyFile = null;
        if (ctype_digit($request->query->get('policy'))) {
            $policy = $this->getDoctrine()
                ->getRepository('AppBundle:XslPolicyFile')
                ->findOneByUserOrSystem($request->query->get('policy'), $this->getUser());
            if ($policy) {
                $helper = $this->container->get('vich_uploader.storage');
                $policyFile = $helper->resolvePath($policy, 'policyFile');
            }
        }

        $validate = $this->get('mco.checker.validate');
        $validate->validate($id, 'POLICY', $policyFile);

        return new JsonResponse($validate->getResponseAsArray());
    }

    /**
     * @Route("/checkerReport/{id}/{reportType}/{displayName}", requirements={"id": "\d+", "reportType", "displayName"})
     */
    public function checkerReportAction($id, $reportType, $displayName, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $policyFile = null;
        if (ctype_digit($request->query->get('policy'))) {
            $policy = $this->getDoctrine()
                ->getRepository('AppBundle:XslPolicyFile')
                ->findOneByUserOrSystem($request->query->get('policy'), $this->getUser());
            if ($policy) {
                $helper = $this->container->get('vich_uploader.storage');
                $policyFile = $helper->resolvePath($policy, 'policyFile');
            }
        }

        $displayFile = null;
        if (ctype_digit($request->query->get('display'))) {
            $display = $this->getDoctrine()
                ->getRepository('AppBundle:DisplayFile')
                ->findOneByUserOrSystem($request->query->get('display'), $this->getUser());
            if ($display) {
                $helper = $this->container->get('vich_uploader.storage');
                $displayFile = $helper->resolvePath($display, 'displayFile');
            }
        }

        $file = $this->get('mco.checker.filename');
        $file->fileFromId($id);

        $report = $this->get('mco.checker.report');
        $report->report($id, $reportType, $displayName, $displayFile, $policyFile, $request->query->get('verbosity'));
        $report->setFullPath(false, $file->getFilename(true));

        if (($reportType == 'mi' || $reportType == 'mt') && $displayName == 'jstree') {
            return new Response($report->getReport());
        }
        else {
            return new JsonResponse($report->getResponseAsArray());
        }
    }

    /**
     * @Route("/checkerDownloadReport/{id}/{reportType}/{displayName}", requirements={"id": "\d+", "reportType", "displayName"})
     */
    public function checkerDownloadReportAction($id, $reportType, $displayName, Request $request)
    {
        if ($this->container->has('profiler'))
        {
            $this->container->get('profiler')->disable();
        }

        $policyFile = null;
        if (ctype_digit($request->query->get('policy'))) {
            $policy = $this->getDoctrine()
                ->getRepository('AppBundle:XslPolicyFile')
                ->findOneByUserOrSystem($request->query->get('policy'), $this->getUser());
            if ($policy) {
                $helper = $this->container->get('vich_uploader.storage');
                $policyFile = $helper->resolvePath($policy, 'policyFile');
            }
        }

        $displayFile = null;
        if (ctype_digit($request->query->get('display'))) {
            $display = $this->getDoctrine()
            ->getRepository('AppBundle:DisplayFile')
            ->findOneByUserOrSystem($request->query->get('display'), $this->getUser());
            if ($display) {
                $helper = $this->container->get('vich_uploader.storage');
                $displayFile = $helper->resolvePath($display, 'displayFile');
            }
        }

        $file = $this->get('mco.checker.filename');
        $file->fileFromId($id);

        $report = $this->get('mco.checker.report');
        $report->report($id, $reportType, $displayName, $displayFile, $policyFile, $request->query->get('verbosity'));
        $report->setFullPath(false, $file->getFilename(true));

        $response = new Response($report->getReport());
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->getFilename() . '_' . $report->getDownloadReportName() . '.' . $report->getDownloadReportExtension()
        );

        $response->headers->set('Content-Type', $report->getDownloadReportMimeType());
        $response->headers->set('Content-Disposition', $d);
        $response->headers->set('Content-length', strlen($report->getReport()));

        return $response;
    }

    /**
     * @Route("/checkerAjaxForm")
     */
    public function checkerAjaxFormAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        if ($this->get('mediaconch_user.quotas')->hasUploadsRights()) {
            $formUpload = $this->createForm('checkerUpload');
            $formUpload->handleRequest($request);

            if (null !== $data = $formUpload->getData()) {
                if ($formUpload->isValid()) {
                    if ($data['file']->isValid()) {
                        $path = $this->container->getParameter('kernel.root_dir').'/../files/upload/' . $this->getUser()->getId();
                        $filename =  $data['file']->getClientOriginalName();
                        $fileMd5 = md5(file_get_contents($data['file']->getRealPath()));

                        if (file_exists($path . '/' . $fileMd5 . '/' . $filename)) {
                            $file = new File($path . '/' . $fileMd5 . '/' . $filename);
                        }
                        else {
                            $file = $data['file']->move($path . '/' . $fileMd5, $filename);
                        }

                        $checks = $this->get('mco.checker.analyze');
                        $checks->analyse($file->getRealPath());
                        $response = $checks->getResponseAsArray();

                        $this->get('mediaconch_user.quotas')->hitUploads();

                        return new JsonResponse($response, 200);
                    }
                }
                else {
                    return new JsonResponse(array('message', 'Error'), 400);
                }

            }
        }

        if ($this->get('mediaconch_user.quotas')->hasUrlsRights()) {
            $formOnline = $this->createForm('checkerOnline');
            $formOnline->handleRequest($request);

            if (null !== $data = $formOnline->getData()) {
                if ($formOnline->isValid()) {

                    $checks = $this->get('mco.checker.analyze');
                    $checks->analyse(str_replace(' ', '%20', $data['file']));
                    $response = $checks->getResponseAsArray();

                    $this->get('mediaconch_user.quotas')->hitUrls();

                    return new JsonResponse($response, 200);
                }
                else {
                    return new JsonResponse(array('message', 'Error'), 400);
                }
            }
        }

        if (null != $this->container->getParameter('mco_check_folder') && file_exists($this->container->getParameter('mco_check_folder'))) {
            if ($this->get('mediaconch_user.quotas')->hasPolicyChecksRights()) {
                $formRepository = $this->createForm('checkerRepository');
                $formRepository->handleRequest($request);

                if (null !== $data = $formRepository->getData()) {
                    if ($formRepository->isValid()) {
                        $response = array();

                        $finder = new Finder();
                        $finder->files()->in($this->container->getParameter('mco_check_folder'));
                        foreach($finder as $file) {
                            $checks = $this->get('mco.checker.analyze');
                            $checks->analyse($file->getPathname());
                            $response[] = $checks->getResponseAsArray();
                        }

                        $this->get('mediaconch_user.quotas')->hitPolicyChecks(count($finder));

                        return new JsonResponse($response, 200);
                    }
                    else {
                        return new JsonResponse(array('message', 'Error'), 400);
                    }
                }
            }
        }
    }
}
