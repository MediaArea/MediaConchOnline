<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use AppBundle\Controller\BaseController;
use AppBundle\Lib\MediaConch\MediaConchServerException;

/**
 * @Route("/publicPolicies")
 */
class PublicPoliciesController extends BaseController
{
    /**
     * Public policies page
     *
     * @Route("")
     * @Template()
     */
    public function listAction()
    {
        // Remove MediaConch-Server-ID setting
        $settings = $this->get('mco.settings');
        $settings->removeMediaConchInstanceID();

        return array();
    }

    /**
     * Export XML of a policy
     * @param int id policy ID of the policy to export
     * @param int userId user ID of the policy to export
     *
     * @return XML
     *
     * @Route("/export/{id}/{userId}", requirements={"id": "\d+", "userId": "\d+"})
     * @Method("GET")
     */
    public function policyExportAction($id, $userId)
    {
        try {
            // Get policy XML
            $policyExport = $this->get('mco.policy.export');
            $policyExport->publicExport($id, $userId);

            // Get policy name
            $policyName = $this->get('mco.policy.getPolicyName');
            $policyName->getPublicPolicyName($id, $userId);
            $policyName = $policyName->getResponse()->getName();

            // Prepare response
            $response = new Response($policyExport->getPolicyXml());
            $disposition = $this->downloadFileDisposition($response, $policyName . '.xml');

            $response->headers->set('Content-Type', 'text/xml');
            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-length', strlen($policyExport->getPolicyXml()));

            return $response;
        }
        catch (MediaConchServerException $e) {
            throw new ServiceUnavailableHttpException();
        }
    }

    /**
     * Import a policy
     * @param int id policy ID of the policy to import
     * @param int userId user ID of the policy to import
     *
     * @return json
     * {"policyId":ID}
     *
     * @Route("/import/{id}/{userId}", requirements={"id": "\d+", "userId": "\d+"})
     * @Method("GET")
     */
    public function policyImportAction(Request $request, $id, $userId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Check quota only if policy is duplicated on the top level
        if (!$this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }

        try {
            // Duplicate policy
            $policyDuplicate = $this->get('mco.policy.duplicate');
            $policyDuplicate->publicDuplicate($id, $userId);

            // Edit policy visibility
            $policyEditVisibility = $this->get('mco.policy.editVisibility');
            $policyEditVisibility->editVisibility($policyDuplicate->getCreatedId(), 0);

            // Save policy
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyDuplicate->getCreatedId());

            return new JsonResponse(array('policyId' => $policyDuplicate->getCreatedId()));
        }
        catch (MediaConchServerException $e) {
            throw new ServiceUnavailableHttpException();
        }
    }
}
