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
 * @Route("/api/protected/v1")
 */
class ProtectedApiController extends Controller
{
     /**
      * Import a policy to the oublic policies list
      *
      * @return json
      * @Route("/publicpolicies/import")
      * @Method({"POST"})
      */
      public function importPolicyToPublicPoliciesAction(Request $request)
      {
          // Get the policy XML
          $xml = $request->request->get('xml');

          if (null === $xml || '' == $xml) {
              return new JsonResponse(array('message' => 'The policy XML is empty'), 400);
          }

          try {
              // Import policy
              $policyImport = $this->get('mco.policy.import');
              $policyImport->import($xml);

              // Make policy public
              $policyEditVisibility = $this->get('mco.policy.editVisibility');
              $policyEditVisibility->editVisibility($policyImport->getCreatedId(), 'public');

              // Save policy
              $policySave = $this->get('mco.policy.save');
              $policySave->save($policyImport->getCreatedId());
          }
          catch (MediaConchServerException $e) {
              return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
          }

          return new JsonResponse(array('message' => 'Success'));
      }
}
