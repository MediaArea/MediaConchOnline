<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/xslPolicy/")
     * @Template()
     */
    public function xslPolicyAction(Request $request)
    {
        $policyList = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findByUser($this->getUser());

        $policySystemList = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findByUser(null);

        if ($this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            $policy = new XslPolicyFile();
            $importPolicyForm = $this->createForm('xslPolicyImport', $policy);
            $importPolicyForm->handleRequest($request);
            if ($importPolicyForm->isValid()) {
                $em = $this->getDoctrine()->getManager();

                // Set user at the creation of the policy
                if (null === $policy->getUser()) {
                    $policy->setUser($this->getUser());
                }

                $em->persist($policy);
                $em->flush();

                $this->addFlashBag('success', 'Policy successfully added');

                return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicyruleedit', array('id' => $policy->getId())));
            }

            $createPolicyForm = $this->createForm('xslPolicyCreate', $policy);
            $createPolicyForm->handleRequest($request);
            if ($createPolicyForm->isValid()) {
                $em = $this->getDoctrine()->getManager();

                // Set user at the creation of the policy
                if (null === $policy->getUser()) {
                    $policy->setUser($this->getUser());
                }

                $tmpNameFile = tempnam(sys_get_temp_dir(), 'policy' . $policy->getUser()->getId());
                $tmpPolicy = new XslPolicy();
                $tmpPolicy->setTitle($policy->getPolicyName())->setDescription($policy->getPolicyDescription());
                $tmpPolicyWriter = new XslPolicyWriter();
                $tmpPolicyWriter->setPolicy($tmpPolicy)->writeXsl($tmpNameFile);
                $tmpFile = new UploadedFile($tmpNameFile, $policy->getPolicyName() . '.xsl', null, null, null, true);
                $policy->setPolicyFile($tmpFile);

                $em->persist($policy);
                $em->flush();

                $this->addFlashBag('success', 'Policy successfully added');

                return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicyruleedit', array('id' => $policy->getId())));
            }
        }

        return array('importPolicyForm' => isset($importPolicyForm) ? $importPolicyForm->createView() : false,
                     'createPolicyForm' => isset($createPolicyForm) ? $createPolicyForm->createView() : false,
                     'policyList' => $policyList,
                     'policySystemList' => $policySystemList,
                     );
    }

    /**
     * @Route("/xslPolicy/delete/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyDeleteAction($id)
    {
        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findOneBy(array('id' => $id, 'user' => $this->getUser()));

        if (!$policy) {
             $this->addFlashBag('danger', 'Policy not found');
        }
        else {
            $em = $this->getDoctrine()->getManager();
            $em->remove($policy);
            $em->flush();

            $this->addFlashBag('success', 'Policy successfully removed');
        }

        return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicy'));
    }

    /**
     * @Route("/xslPolicy/export/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyExportAction($id)
    {
        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findOneBy(array('id' => $id, 'user' => $this->getUser()));

        if (!$policy) {
            $this->addFlashBag('danger', 'Policy not found');

            return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicy'));
        }
        else {
            $handler = $this->container->get('vich_uploader.download_handler');
            return $handler->downloadObject($policy, 'policyFile');
        }
    }

    /**
     * @Route("/xslPolicy/system/export/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function xslPolicySystemExportAction($id)
    {
        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findOneBy(array('id' => $id, 'user' => null));

        if (!$policy) {
            $this->addFlashBag('danger', 'Policy not found');

            return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicy'));
        }
        else {
            $handler = $this->container->get('vich_uploader.download_handler');
            return $handler->downloadObject($policy, 'policyFile');
        }
    }

    /**
     * @Route("/xslPolicy/duplicate/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyDuplicateAction($id)
    {
        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findOneBy(array('id' => $id, 'user' => $this->getUser()));

        if (!$policy) {
            $this->addFlashBag('danger', 'Policy not found');
        }
        else {
            $duplicatePolicy = clone $policy;
            $helper = $this->container->get('vich_uploader.storage');
            $policyFile = $helper->resolvePath($policy, 'policyFile');
            $duplicatePolicyFilename = str_replace(pathinfo($policy->getPolicyFilename(), PATHINFO_FILENAME), pathinfo($policy->getPolicyFilename(), PATHINFO_FILENAME) . '_duplicate', $policy->getPolicyFilename());
            $duplicatePolicy->setPolicyFilename($duplicatePolicyFilename);
            $duplicatePolicy->setPolicyName($policy->getPolicyName() . ' - duplicate');
            $duplicatePolicyFile = str_replace($policy->getPolicyFilename(), $duplicatePolicyFilename, $policyFile);
            copy($policyFile, $duplicatePolicyFile);

            $em = $this->getDoctrine()->getManager();
            $em->persist($duplicatePolicy);
            $em->flush();

            $this->addFlashBag('success', 'Policy successfully duplicated');
        }

        return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicy'));
    }

    /**
     * @Route("/xslPolicy/edit/{id}/{ruleId}", defaults={"ruleId" = "new"}, requirements={"id": "\d+", "ruleId": "\d+"})
     * @Template()
     */
    public function xslPolicyRuleEditAction($id, $ruleId, Request $request)
    {
        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findOneBy(array('id' => $id, 'user' => $this->getUser()));

        if (!$policy) {
            $this->addFlashBag('danger', 'Policy not found');

            return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicy'));
        }

        $helper = $this->container->get('vich_uploader.storage');
        $policyFile = $helper->resolvePath($policy, 'policyFile');

        $parser = $this->get('mco.xslpolicy.parser');
        $parser->loadXsl($policyFile);

        if ('new' == $ruleId) {
            $rule = new XslPolicyRule();
        }
        else {
            if ($parser->getPolicy()->getRules()->containsKey($ruleId)) {
                $rule = $parser->getPolicy()->getRules()->get($ruleId);
                $originalRule = clone $rule;
            }
            else {
                $this->addFlashBag('danger', 'Policy rule not found');

                return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicyruleedit', array('id' => $id)));
            }
        }

        $policyRuleForm = $this->createForm('xslPolicyRule', $rule);
        $policyRuleForm->handleRequest($request);
        if ($policyRuleForm->isValid()) {
            if ('new' == $ruleId) {
                $parser->getPolicy()->getRules()->add($rule);
            }
            else if ($policyRuleForm->get('DuplicateRule')->isClicked())
            {
                if ($rule->getTitle() == $originalRule->getTitle()) {
                    $rule->setTitle($originalRule->getTitle() . ' - duplicate');
                }
                $parser->getPolicy()->getRules()->add($rule);
                $parser->getPolicy()->getRules()->set($ruleId, $originalRule);
            }

            $writer = $this->get('mco.xslpolicy.writer');
            $writer->setPolicy($parser->getPolicy());
            $writer->writeXsl($policyFile);

            $this->addFlashBag('success', 'Policy rule successfully saved');

            return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicyruleedit', array('id' => $id)));
        }
        return array('policyRuleForm' => isset($policyRuleForm) ? $policyRuleForm->createView() : false,
            'policy' => $policy,
            'xslPolicy' => $parser->getPolicy());
    }

    /**
     * @Route("/xslPolicy/deleteRule/{id}/{ruleId}", requirements={"id": "\d+", "ruleId": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyRuleDeleteAction($id, $ruleId, Request $request)
    {
        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findOneBy(array('id' => $id, 'user' => $this->getUser()));

        if (!$policy) {
            $this->addFlashBag('danger', 'Policy not found');

            return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicy'));
        }

        $helper = $this->container->get('vich_uploader.storage');
        $policyFile = $helper->resolvePath($policy, 'policyFile');

        $parser = $this->get('mco.xslpolicy.parser');
        $parser->loadXsl($policyFile);

        if ($parser->getPolicy()->getRules()->containsKey($ruleId)) {
            $parser->getPolicy()->getRules()->remove($ruleId);

            $writer = $this->get('mco.xslpolicy.writer');
            $writer->setPolicy($parser->getPolicy());
            $writer->writeXsl($policyFile);

            $this->addFlashBag('success', 'Policy rule successfully removed');
        }
        else {
            $this->addFlashBag('danger', 'Policy rule not found');
        }

        return $this->redirect($this->generateUrl('app_xslpolicy_xslpolicyruleedit', array('id' => $policy->getId())));
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
            $policy = new XslPolicyFile();
            $importPolicyForm = $this->createForm('xslPolicyImport', $policy);
            $createPolicyForm = $this->createForm('xslPolicyCreate', $policy);
        }

        return array(
            'policyRuleForm' => $policyRuleForm->createView(),
            'importPolicyForm' => isset($importPolicyForm) ? $importPolicyForm->createView() : false,
            'createPolicyForm' => isset($createPolicyForm) ? $createPolicyForm->createView() : false,
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

        $helper = $this->container->get('vich_uploader.storage');
        $parser = $this->get('mco.xslpolicy.parser');

        // System policies
        $policiesSystem = array('id' => 's_p', 'text' => 'System policies', 'type' => 'sp', 'state' => array('opened' => true, 'selected' => true), 'children' => array());
        $policyList = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findByUser(null);
        foreach ($policyList as $policy) {
            $policyFile = $helper->resolvePath($policy, 'policyFile');
            $parser->loadXsl($policyFile);
            $rules = array();
            foreach ($parser->getPolicy()->getRules() as $ruleId => $rule) {
                $rules[] = array('text' => $rule->getTitle(), 'type' => 'r', 'data' => array('ruleId' => $ruleId, 'trackType' => $rule->getTrackType(), 'field' => $rule->getField(), 'occurrence' => $rule->getOccurrence(), 'validator' => $rule->getValidator(), 'value' => $rule->getValue()));
            }
            $policiesSystem['children'][] = array('text' => $policy->getPolicyName(), 'type' => 's', 'data' => array('policyId' => $policy->getId()), 'children' => $rules);
        }

        // User policies
        $policiesUser = array('id' => 'u_p', 'text' => 'User policies', 'type' => 'up', 'state' => array('opened' => true), 'children' => array());
        $policyList = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findByUser($this->getUser());
        foreach ($policyList as $policy) {
            $policyFile = $helper->resolvePath($policy, 'policyFile');
            $parser->loadXsl($policyFile);
            $rules = array();
            foreach ($parser->getPolicy()->getRules() as $ruleId => $rule) {
                $rules[] = array('text' => $rule->getTitle(), 'type' => 'r', 'data' => array('ruleId' => $ruleId, 'trackType' => $rule->getTrackType(), 'field' => $rule->getField(), 'occurrence' => $rule->getOccurrence(), 'validator' => $rule->getValidator(), 'value' => $rule->getValue()));
            }
            $policiesUser['children'][] = array('text' => $policy->getPolicyName(), 'type' => 'u', 'data' => array('policyId' => $policy->getId()), 'children' => $rules);
        }

        return new JsonResponse(array('policiesTree' => array($policiesSystem, $policiesUser)), 200);
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
     * @Route("/xslPolicyTree/ajax/create")
     */
    public function xslPolicyTreeCreateAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        if ($this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            $policy = new XslPolicyFile();
            $createPolicyForm = $this->createForm('xslPolicyCreate', $policy);
            $createPolicyForm->handleRequest($request);
            if ($createPolicyForm->isValid()) {
                $em = $this->getDoctrine()->getManager();

                // Set user at the creation of the policy
                if (null === $policy->getUser()) {
                    $policy->setUser($this->getUser());
                }

                $tmpNameFile = tempnam(sys_get_temp_dir(), 'policy' . $policy->getUser()->getId());
                $tmpPolicy = new XslPolicy();
                $tmpPolicy->setTitle($policy->getPolicyName())->setDescription($policy->getPolicyDescription());
                $tmpPolicyWriter = new XslPolicyWriter();
                $tmpPolicyWriter->setPolicy($tmpPolicy)->writeXsl($tmpNameFile);
                $tmpFile = new UploadedFile($tmpNameFile, $policy->getPolicyName() . '.xsl', null, null, null, true);
                $policy->setPolicyFile($tmpFile);

                $em->persist($policy);
                $em->flush();

                return new JsonResponse(array('policyId' => $policy->getId(), 'policyName' => $policy->getPolicyName()), 200);
            }
            else {
                return new JsonResponse(array('message' => 'Error'), 400);
            }
        }
        else {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }
    }

    /**
     * @Route("/xslPolicyTree/ajax/import")
     */
    public function xslPolicyTreeImportAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        if ($this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            $policy = new XslPolicyFile();
            $importPolicyForm = $this->createForm('xslPolicyImport', $policy);
            $importPolicyForm->handleRequest($request);
            if ($importPolicyForm->isValid()) {
                $em = $this->getDoctrine()->getManager();

                // Set user at the creation of the policy
                if (null === $policy->getUser()) {
                    $policy->setUser($this->getUser());
                }

                $em->persist($policy);
                $em->flush();

                $helper = $this->container->get('vich_uploader.storage');
                $parser = $this->get('mco.xslpolicy.parser');
                $policyFile = $helper->resolvePath($policy, 'policyFile');
                $parser->loadXsl($policyFile);
                $rules = array();
                foreach ($parser->getPolicy()->getRules() as $ruleId => $rule) {
                    $rules[] = array('text' => $rule->getTitle(), 'type' => 'r', 'data' => array('ruleId' => $ruleId, 'trackType' => $rule->getTrackType(), 'field' => $rule->getField(), 'occurrence' => $rule->getOccurrence(), 'validator' => $rule->getValidator(), 'value' => $rule->getValue()));
                }

                return new JsonResponse(array('policyId' => $policy->getId(), 'policyName' => $policy->getPolicyName(), 'policyRules' => $rules), 200);
            }
            else {
                return new JsonResponse(array('message' => 'Error'), 400);
            }
        }
        else {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }
    }

    /**
     * @Route("/xslPolicyTree/ajax/duplicate/{id}", requirements={"id": "\d+"})
     * @Method("GET")
     */
    public function xslPolicyTreeDuplicateAction(Request $request, $id)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundHttpException();
        }

        if ($this->get('mediaconch_user.quotas')->hasPolicyCreationRights()) {
            $policy = $this->getDoctrine()
                ->getRepository('AppBundle:XslPolicyFile')
                ->findOneByUserOrSystem($id, $this->getUser());

            if (!$policy) {
                return new JsonResponse(array('message' => 'Policy not found'), 400);
            }

            $duplicatePolicy = clone $policy;
            $helper = $this->container->get('vich_uploader.storage');
            $policyFile = $helper->resolvePath($policy, 'policyFile');
            $duplicatePolicyFilename = str_replace(pathinfo($policy->getPolicyFilename(), PATHINFO_FILENAME), pathinfo($policy->getPolicyFilename(), PATHINFO_FILENAME) . '_duplicate', $policy->getPolicyFilename());
            $duplicatePolicy->setPolicyFilename($duplicatePolicyFilename);
            $duplicatePolicy->setPolicyName($policy->getPolicyName() . ' - duplicate');
            $duplicatePolicy->setUser($this->getUser());
            $duplicatePolicyFile = str_replace($policy->getPolicyFilename(), $this->getUser()->getId() . '/' . $duplicatePolicyFilename, $policyFile);
            copy($policyFile, $duplicatePolicyFile);

            $em = $this->getDoctrine()->getManager();
            $em->persist($duplicatePolicy);
            $em->flush();

            $helper = $this->container->get('vich_uploader.storage');
            $parser = $this->get('mco.xslpolicy.parser');
            $policyFile = $helper->resolvePath($duplicatePolicy, 'policyFile');
            $parser->loadXsl($policyFile);
            $rules = array();
            foreach ($parser->getPolicy()->getRules() as $ruleId => $rule) {
                $rules[] = array('text' => $rule->getTitle(), 'type' => 'r', 'data' => array('ruleId' => $ruleId, 'trackType' => $rule->getTrackType(), 'field' => $rule->getField(), 'occurrence' => $rule->getOccurrence(), 'validator' => $rule->getValidator(), 'value' => $rule->getValue()));
            }

            return new JsonResponse(array('policyId' => $duplicatePolicy->getId(), 'policyName' => $duplicatePolicy->getPolicyName(), 'policyRules' => $rules), 200);
        }
        else {
            return new JsonResponse(array('message' => 'Quota exceeded', 'quota' => $this->renderView('AppBundle:Default:quotaExceeded.html.twig')), 400);
        }
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

        $policy = $this->getDoctrine()
            ->getRepository('AppBundle:XslPolicyFile')
            ->findOneBy(array('id' => $id, 'user' => $this->getUser()));

        if (!$policy) {
            return new JsonResponse(array('message' => 'Policy not found'), 400);
        }

        $policyId = $policy->getId();
        $em = $this->getDoctrine()->getManager();
        $em->remove($policy);
        $em->flush();

        return new JsonResponse(array('policyId' => $policyId), 200);
    }

    /**
     * @Route("/xslPolicyTree/ajax/edit/{id}/{action}/{ruleId}", defaults={"ruleId" = "new"}, requirements={"id": "\d+", "ruleId": "\d+", "action": "[a-z]+"})
     * @Method("POST")
     */
    public function xslPolicyTreeRuleEditAction($id, $action, $ruleId, Request $request)
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

        $helper = $this->container->get('vich_uploader.storage');
        $policyFile = $helper->resolvePath($policy, 'policyFile');

        $parser = $this->get('mco.xslpolicy.parser');
        $parser->loadXsl($policyFile);

        if ('new' == $ruleId) {
            $action = 'create';
        }

        if ('create' == $action) {
            $rule = new XslPolicyRule();
        }
        else {
            if ($parser->getPolicy()->getRules()->containsKey($ruleId)) {
                $rule = $parser->getPolicy()->getRules()->get($ruleId);
                if ('duplicate' == $action) {
                    $originalRule = clone $rule;
                }
            }
            else {
                return new JsonResponse(array('message' => 'Policy rule not found'), 400);
            }
        }

        $policyRuleForm = $this->createForm('xslPolicyRule', $rule);
        $policyRuleForm->handleRequest($request);
        if ($policyRuleForm->isValid()) {
            if ('create' == $action) {
                $parser->getPolicy()->getRules()->add($rule);
            }
            else if ('duplicate' == $action) {
                if ($rule->getTitle() == $originalRule->getTitle()) {
                    $rule->setTitle($originalRule->getTitle() . ' - duplicate');
                }
                $parser->getPolicy()->getRules()->add($rule);
                $parser->getPolicy()->getRules()->set($ruleId, $originalRule);
            }

            $writer = $this->get('mco.xslpolicy.writer');
            $writer->setPolicy($parser->getPolicy());
            $writer->writeXsl($policyFile);

            if ('create' == $action) {
                $ruleTree = array('text' => $rule->getTitle(), 'type' => 'r', 'data' => array('ruleId' => ($parser->getPolicy()->getRules()->count() - 1), 'trackType' => $rule->getTrackType(), 'field' => $rule->getField(), 'occurrence' => $rule->getOccurrence(), 'validator' => $rule->getValidator(), 'value' => $rule->getValue()));

                return new JsonResponse(array('policyId' => $id, 'rule' => $ruleTree), 200);
            }
            else if ('duplicate' == $action) {
                $ruleTree = array('text' => $rule->getTitle(), 'type' => 'r', 'data' => array('ruleId' => ($parser->getPolicy()->getRules()->count() - 1), 'trackType' => $rule->getTrackType(), 'field' => $rule->getField(), 'occurrence' => $rule->getOccurrence(), 'validator' => $rule->getValidator(), 'value' => $rule->getValue()));

                return new JsonResponse(array('policyId' => $id, 'rule' => $ruleTree), 200);
            }
            else {
                $ruleTree = array('text' => $rule->getTitle(), 'type' => 'r', 'data' => array('ruleId' => $ruleId, 'trackType' => $rule->getTrackType(), 'field' => $rule->getField(), 'occurrence' => $rule->getOccurrence(), 'validator' => $rule->getValidator(), 'value' => $rule->getValue()));

                return new JsonResponse(array('policyId' => $id, 'rule' => $ruleTree), 200);
            }
        }
        else {
            return new JsonResponse(array('message' => 'Error'), 400);
        }
    }

    /**
     * @Route("/xslPolicyTree/ajax/deleteRule/{id}/{ruleId}", requirements={"id": "\d+", "ruleId": "\d+"})
     * @Method("POST")
     */
    public function xslPolicyTreeRuleDeleteAction($id, $ruleId, Request $request)
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

        $helper = $this->container->get('vich_uploader.storage');
        $policyFile = $helper->resolvePath($policy, 'policyFile');

        $parser = $this->get('mco.xslpolicy.parser');
        $parser->loadXsl($policyFile);

        if ($parser->getPolicy()->getRules()->containsKey($ruleId)) {
            $parser->getPolicy()->getRules()->remove($ruleId);

            $writer = $this->get('mco.xslpolicy.writer');
            $writer->setPolicy($parser->getPolicy());
            $writer->writeXsl($policyFile);

            return new JsonResponse(array('policyId' => $id), 200);
        }
        else {
            return new JsonResponse(array('message' => 'Policy rule not found'), 400);
        }
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
