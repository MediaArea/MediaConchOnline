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
     * @Route("/publicpolicies/list")
     */
    public function publicPoliciesListAction(Request $request)
    {
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
                        'name' => $policy->name,
                        'description' => $policy->description,
                        );
                }
            }
        }
        catch (MediaConchServerException $e) {
            // Empty list
        }

        return new JsonResponse(array('list' => $list));
    }

    /**
     * Public policies get policy
     *
     * @Route("/publicpolicies/policy/{id}/{userId}", requirements={"id": "\d+", "userId": "\d+"})
     * @Route("/publicpolicies/policy/", defaults={"id" = -1, "userId" = -1})
     */
    public function publicPoliciesPolicyAction(Request $request, $id, $userId)
    {
        if (-1 == $id) {
            return new JsonResponse(null);
        }

        try {
            // Get policy
            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPublicPolicy($id, $userId, 'JSTREE');

            return new JsonResponse($policy->getResponse()->getPolicy());
        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }
}
