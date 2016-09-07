<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

use AppBundle\Controller\BaseController;
use AppBundle\Entity\XslPolicy;
use AppBundle\Entity\XslPolicyFile;
use AppBundle\Entity\XslPolicyRule;
use AppBundle\Lib\XslPolicy\XslPolicyFormFields;
use AppBundle\Lib\XslPolicy\XslPolicyWriter;

/**
 * @Route("/")
 */
class XslPolicyController extends BaseController
{
    /**
     * Policy editor page
     *
     * @Route("/xslPolicyTree/")
     * @Template()
     */
    public function xslPolicyTreeAction(Request $request)
    {
        // Forms
        $rule = new XslPolicyRule();
        $rule->setTitle('New rule');
        $policyRuleForm = $this->createForm('xslPolicyRule', $rule);
        if ($this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            $importPolicyForm = $this->createForm('xslPolicyImport');
        }
        $policyInfoForm = $this->createForm('xslPolicyInfo');

        return array(
            'policyRuleForm' => $policyRuleForm->createView(),
            'importPolicyForm' => isset($importPolicyForm) ? $importPolicyForm->createView() : false,
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

        $policies = $this->get('mco.policy.getPolicies');
        $policies->getPolicies(array(), 'JSTREE');

        if ($policies->getResponse()->getStatus()) {
            return new JsonResponse(array('policiesTree' => $policies->getResponse()->getPolicies()), 200);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
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

        $policyCreate = $this->get('mco.policy.create');
        $policyCreate->create($parentId);

        if ($policyCreate->getResponse()->getStatus()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyCreate->getCreatedId());

            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($policyCreate->getCreatedId(), 'JSTREE');

            if ($policy->getResponse()->getStatus()) {
                return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
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

        $importPolicyForm = $this->createForm('xslPolicyImport');
        $importPolicyForm->handleRequest($request);
        if ($importPolicyForm->isValid()) {
            $data = $importPolicyForm->getData();
            if ($data['policyFile']->isValid()) {
                $policyImport = $this->get('mco.policy.import');
                $policyImport->import(file_get_contents($data['policyFile']->getRealPath()));

                if (null !== $policyImport->getCreatedId()) {
                    $policySave = $this->get('mco.policy.save');
                    $policySave->save($policyImport->getCreatedId());
                    $policy = $this->get('mco.policy.getPolicy');
                    $policy->getPolicy($policyImport->getCreatedId(), 'JSTREE');

                    if ($policy->getResponse()->getStatus()) {
                        return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
                    }
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
        $policyName = $this->get('mco.policy.getPolicyName');
        $policyName->getPolicyName($id);

        if ($policyName->getResponse()->getStatus()) {
            $policyName = $policyName->getResponse()->getName();

            $policyExport = $this->get('mco.policy.export');
            $policyExport->export($id);

            if ($policyExport->getResponse()->getStatus()) {
                $response = new Response($policyExport->getPolicyXml());
                $disposition = $this->downloadFileDisposition($response, $policyName . '.xml');

                $response->headers->set('Content-Type', 'text/xml');
                $response->headers->set('Content-Disposition', $disposition);
                $response->headers->set('Content-length', strlen($policyExport->getPolicyXml()));

                return $response;
            }
        }

        throw new ServiceUnavailableHttpException();
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
            $policyEdit = $this->get('mco.policy.edit');
            $policyEdit->edit($id, $data['policyName'], $data['policyDescription']);

            $policyEditType = $this->get('mco.policy.editType');
            $policyEditType->editType($id, $data['policyType']);

            $policySave = $this->get('mco.policy.save');
            $policySave->save($id);

            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($id, 'JSTREE');

            if ($policy->getResponse()->getStatus()) {
                return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
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

        $policyDuplicate = $this->get('mco.policy.duplicate');
        $policyDuplicate->duplicate($id, $dstPolicyId);

        if ($policyDuplicate->getResponse()->getStatus()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyDuplicate->getCreatedId());
            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($policyDuplicate->getCreatedId(), 'JSTREE');

            if ($policy->getResponse()->getStatus()) {
                return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
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

        $policyMove = $this->get('mco.policy.move');
        $policyMove->move($id, $dstPolicyId);

        if ($policyMove->getResponse()->getStatus()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyMove->getCreatedId());
            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($policyMove->getCreatedId(), 'JSTREE');

            if ($policy->getResponse()->getStatus()) {
                return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
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

        $policyDelete = $this->get('mco.policy.delete');
        $policyDelete->delete($id);
        $policySave = $this->get('mco.policy.save');
        $policySave->save($id);

        return new JsonResponse(array('policyId' => $id), 200);
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

        $ruleCreate = $this->get('mco.policy.rule.create');
        $ruleCreate->create($policyId);

        if ($ruleCreate->getResponse()->getStatus()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyId);
            $rule = $this->get('mco.policy.getRule');
            $rule->getRule($ruleCreate->getCreatedId(), $policyId);

            if ($rule->getResponse()->getStatus()) {
                return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
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

        $policyRuleForm = $this->createForm('xslPolicyRule');
        $policyRuleForm->handleRequest($request);
        if ($policyRuleForm->isValid()) {
            $data = $policyRuleForm->getData();

            $ruleEdit = $this->get('mco.policy.rule.edit');
            $ruleEdit->edit($id, $policyId, $data);
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyId);
            $rule = $this->get('mco.policy.getRule');
            $rule->getRule($id, $policyId);

            if ($rule->getResponse()->getStatus()) {
                return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);
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

        $ruleDelete = $this->get('mco.policy.rule.delete');
        $ruleDelete->delete($id, $policyId);
        $policySave = $this->get('mco.policy.save');
        $policySave->save($policyId);

        return new JsonResponse(array('id' => $id), 200);
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

        $ruleDuplicate = $this->get('mco.policy.rule.duplicate');
        $ruleDuplicate->duplicate($id, $policyId, $dstPolicyId);

        if ($ruleDuplicate->getResponse()->getStatus()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyId);
            $rule = $this->get('mco.policy.getRule');
            $rule->getRule($ruleDuplicate->getCreatedId(), $dstPolicyId);

            if ($rule->getResponse()->getStatus()) {
                return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
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

        $ruleMove = $this->get('mco.policy.rule.move');
        $ruleMove->move($id, $policyId, $dstPolicyId);

        if ($ruleMove->getResponse()->getStatus()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($dstPolicyId);
            $policySave->save($policyId);
            $rule = $this->get('mco.policy.getRule');
            $rule->getRule($ruleMove->getCreatedId(), $dstPolicyId);

            if ($rule->getResponse()->getStatus()) {
                return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);
            }
        }

        return new JsonResponse(array('message' => 'Error'), 400);
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

        $valuesList = $this->get('mco.policy.form.values');
        $valuesList->getValues($type, $field, $value);

        if ($valuesList->getResponse()->getStatus()) {
            return new JsonResponse($valuesList->getResponseAsArray());
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }
}
