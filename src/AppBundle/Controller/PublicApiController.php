<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use AppBundle\Lib\MediaConch\MediaConchServerException;

/**
 * @Route("/api/public/v1")
 */
class PublicApiController extends Controller
{
    /**
     * Public policies page
     *
     * @return json
     * @Route("/publicpolicies/list")
     */
    public function publicPoliciesListAction(Request $request)
    {
        // Remove MediaConch-Server-ID setting
        $settings = $this->get('mco.settings');
        $settings->removeMediaConchInstanceID();

        // Get start value
        $start = $request->request->get('start', 0);

        // Get limit value
        $limit = $request->request->get('limit', 100);

        $list = array();

        try {
            // Get public policies from server
            $policyList = $this->get('mco.policy.getPublicPolicies');
            $policyList->getPublicPolicies();
            $policyList = $policyList->getResponse()->getPolicies();

            if (0 < count($policyList)) {
                // Fetch user list
                $userList = array();
                foreach ($policyList as $policy) {
                    if (!in_array($policy->user, $userList)) {
                        $userList[] = $policy->user;
                    }
                }

                // Fetch users
                $query = $this->getDoctrine()->getRepository('AppBundle:User')->createQueryBuilder('u')
                    ->select('u.id, u.username, u.firstname, u.lastname, u.companyName')
                    ->where('u.id IN (:userId)')
                    ->setParameter('userId', $userList)
                    ->getQuery();
                $userList = array();
                foreach ($query->getArrayResult() as $user) {
                    $name = '';
                    // Firstname
                    if (null !== $user['firstname'] && '' != trim($user['firstname'])) {
                        $name .= trim($user['firstname']) . ' ';
                    }
                    // Lastname
                    if (null !== $user['lastname'] && '' != trim($user['lastname'])) {
                        $name .= trim($user['lastname']);
                    }
                    // Username if no firstname or lastname
                    if ('' == $name) {
                        $name = trim($user['username']);
                    }
                    // CompanyName
                    if (null !== $user['companyName'] && '' != trim($user['companyName'])) {
                        $name .= ' (' . trim($user['companyName']) . ')';
                    }

                    $userList[$user['id']] = $name;
                }

                // Build result list
                foreach ($policyList as $policy) {
                    $list[] = array('id' => $policy->id,
                        'user' => array('id' => $policy->user, 'name' => $userList[$policy->user]),
                        'name' => htmlspecialchars($policy->name),
                        'description' => htmlspecialchars($policy->description),
                        'license' => isset($policy->license) ? $policy->license : '',
                        );
                }
            }
        }
        catch (MediaConchServerException $e) {
            // Empty list
        }

        $response = new JsonResponse(array('list' => $list));
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    /**
     * Public policies get policy
     * @param int id policy ID of the policy to import
     * @param int userId user ID of the policy to import
     *
     * @return json
     * {"policy":POLICY_JSTREE_JSON}
     * @Route("/publicpolicies/policy/{id}/{userId}", requirements={"id": "\d+", "userId": "\d+"})
     */
    public function publicPoliciesPolicyAction(Request $request, $id, $userId)
    {
        try {
            // Get policy
            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPublicPolicy($id, $userId, 'JSTREE');

            $response = new JsonResponse($policy->getResponse()->getPolicy());
        }
        catch (MediaConchServerException $e) {
            $response = new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    /**
     * Public policies get policy
     * @param int id policy ID of the policy to import
     * @param int userId user ID of the policy to import
     *
     * @return XML
     * @Route("/publicpolicies/policy/export/{id}/{userId}", requirements={"id": "\d+", "userId": "\d+"})
     */
    public function publicPoliciesPolicyExportAction(Request $request, $id, $userId)
    {
        try {
            // Get policy XML
            $policyExport = $this->get('mco.policy.export');
            $policyExport->publicExport($id, $userId);

            $response = new Response($policyExport->getPolicyXml());
        }
        catch (MediaConchServerException $e) {
            $response = new Response('<?xml version="1.0"?><error />', $e->getStatusCode());
        }

        $response->headers->set('Content-Type', 'xml');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
