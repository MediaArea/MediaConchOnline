<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use AppBundle\Controller\BaseController;
use AppBundle\Lib\XslPolicy\XslPolicyFormFields;
use AppBundle\Lib\MediaConch\MediaConchServerException;

/**
 * @Route("/")
 */
class XslPolicyController extends BaseController
{
    /**
     * Old policy editor page
     *
     * @Route("/xslPolicyTree/")
     */
    public function xslPolicyTreeOldAction()
    {
        return $this->redirectToRoute('app_xslpolicy_xslpolicytree', array(), 301);
    }

    /**
     * Policy editor page
     *
     * @Route("/policyEditor")
     * @Template()
     */
    public function xslPolicyTreeAction()
    {
        // Forms
        $policyRuleForm = $this->createForm('xslPolicyRule');
        $policyRuleMtForm = $this->createForm('xslPolicyRuleMt');
        $policyInfoForm = $this->createForm('xslPolicyInfo');

        if ($this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            $importPolicyForm = $this->createForm('xslPolicyImport');
            $policyCreateFromFileForm = $this->createForm('xslPolicyCreateFromFile');
        }

        return array(
            'policyRuleForm' => $policyRuleForm->createView(),
            'policyRuleMtForm' => $policyRuleMtForm->createView(),
            'importPolicyForm' => isset($importPolicyForm) ? $importPolicyForm->createView() : false,
            'policyCreateFromFileForm' => isset($policyCreateFromFileForm) ? $policyCreateFromFileForm->createView() : false,
            'policyInfoForm' => $policyInfoForm->createView(),
            );
    }

    /**
     * Get the json for jstree
     *
     * @return json
     * {"policiesTree":POLICIES_JSTREE_JSON}
     *
     * @Route("/xslPolicyTree/ajax/data")
     */
    public function xslPolicyTreeDataAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Remove MediaConch-Server-ID setting
        $settings = $this->get('mco.settings');
        $settings->removeMediaConchInstanceID();

