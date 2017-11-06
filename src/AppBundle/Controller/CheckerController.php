<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Finder\Finder;
use AppBundle\Lib\MediaConch\MediaConchServerException;

/**
 * @Route("/")
 */
class CheckerController extends BaseController
{
    /**
     * @Route("/checker")
     * @Template()
     */
    public function checkerAction()
    {
        // Remove MediaConch-Server-ID setting
        $settings = $this->get('mco.settings');
        $settings->removeMediaConchInstanceID();

        if ($this->get('mediaconch_user.quotas')->hasUploadsRights()) {
            $formUpload = $this->createForm('checkerUpload');
        }

        if ($this->get('mediaconch_user.quotas')->hasUrlsRights()) {
            $formOnline = $this->createForm('checkerOnline');
        }

        if (null != $this->container->getParameter('mco_check_folder') && file_exists($this->container->getParameter('mco_check_folder'))) {
            if ($this->get('mediaconch_user.quotas')->hasPolicyChecksRights()) {
                $formRepository = $this->createForm('checkerRepository');
            }
        }

        return array('formUpload' => isset($formUpload) ? $formUpload->createView() : false,
            'formOnline' => isset($formOnline) ? $formOnline->createView() : false,
            'formRepository' => isset($formRepository) ? $formRepository->createView() : false,
            'repositoryEnable' => isset($formRepository),
        );
    }

    /**
     * @Route("/checkerStatus/")
     */
    public function checkerStatusAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Get the list of IDs
        $ids = $request->request->get('ids');

