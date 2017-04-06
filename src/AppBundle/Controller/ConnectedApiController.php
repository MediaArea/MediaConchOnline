<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use AppBundle\Controller\BaseController;
use AppBundle\Lib\MediaConch\MediaConchServerException;

/**
 * @Route("/api/connected/v1")
 */
class ConnectedApiController extends BaseController
{
    /**
     * Public policies list
     *
     * @return json
     * @Route("/publicpolicies/list")
     * @Method({"GET"})
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
                        'description' => nl2br(htmlspecialchars($policy->description)),
                        'license' => isset($policy->license) ? $policy->license : '',
                        'allowEdit' => ($this->getUser()->getId() == $policy->user)
                        );
                }
            }
        } catch (MediaConchServerException $e) {
            // Empty list
        }

        return new JsonResponse(array('list' => $list));
    }

    /**
     * Unpublish a public policy
     *
     * @return json
     * @Route("/publicpolicies/unpublish/{id}", requirements={"id": "\d+"})
     * @Method({"PUT"})
     */
    public function publicPoliciesUnpublishAction($id)
    {
        try {
            // Make policy private
            $policyEditVisibility = $this->get('mco.policy.editVisibility');
            $policyEditVisibility->editVisibility($id, false);

            // Save policy
            $policySave = $this->get('mco.policy.save');
            $policySave->save($id);

            return new JsonResponse(array('policyId' => $id));
        } catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }
}