        try {
            $policies = $this->get('mco.policy.getPolicies');
            $policies->getPolicies(array(), 'JSTREE');

            return new JsonResponse(array('policiesTree' => $policies->getResponse()->getPolicies()), 200);
        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * Create a policy
     * @param int parentId policy ID in which the new policy will be created
     *
     * @return json
     * {"policy":POLICY_JSTREE_JSON}
     *
     * @Route("/xslPolicyTree/ajax/create/{parentId}", requirements={"parentId": "(-)?\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeCreateAction($parentId, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Check quota only if policy is created on the top level
        if (-1 == $parentId && !$this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }

        try {
            // Create policy
            $policyCreate = $this->get('mco.policy.create');
            $policyCreate->create($parentId);

            // Save policy
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyCreate->getCreatedId());

            // Get policy
            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($policyCreate->getCreatedId(), 'JSTREE');

            return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * Import a policy from an XML (the XML is provided as POST data from a form)
     *
     * @return json
     * {"policy":POLICY_JSTREE_JSON}
     *
     * @Route("/xslPolicyTree/ajax/import")
     * @Method("POST")
     */
    public function xslPolicyTreeImportAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Check quota
        if (!$this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }

        $importPolicyForm = $this->createForm('xslPolicyImport');
        $importPolicyForm->handleRequest($request);
        if ($importPolicyForm->isValid()) {
            $data = $importPolicyForm->getData();
            if ($data['policyFile']->isValid()) {
                try {
                    // Import policy
                    $policyImport = $this->get('mco.policy.import');
                    $policyImport->import(file_get_contents($data['policyFile']->getRealPath()));

                    // Save policy
                    $policySave = $this->get('mco.policy.save');
                    $policySave->save($policyImport->getCreatedId());

                    // Get policy
                    $policy = $this->get('mco.policy.getPolicy');
                    $policy->getPolicy($policyImport->getCreatedId(), 'JSTREE');

                    return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
                }
                catch (MediaConchServerException $e) {
                    return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
                }
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * Create a policy from a file (the file is provided as POST data from a form)
     *
     * @return json
     * {"policy":POLICY_JSTREE_JSON}
     *
     * @Route("/xslPolicyTree/ajax/createFromFile")
     * @Method("POST")
     */
    public function xslPolicyTreeCreateFromFileAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Check quota
        if (!$this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }

        $policyCreateFromFileForm = $this->createForm('xslPolicyCreateFromFile');
        $policyCreateFromFileForm->handleRequest($request);
        if ($policyCreateFromFileForm->isValid()) {
            $data = $policyCreateFromFileForm->getData();
            if ($data['file']->isValid()) {
                $path = $this->container->getParameter('kernel.root_dir').'/../files/uploadTmp/' . $this->getUser()->getId();
                $filename =  $data['file']->getClientOriginalName();
                $file = $data['file']->move($path . '/', $filename);

                try {
                    // Analyze file
                    $checks = $this->get('mco.checker.analyze');
                    $checks->analyse($file->getRealPath());
                    $response = $checks->getResponseAsArray();
                    $transactionId = $response['transactionId'];

                    // Wait for analyze is complete
                    usleep(200000);
                    $status = $this->get('mco.checker.status');
                    for ($loop = 100; $loop--; $loop >= 0) {
                        $status->getStatus($transactionId);
                        $response = $status->getResponse()->getResponse();
                        // Stop the loop when analyze is finish
                        if (isset($response[$transactionId]['finish']) && true === $response[$transactionId]['finish']) {
                            $loop = 0;
                        }
                        else if (0 == $loop) {
                            throw new MediaConchServerException('Analyze is not finish', 400);
                        }
                        else {
                            usleep(500000);
                        }
                    }

                    // Remove tmp file
                    unlink($file);

                    // Generate policy
                    $policyFromFile = $this->get('mco.policy.fromFile');
                    $policyFromFile->getPolicy($transactionId);

                    // Save policy
                    $policySave = $this->get('mco.policy.save');
                    $policySave->save($policyFromFile->getCreatedId());

                    // Get policy
                    $policy = $this->get('mco.policy.getPolicy');
                    $policy->getPolicy($policyFromFile->getCreatedId(), 'JSTREE');

                    return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
                }
                catch (MediaConchServerException $e) {
                    // Remove tmp file
                    unlink($file);

                    return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
                }
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * Export XML of a policy
     * @param int id policy ID of the policy to export
     *
     * @return XML
     *
     * @Route("/xslPolicyTree/export/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeExportAction($id)
    {
        try {
            // Get policy name
            $policyName = $this->get('mco.policy.getPolicyName');
            $policyName->getPolicyName($id);
            $policyName = $policyName->getResponse()->getName();

            // Get policy XML
            $policyExport = $this->get('mco.policy.export');
            $policyExport->export($id);

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
     * Edit a policy (POST data from a form)
     * @param int id policy ID of the policy to edit
     *
     * @return json
     * {"policy":POLICY_JSTREE_JSON}
     *
     * @Route("/xslPolicyTree/ajax/edit/{id}", requirements={"id": "\d+"})
     * @Method("POST")
     */
    public function xslPolicyTreeEditAction(Request $request, $id)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $policyEditForm = $this->createForm('xslPolicyInfo');
        $policyEditForm->handleRequest($request);
        if ($policyEditForm->isValid()) {
            $data = $policyEditForm->getData();

            try {
                // Edit policy name and description
                $policyEdit = $this->get('mco.policy.edit');
                $policyEdit->edit($id, $data['policyName'], $data['policyDescription'], $data['policyLicense']);

                // Edit policy type
                $policyEditType = $this->get('mco.policy.editType');
                $policyEditType->editType($id, $data['policyType']);

                // Edit policy visibility if policy is top level
                if (1 == $data['policyTopLevel'] && $this->get('security.authorization_checker')->isGranted('ROLE_BASIC')) {
                    $policyEditVisibility = $this->get('mco.policy.editVisibility');
                    $policyEditVisibility->editVisibility($id, $data['policyVisibility']);
                }

                // Save policy
                $policySave = $this->get('mco.policy.save');
                $policySave->save($id);

                // Get policy
                $policy = $this->get('mco.policy.getPolicy');
                $policy->getPolicy($id, 'JSTREE');

                return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
            }
            catch (MediaConchServerException $e) {
                return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * Duplicate a policy
     * @param int id policy ID of the policy to duplicate
     * @param int dstPolicyId policy ID of the destination policy
     *
     * @return json
     * {"policy":POLICY_JSTREE_JSON}
     *
     * @Route("/xslPolicyTree/ajax/duplicate/{id}/{dstPolicyId}", requirements={"id": "\d+", "dstPolicyId": "(-)?\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeDuplicateAction(Request $request, $id, $dstPolicyId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Check quota only if policy is duplicated on the top level
        if (-1 == $dstPolicyId && !$this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }

        try {
            // Duplicate policy
            $policyDuplicate = $this->get('mco.policy.duplicate');
            $policyDuplicate->duplicate($id, $dstPolicyId);

            // Save policy
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyDuplicate->getCreatedId());

            // Get policy
            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($policyDuplicate->getCreatedId(), 'JSTREE');

            return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * Move a policy
     * @param int id policy ID of the policy to duplicate
     * @param int dstPolicyId policy ID of the destination policy
     *
     * @return json
     * {"policy":POLICY_JSTREE_JSON}
     *
     * @Route("/xslPolicyTree/ajax/move/{id}/{dstPolicyId}", requirements={"id": "\d+", "dstPolicyId": "(-)?\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeMoveAction(Request $request, $id, $dstPolicyId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            // Move policy
            $policyMove = $this->get('mco.policy.move');
            $policyMove->move($id, $dstPolicyId);

            // Save policy
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyMove->getCreatedId());

            // Get policy
            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($policyMove->getCreatedId(), 'JSTREE');

            return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * Delete a policy
     * @param int id policy ID of the policy to duplicate
     *
     * @return json
     * {"policyId":ID}
     *
     * @Route("/xslPolicyTree/ajax/delete/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeDeleteAction(Request $request, $id)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            // Delete policy
            $policyDelete = $this->get('mco.policy.delete');
            $policyDelete->delete($id);

            return new JsonResponse(array('policyId' => $id), 200);
        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * Add a rule to a policy
     * @param int policyId policy ID of the policy that will contain the rule
     *
     * @return json
     * {"rule":{"tracktype":TRACKTYPE, "field":FIELD, "id":RULE_ID, "name":NAME, "value":VALUE, "occurrence":OCCURENCE, "ope":OPERATOR}}
     *
     * @Route("/xslPolicyTree/ajax/ruleCreate/{policyId}", requirements={"policyId": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeRuleCreateAction(Request $request, $policyId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            // Create rule
            $ruleCreate = $this->get('mco.policy.rule.create');
            $ruleCreate->create($policyId);

            // Save policy
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyId);

            // Get rule
            $rule = $this->get('mco.policy.getRule');
            $rule->getRule($ruleCreate->getCreatedId(), $policyId);

            return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);
        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * Edit a rule (POST data from a form)
     * @param int id rule ID of the rule to edit
     * @param int policyId policy ID of the policy that contain the rule
     *
     * @return json
     * {"rule":{"tracktype":TRACKTYPE, "field":FIELD, "id":RULE_ID, "name":NAME, "value":VALUE, "occurrence":OCCURENCE, "ope":OPERATOR}}
     *
     * @Route("/xslPolicyTree/ajax/ruleEdit/{id}/{policyId}", requirements={"id": "\d+", "policyId": "\d+"})
     * @Method("POST")
     */
    public function xslPolicyTreeRuleEditAction(Request $request, $id, $policyId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Get the requested form
        if ($request->request->has('xslPolicyRuleMt')) {
            $policyRuleForm = $this->createForm('xslPolicyRuleMt');
        }
        else {
            $policyRuleForm = $this->createForm('xslPolicyRule');
        }

        $policyRuleForm->handleRequest($request);
        if ($policyRuleForm->isValid()) {
            $data = $policyRuleForm->getData();

            try {
                // Edit rule
                $ruleEdit = $this->get('mco.policy.rule.edit');
                $ruleEdit->edit($id, $policyId, $data);

                // Save policy
                $policySave = $this->get('mco.policy.save');
                $policySave->save($policyId);

                // Get rule
                $rule = $this->get('mco.policy.getRule');
                $rule->getRule($id, $policyId);

                return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);
            }
            catch (MediaConchServerException $e) {
                return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * Delete a rule
     * @param int id rule ID of the rule to delete
     * @param int policyId policy ID of the policy that contain the rule
     *
     * @return json
     * {"id":RULE_ID}
     *
     * @Route("/xslPolicyTree/ajax/ruleDelete/{id}/{policyId}", requirements={"id": "\d+", "policyId": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeRuleDeleteAction(Request $request, $id, $policyId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            // Delete rule
            $ruleDelete = $this->get('mco.policy.rule.delete');
            $ruleDelete->delete($id, $policyId);

            // Save policy
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyId);

            return new JsonResponse(array('id' => $id), 200);
        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * Duplicate a rule
     * @param int id rule ID of the rule to duplicate
     * @param int policyId policy ID of the policy that contain the rule
     * @param int dstPolicyId policy ID of the destination policy
     *
     * @return json
     * {"rule":{"tracktype":TRACKTYPE, "field":FIELD, "id":RULE_ID, "name":NAME, "value":VALUE, "occurrence":OCCURENCE, "ope":OPERATOR}}
     *
     * @Route("/xslPolicyTree/ajax/ruleDuplicate/{id}/{policyId}/{dstPolicyId}", requirements={"id": "\d+", "policyId": "\d+", "dstPolicyId": "(-)?\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeRuleDuplicateAction(Request $request, $id, $policyId, $dstPolicyId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            // Duplicate rule
            $ruleDuplicate = $this->get('mco.policy.rule.duplicate');
            $ruleDuplicate->duplicate($id, $policyId, $dstPolicyId);

            // Save policy
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyId);

            // Get rule
            $rule = $this->get('mco.policy.getRule');
            $rule->getRule($ruleDuplicate->getCreatedId(), $dstPolicyId);

            return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);

        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * Move a rule
     * @param int id rule ID of the rule to move
     * @param int policyId policy ID of the policy that contain the rule
     * @param int dstPolicyId policy ID of the destination policy
     *
     * @return json
     * {"rule":{"tracktype":TRACKTYPE, "field":FIELD, "id":RULE_ID, "name":NAME, "value":VALUE, "occurrence":OCCURENCE, "ope":OPERATOR}}
     *
     * @Route("/xslPolicyTree/ajax/ruleMove/{id}/{policyId}/{dstPolicyId}", requirements={"id": "\d+", "policyId": "\d+", "dstPolicyId": "(-)?\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeRuleMoveAction(Request $request, $id, $policyId, $dstPolicyId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        try {
            // Move rule
            $ruleMove = $this->get('mco.policy.rule.move');
            $ruleMove->move($id, $policyId, $dstPolicyId);

            // Save policy
            $policySave = $this->get('mco.policy.save');
            $policySave->save($dstPolicyId);
            $policySave->save($policyId);

            // Get rule
            $rule = $this->get('mco.policy.getRule');
            $rule->getRule($ruleMove->getCreatedId(), $dstPolicyId);

            return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);
        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }

    /**
     * Get list of fields for a trackType (POST : type and field)
     *
     * @return json
     *
     * @Route("/xslPolicy/fieldsListRule")
     * @Method({"POST"})
     */
    public function xslPolicyRuleFieldsListAction(Request $request) {
        if (! $request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Get the type value
        $type = $request->request->get('type');

        // Get the field value
        $field = $request->request->get('field', null);

        return new JsonResponse(XslPolicyFormFields::getFields($type, $field));
    }

    /**
     * Get list of values for a trackType and a field (POST : type, field and value)
     *
     * @return json
     *
     * @Route("/xslPolicyTree/ajax/valueListRule")
     * @Method({"POST"})
     */
    public function xslPolicyRuleValuesListAction(Request $request) {
        if (! $request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        // Get the type value
        $type = $request->request->get('type');

        // Get the field value
        $field = $request->request->get('field');

        // Get the value
        $value = $request->request->get('value');

        try {
            $valuesList = $this->get('mco.policy.form.values');
            $valuesList->getValues($type, $field, $value);

            return new JsonResponse($valuesList->getResponseAsArray());
        }
        catch (MediaConchServerException $e) {
            return new JsonResponse(array('message' => 'Error'), $e->getStatusCode());
        }
    }
}
