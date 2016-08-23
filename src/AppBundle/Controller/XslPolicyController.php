<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * @Route("/xslPolicyTree/ajax/data")
     */
    public function xslPolicyTreeDataAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $policies = $this->get('mco.policy.getPolicies');
        $policies->getPolicies(array(), 'JSTREE');

        return new JsonResponse(array('policiesTree' => $policies->getResponse()->getPolicies()), 200);
    }

    /**
     * @Route("/xslPolicyTree/ajax/check/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeCheckAction($id, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findOneBy(array('id' => $id, 'user' => $this->getUser()));

        if (!$policy) {
            return new JsonResponse(array('message' => 'Policy not found'), 400);
        }

        return new JsonResponse(array('policyId' => $id), 200);
    }

    /**
     * @Route("/xslPolicyTree/ajax/create/{parentId}/{topLevelId}", requirements={"parentId": "(-)?\d+", "topLevelId": "(-)?\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeCreateAction($parentId, $topLevelId, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $policyCreate = $this->get('mco.policy.create');
        $policyCreate->create($parentId);

        if (null !== $policyCreate->getCreatedId()) {
            $policySave = $this->get('mco.policy.save');
            if (-1 == $topLevelId) {
                $policySave->save($policyCreate->getCreatedId());
            }
            else {
                $policySave->save($topLevelId);
            }

            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($policyCreate->getCreatedId(), 'JSTREE');

            return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy(), 'parentId' => $parentId), 200);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * @Route("/xslPolicyTree/ajax/import")
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

                    return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
                }
            }

        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }
    /**
     * @Route("/xslPolicyTree/export/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeExportAction($id)
    {
        $policyName = $this->get('mco.policy.getPolicyName');
        $policyName->getPolicyName($id);
        $policyName = $policyName->getResponse()->getName();

        $policyExport = $this->get('mco.policy.export');
        $policyExport->export($id);

        $response = new Response($policyExport->getPolicyXml());
        $d = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $policyName . '.xml'
        );

        $response->headers->set('Content-Type', 'text/xml');
        $response->headers->set('Content-Disposition', $d);
        $response->headers->set('Content-length', strlen($policyExport->getPolicyXml()));

        return $response;
    }


    /**
     * @Route("/xslPolicyTree/ajax/edit/{id}/{topLevelId}", requirements={"id": "\d+", "topLevelId": "\d+"})
     */
    public function xslPolicyTreeEditAction(Request $request, $id, $topLevelId)
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
            $policySave->save($topLevelId);

            return new JsonResponse(array('policyId' => $id, 'policyName' => $data['policyName'], 'policyDescription' => $data['policyDescription'], 'policyType' => $data['policyType']), 200);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
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

        if (null !== $policyDuplicate->getCreatedId()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyDuplicate->getCreatedId());
            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($policyDuplicate->getCreatedId(), 'JSTREE');

            return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * @Route("/xslPolicyTree/ajax/duplicate/{id}/{dstPolicyId}", requirements={"id": "\d+", "dstPolicyId": "(-)?\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeMoveAction(Request $request, $id, $dstPolicyId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $policyMove = $this->get('mco.policy.move');
        $policyMove->move($id, $dstPolicyId);

        if (null !== $policyMove->getCreatedId()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($policyMove->getCreatedId());
            $policySave->save($id);
            $policy = $this->get('mco.policy.getPolicy');
            $policy->getPolicy($policyMove->getCreatedId(), 'JSTREE');

            return new JsonResponse(array('policy' => $policy->getResponse()->getPolicy()), 200);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
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
     * @Route("/xslPolicyTree/ajax/ruleCreate/{policyId}/{topLevelId}", requirements={"policyId": "\d+", "topLevelId": "\d+"})
     */
    public function xslPolicyTreeRuleCreateAction(Request $request, $policyId, $topLevelId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $ruleCreate = $this->get('mco.policy.rule.create');
        $ruleCreate->create($policyId);

        if (null !== $ruleCreate->getCreatedId()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($topLevelId);
            $rule = $this->get('mco.policy.getRule');
            $rule->getRule($ruleCreate->getCreatedId(), $policyId);

            return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * @Route("/xslPolicyTree/ajax/ruleEdit/{id}/{policyId}/{topLevelId}", requirements={"id": "\d+", "policyId": "\d+", "topLevelId": "\d+"})
     */
    public function xslPolicyTreeRuleEditAction(Request $request, $id, $policyId, $topLevelId)
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
            $policySave->save($topLevelId);

            return new JsonResponse(array('policyId' => $id, 'rule' => $data), 200);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * @Route("/xslPolicyTree/ajax/ruleDelete/{id}/{policyId}/{topLevelId}", requirements={"id": "\d+", "policyId": "\d+", "topLevelId": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeRuleDeleteAction(Request $request, $id, $policyId, $topLevelId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $ruleDelete = $this->get('mco.policy.rule.delete');
        $ruleDelete->delete($id, $policyId);
        $policySave = $this->get('mco.policy.save');
        $policySave->save($topLevelId);

        return new JsonResponse(array('id' => $id), 200);
    }

    /**
     * @Route("/xslPolicyTree/ajax/ruleDuplicate/{id}/{policyId}/{topLevelId}/{dstPolicyId}", requirements={"id": "\d+", "policyId": "\d+", "topLevelId": "\d+", "dstPolicyId": "(-)?\d+"})
     */
    public function xslPolicyTreeRuleDuplicateAction(Request $request, $id, $policyId, $topLevelId, $dstPolicyId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $ruleDuplicate = $this->get('mco.policy.rule.duplicate');
        $ruleDuplicate->duplicate($id, $policyId, $dstPolicyId);

        if (null !== $ruleDuplicate->getCreatedId()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($topLevelId);
            $rule = $this->get('mco.policy.getRule');
            $rule->getRule($ruleDuplicate->getCreatedId(), $dstPolicyId);

            return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
     * @Route("/xslPolicyTree/ajax/ruleDuplicate/{id}/{policyId}/{dstPolicyId}", requirements={"id": "\d+", "policyId": "\d+", "dstPolicyId": "(-)?\d+"})
     */
    public function xslPolicyTreeRuleMoveAction(Request $request, $id, $policyId, $dstPolicyId)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        $ruleMove = $this->get('mco.policy.rule.move');
        $ruleMove->move($id, $policyId, $dstPolicyId);

        if (null !== $ruleMove->getCreatedId()) {
            $policySave = $this->get('mco.policy.save');
            $policySave->save($dstPolicyId);
            $policySave->save($policyId);
            $rule = $this->get('mco.policy.getRule');
            $rule->getRule($ruleMove->getCreatedId(), $dstPolicyId);

            return new JsonResponse(array('rule' => $rule->getResponse()->getRule()), 200);
        }

        return new JsonResponse(array('message' => 'Error'), 400);
    }

    /**
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

        return new JsonResponse($valuesList->getResponseAsArray());
    }
}