        if (is_array($ids) && count($ids) > 0) {
            try {
                $status = $this->get('mco.checker.status');
                $status->getStatus($ids);

                return new JsonResponse(array('status' => $status->getResponse()));
            } catch (MediaConchServerException $e) {
                return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * @Route("/checkerReportStatus/{id}/{reportType}", requirements={"id": "\d+", "reportType"})
     */
    public function checkerReportStatusAction($id, $reportType, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            $validate = $this->get('mco.checker.validate');
            $validate->validate($id, $reportType);

            return new JsonResponse($validate->getResponseAsArray());
        } catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * @Route("/checkerPolicyStatus/{id}", requirements={"id": "\d+"})
     */
    public function checkerPolicyStatusAction($id, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            $validate = $this->get('mco.checker.validate');
            $validate->validate($id, 1, $request->query->get('policy'));

            return new JsonResponse($validate->getResponseAsArray());
        } catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * @Route("/checkerReportStatusMulti/")
     */
    public function statusReportsMultiAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Reports list
        $reports = $request->request->get('reports');
        if (is_array($reports) && count($reports) > 0) {
            try {
                $response = array();
                foreach ($reports as $report) {
                    $validate = $this->get('mco.checker.validate');

                    // Implementation report
                    $validate->validate($report['id'], $report['tool']);
                    $response[$report['id']] = array('implemReport' => $validate->getResponseAsArray());

                    // Policy report
                    if (isset($report['policyId'])) {
                        $validate->validate($report['id'], 1, $report['policyId']);
                        $response[$report['id']]['policyReport'] = $validate->getResponseAsArray();
                    }
                }

                return new JsonResponse($response);
            } catch (MediaConchServerException $e) {
                return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * @Route("/checkerReport/{id}/{reportType}/{displayName}", requirements={"id": "\d+", "reportType", "displayName"})
     */
    public function checkerReportAction($id, $reportType, $displayName, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
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

        try {
            $file = $this->get('mco.checker.filename');
            $file->fileFromId($id);

            $report = $this->get('mco.checker.report');
            $report->report($id, $reportType, $displayName, $displayFile, $request->query->get('policy'), $request->query->get('verbosity'));

            $report->setFullPath(false, $file->getFilename(true));

            if (('mi' == $reportType || 'mt' == $reportType) && 'jstree' == $displayName) {
                return new Response($report->getReport());
            }

            return new JsonResponse($report->getResponseAsArray());
        } catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * @Route("/checkerCreatePolicy/{id}", requirements={"id": "\d+"})
     */
    public function checkerCreatePolicyAction($id, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            $policyFromFile = $this->get('mco.policy.fromFile');
            $policyFromFile->getPolicy($id);

            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyFromFile->getCreatedId());
            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($policyFromFile->getCreatedId());
            $policy->getResponse()->getPolicy();
            $policy = $policy->getResponse()->getPolicy();

            return new JsonResponse(array('result' => true, 'policyId' => $policy->id, 'policyName' => $policy->name));
        } catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * @Route("/checkerDownloadReport/{id}/{reportType}/{displayName}", requirements={"id": "\d+", "reportType", "displayName"})
     */
    public function checkerDownloadReportAction($id, $reportType, $displayName, Request $request)
    {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
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

        try {
            $file = $this->get('mco.checker.filename');
            $file->fileFromId($id);

            if ($request->query->get('miFormat')) {
                $displayName = null;
            }

            $report = $this->get('mco.checker.report');
            $report->report($id, $reportType, $displayName, $displayFile, $request->query->get('policy'), $request->query->get('verbosity'), $request->query->get('miFormat'));

            $report->setFullPath(false, $file->getFilename(true));
            $response = new Response($report->getReport());
            $disposition = $this->downloadFileDisposition($response, $file->getFilename().'_'.$report->getDownloadReportName().'.'.$report->getDownloadReportExtension());

            $response->headers->set('Content-Type', $report->getDownloadReportMimeType());
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-length', strlen($report->getReport()));

            return $response;
        } catch (MediaConchServerException $e) {
            throw new ServiceUnavailableHttpException();
        }
    }

    /**
     * @Route("/checkerAjaxForm")
     */
    public function checkerAjaxFormAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $formUpload = $this->createForm('checkerUpload');
        $formUpload->handleRequest($request);
        if ($formUpload->isSubmitted()) {
            return $this->checkerAjaxFormUpload($formUpload);
        }

        $formOnline = $this->createForm('checkerOnline');
        $formOnline->handleRequest($request);

        if ($formOnline->isSubmitted()) {
            return $this->checkerAjaxFormOnline($formOnline);
        }

        if (null != $this->container->getParameter('mco_check_folder') && file_exists($this->container->getParameter('mco_check_folder'))) {
            $formRepository = $this->createForm('checkerRepository');
            $formRepository->handleRequest($request);

            if ($formRepository->isSubmitted()) {
                return $this->checkerAjaxFormRepository($formRepository);
            }
        }

        return new JsonResponse(array('message' => 'No form selected'), 400);
    }

    protected function checkerAjaxFormUpload($formUpload)
    {
        if ($this->get('mediaconch_user.quotas')->hasUploadsRights()) {
            if ($formUpload->isValid()) {
                $data = $formUpload->getData();

                $settings = $this->get('mco.settings');
                $settings->setLastUsedPolicy($data['policy']);
                $settings->setLastUsedDisplay($data['display']);
                $settings->setLastUsedVerbosity($data['verbosity']);

                if ($data['file']->isValid()) {
                    $path = $this->container->getParameter('kernel.root_dir').'/../files/upload/'.$this->getUser()->getId();
                    $filename = $data['file']->getClientOriginalName();
                    $fileMd5 = md5(file_get_contents($data['file']->getRealPath()));

                    if (file_exists($path.'/'.$fileMd5.'/'.$filename)) {
                        $file = new File($path.'/'.$fileMd5.'/'.$filename);
                    } else {
                        $file = $data['file']->move($path.'/'.$fileMd5, $filename);
                    }

                    try {
                        $checks = $this->get('mco.checker.analyze');
                        $checks->analyse(array($file->getRealPath()));

                        $this->get('mediaconch_user.quotas')->hitUploads();

                        return new JsonResponse($checks->getResponseAsArray(), 200);
                    } catch (MediaConchServerException $e) {
                        return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
                    }
                }
            } else {
                return new JsonResponse(array('message' => $formUpload->getErrors(true)->current()->getMessage()), 400);
            }
        } else {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    protected function checkerAjaxFormOnline($formOnline)
    {
        if ($this->get('mediaconch_user.quotas')->hasUrlsRights()) {
            if ($formOnline->isValid()) {
                $data = $formOnline->getData();

                $settings = $this->get('mco.settings');
                $settings->setLastUsedPolicy($data['policy']);
                $settings->setLastUsedDisplay($data['display']);
                $settings->setLastUsedVerbosity($data['verbosity']);

                try {
                    $checks = $this->get('mco.checker.analyze');
                    $checks->setFullPath(true);
                    $checks->analyse(array(str_replace(' ', '%20', $data['file'])));

                    $this->get('mediaconch_user.quotas')->hitUrls();

                    return new JsonResponse($checks->getResponseAsArray(), 200);
                } catch (MediaConchServerException $e) {
                    return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
                }
            }
        } else {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    protected function checkerAjaxFormRepository($formRepository)
    {
        if ($this->get('mediaconch_user.quotas')->hasPolicyChecksRights()) {
            if ($formRepository->isValid()) {
                $data = $formRepository->getData();

                $settings = $this->get('mco.settings');
                $settings->setLastUsedPolicy($data['policy']);
                $settings->setLastUsedDisplay($data['display']);
                $settings->setLastUsedVerbosity($data['verbosity']);

                try {
                    $finder = new Finder();
                    $finder->files()->in($this->container->getParameter('mco_check_folder'));
                    $checks = $this->get('mco.checker.analyze');
                    $files = array();
                    foreach ($finder as $file) {
                        $files[] = $file->getPathname();
                    }

                    $checks->analyse($files);

                    $this->get('mediaconch_user.quotas')->hitPolicyChecks(count($finder));

                    return new JsonResponse($checks->getResponseAsArray(), 200);
                } catch (MediaConchServerException $e) {
                    return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
                }
            }
        } else {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * @Route("/checkerForceAnalyze/{id}", requirements={"id": "\d+"})
     */
    public function checkerForceAnalyzeAction($id, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            // Get the filename
            $file = $this->get('mco.checker.filename');
            $file->fileFromId($id);

            // Force analyze
            $checks = $this->get('mco.checker.analyze');
            $checks->analyse(array($file->getFilename(true)), true);
            $response = $checks->getResponseAsArray();

            return new JsonResponse($response);
        } catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * @Route("/checkerMediaInfoOutputList")
     */
    public function checkerMediaInfoOutputListAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            // Get the list
            $list = $this->get('mco.mediainfo.output.list');
            $list->getList();

            return new JsonResponse($list->getResponseAsArray());
        } catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }
}
