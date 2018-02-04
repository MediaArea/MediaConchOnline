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
use AppBundle\Form\Type\CheckerOnlineFormType;
use AppBundle\Form\Type\CheckerRepositoryFormType;
use AppBundle\Form\Type\CheckerUploadFormType;
use AppBundle\Lib\MediaConch\MediaConchServerException;
use AppBundle\Lib\Quotas\Quotas;

/**
 * @Route("/")
 */
class CheckerController extends BaseController
{
    /**
     * @Route("/checker")
     * @Template()
     */
    public function checkerAction(Quotas $quotas)
    {
        // Remove MediaConch-Server-ID setting
        $settings = $this->get('mco.settings');
        $settings->removeMediaConchInstanceID();

        if ($quotas->hasUploadsRights()) {
            $formUpload = $this->createForm(CheckerUploadFormType::class);
        }

        if ($quotas->hasUrlsRights()) {
            $formOnline = $this->createForm(CheckerOnlineFormType::class);
        }

        if (null != $this->getParameter('mco_check_folder') && file_exists($this->getParameter('mco_check_folder'))) {
            if ($quotas->hasPolicyChecksRights()) {
                $formRepository = $this->createForm(CheckerRepositoryFormType::class);
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
                $helper = $this->get('vich_uploader.storage');
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
        if ($this->has('profiler')) {
            $this->get('profiler')->disable();
        }

        $displayFile = null;
        if (ctype_digit($request->query->get('display'))) {
            $display = $this->getDoctrine()
                ->getRepository('AppBundle:DisplayFile')
                ->findOneByUserOrSystem($request->query->get('display'), $this->getUser());
            if ($display) {
                $helper = $this->get('vich_uploader.storage');
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
    public function checkerAjaxFormAction(Request $request, Quotas $quotas)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $formUpload = $this->createForm(CheckerUploadFormType::class);
        $formUpload->handleRequest($request);
        if ($formUpload->isSubmitted()) {
            return $this->checkerAjaxFormUpload($formUpload, $quotas);
        }

        $formOnline = $this->createForm(CheckerOnlineFormType::class);
        $formOnline->handleRequest($request);

        if ($formOnline->isSubmitted()) {
            return $this->checkerAjaxFormOnline($formOnline, $quotas);
        }

        if (null != $this->getParameter('mco_check_folder') && file_exists($this->getParameter('mco_check_folder'))) {
            $formRepository = $this->createForm(CheckerRepositoryFormType::class);
            $formRepository->handleRequest($request);

            if ($formRepository->isSubmitted()) {
                return $this->checkerAjaxFormRepository($formRepository, $quotas);
            }
        }

        return new JsonResponse(array('message' => 'No form selected'), 400);
    }

    protected function checkerAjaxFormUpload($formUpload, Quotas $quotas)
    {
        if ($quotas->hasUploadsRights()) {
            if ($formUpload->isValid()) {
                $data = $formUpload->getData();

                $settings = $this->get('mco.settings');
                $settings->setLastUsedPolicy($data['policy']);
                $settings->setLastUsedDisplay($data['display']);
                $settings->setLastUsedVerbosity($data['verbosity']);

                if ($data['file']->isValid()) {
                    $path = $this->getParameter('kernel.project_dir').'/files/upload/'.$this->getUser()->getId();
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

                        $quotas->hitUploads();

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

    protected function checkerAjaxFormOnline($formOnline, Quotas $quotas)
    {
        if ($quotas->hasUrlsRights()) {
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

                    $quotas->hitUrls();

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

    protected function checkerAjaxFormRepository($formRepository, Quotas $quotas)
    {
        if ($quotas->hasPolicyChecksRights()) {
            if ($formRepository->isValid()) {
                $data = $formRepository->getData();

                $settings = $this->get('mco.settings');
                $settings->setLastUsedPolicy($data['policy']);
                $settings->setLastUsedDisplay($data['display']);
                $settings->setLastUsedVerbosity($data['verbosity']);

                try {
                    $finder = new Finder();
                    $finder->files()->in($this->getParameter('mco_check_folder'));
                    $checks = $this->get('mco.checker.analyze');
                    $files = array();
                    foreach ($finder as $file) {
                        $files[] = $file->getPathname();
                    }

                    $checks->analyse($files);

                    $quotas->hitPolicyChecks(count($finder));

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
