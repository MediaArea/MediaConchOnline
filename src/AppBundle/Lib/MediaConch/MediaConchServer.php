<?php

namespace AppBundle\Lib\MediaConch;

class MediaConchServer
{
    public function __construct($address, $port, $apiVersion)
    {
        $this->address = $address;
        $this->port = $port;
        $this->apiVersion = $apiVersion;
    }

    public function analyse($file)
    {
        $request = array('CHECKER_ANALYZE' => array('args' => array(array('id' => 0, 'file' => $file))));
        $response = $this->callApi('checker_analyze', 'POST', json_encode($request));
        $response = $response->CHECKER_ANALYZE_RESULT;

        return new AnalyzeResponse($response);
    }

    public function status($id)
    {
        $request = array('id' => $id);
        $response = $this->callApi('checker_status', 'GET', $request);
        $response = $response->CHECKER_STATUS_RESULT;

        return new StatusResponse($response);
    }

    public function report($user, $id, $report, $displayName, $display = null, $policy = null, $verbosity = -1)
    {
        $request = array('CHECKER_REPORT' => array('user' => $user, 'ids' => array((int) $id), 'reports' => array($report), 'verbosity' => (int) $verbosity));
        if ($display && file_exists($display) && is_readable($display)) {
            $request['CHECKER_REPORT']['display_content'] = file_get_contents($display);
        }
        else {
            $request['CHECKER_REPORT']['display_name'] = $displayName;
        }

        if (null !== $policy) {
            $request['CHECKER_REPORT']['policies_ids'] = array((int) $policy);
        }

        $response = $this->callApi('checker_report', 'POST', json_encode($request));
        $response = $response->CHECKER_REPORT_RESULT;

        return new ReportResponse($response);
    }

    public function validate($user, $id, $report, $policy = null)
    {
        $request = array('CHECKER_VALIDATE' => array('user' => $user, 'ids' => array((int) $id), 'report' => $report));
        if (null !== $policy) {
            $request['CHECKER_VALIDATE']['policies_ids'] = array((int) $policy);
        }
        $response = $this->callApi('checker_validate', 'POST', json_encode($request));
        $response = $response->CHECKER_VALIDATE_RESULT;

        return new ValidateResponse($response);
    }

    public function fileFromId($id)
    {
        $request = array('CHECKER_FILE_FROM_ID' => array('id' => (int) $id));

        $response = $this->callApi('checker_file_from_id', 'POST', json_encode($request));
        $response = $response->CHECKER_FILE_FROM_ID_RESULT;

        return new FileFromIdResponse($response);
    }

    public function policyFromFile($user, $id)
    {
        $request = array('user' => $user, 'id' => $id);
        $response = $this->callApi('xslt_policy_create_from_file', 'GET', $request);
        $response = $response->XSLT_POLICY_CREATE_FROM_FILE_RESULT;

        return new PolicyFromFileResponse($response);
    }

    public function valuesFromType($trackType, $field)
    {
        $request = array('type' => $trackType, 'field' => $field);
        $response = $this->callApi('default_values_for_type', 'GET', $request);
        $response = $response->DEFAULT_VALUES_FOR_TYPE_RESULT;

        return new ValuesFromTypeResponse($response);
    }

    public function policyGetPolicy($user, $id, $format)
    {
        $request = array('user' => $user, 'id' => $id, 'format' => $format);
        $response = $this->callApi('policy_get', 'GET', $request);
        $response = $response->POLICY_GET_RESULT;

        return new PolicyGetPolicyResponse($response);
    }

    public function policyGetPolicies($user, $ids, $format)
    {
        $request = array('user' => $user, 'format' => $format);
        if (count($ids) > 0) {
            $request['id'] = $ids;
        }
        $response = $this->callApi('policy_get_policies', 'GET', $request);
        $response = $response->POLICY_GET_POLICIES_RESULT;

        return new PolicyGetPoliciesResponse($response);
    }

    public function policyGetPoliciesNamesList($user)
    {
        $request = array('user' => $user);
        $response = $this->callApi('policy_get_policies_names_list', 'GET', $request);
        $response = $response->POLICY_GET_POLICIES_NAMES_LIST_RESULT;

        return new PolicyGetPoliciesNamesListResponse($response);
    }

    public function policyCreate($user, $type, $parentId)
    {
        $request = array('user' => $user);
        if ($type) {
            $request['type'] = $type;
        }

        if ($parentId) {
            $request['parent_id'] = $parentId;
        }
        $response = $this->callApi('xslt_policy_create', 'GET', $request);
        $response = $response->XSLT_POLICY_CREATE_RESULT;

        return new PolicyCreateResponse($response);
    }

    public function policySave($user, $policyId)
    {
        $request = array('user' => $user, 'id' => $policyId);
        $response = $this->callApi('policy_save', 'GET', $request);
        $response = $response->POLICY_SAVE_RESULT;

        return new PolicySaveResponse($response);
    }

    public function policyImport($user, $xml)
    {
        $request = array('POLICY_IMPORT' => array('user' => $user, 'xml' => $xml));
        $response = $this->callApi('policy_import', 'POST', json_encode($request));
        $response = $response->POLICY_IMPORT_RESULT;

        return new PolicyImportResponse($response);
    }

    public function policyExport($user, $policyId)
    {
        $request = array('user' => $user, 'id' => $policyId);
        $response = $this->callApi('policy_dump', 'GET', $request);
        $response = $response->POLICY_DUMP_RESULT;

        return new PolicyExportResponse($response);
    }

    public function policyEdit($user, $policyId, $name, $description)
    {
        $request = array('POLICY_CHANGE_INFO' => array('user' => $user, 'id' => (int) $policyId, 'name' => null == $name ? '' : $name, 'description' => null == $description ? '' : $description));
        $response = $this->callApi('policy_change_info', 'POST', json_encode($request));
        $response = $response->POLICY_CHANGE_INFO_RESULT;

        return new PolicyEditResponse($response);
    }

    public function policyDelete($user, $policyId)
    {
        $request = array('user' => $user, 'id' => $policyId);
        $response = $this->callApi('policy_remove', 'GET', $request);
        $response = $response->POLICY_REMOVE_RESULT;

        return new PolicyDeleteResponse($response);
    }

    public function policyDuplicate($user, $policyId)
    {
        $request = array('user' => $user, 'id' => $policyId);
        $response = $this->callApi('policy_duplicate', 'GET', $request);
        $response = $response->POLICY_DUPLICATE_RESULT;

        return new PolicyDuplicateResponse($response);
    }

    public function policyRuleCreate($user, $policyId)
    {
        $request = array('user' => $user, 'policy_id' => (int) $policyId);
        $response = $this->callApi('xslt_policy_rule_create', 'GET', $request);
        $response = $response->XSLT_POLICY_RULE_CREATE_RESULT;

        return new PolicyRuleCreateResponse($response);
    }

    public function policyRuleEdit($user, $ruleData, $policyId)
    {
        $request = array('XSLT_POLICY_RULE_EDIT' => array('user' => $user, 'policy_id' => (int) $policyId, 'rule' => $ruleData));
        $response = $this->callApi('xslt_policy_rule_edit', 'POST', json_encode($request));
        $response = $response->XSLT_POLICY_RULE_EDIT_RESULT;

        return new PolicyRuleEditResponse($response);
    }

    public function policyRuleDelete($user, $ruleId, $policyId)
    {
        $request = array('user' => $user, 'policy_id' => (int) $policyId, 'id' => (int) $ruleId);
        $response = $this->callApi('xslt_policy_rule_delete', 'GET', $request);
        $response = $response->XSLT_POLICY_RULE_DELETE_RESULT;

        return new PolicyRuleDeleteResponse($response);
    }

    public function policyRuleDuplicate($user, $ruleId, $policyId)
    {
        $request = array('user' => $user, 'policy_id' => (int) $policyId, 'id' => (int) $ruleId);
        $response = $this->callApi('xslt_policy_rule_duplicate', 'GET', $request);
        $response = $response->XSLT_POLICY_RULE_DUPLICATE_RESULT;

        return new PolicyRuleDuplicateResponse($response);
    }

    public function policyGetRule($user, $ruleId, $policyId)
    {
        $request = array('user' => $user, 'policy_id' => (int) $policyId, 'id' => (int) $ruleId);
        $response = $this->callApi('xslt_policy_rule_get', 'GET', $request);
        $response = $response->XSLT_POLICY_RULE_GET_RESULT;

        return new PolicyGetRuleResponse($response);
    }

    protected function callApi($uri, $method, $params)
    {
        $url = 'http://' . $this->address . '/' . $this->apiVersion . '/' . $uri;

        if ('GET' == $method && in_array($uri, array('checker_status', 'policy_get_policies')) && isset($params['id']) && is_array($params['id'])) {
            $url .= '?id=' . implode('&id=', $params['id']);
            unset($params['id']);
            if (count($params) > 0) {
                $url .= '&' . http_build_query($params);
            }
        }
        else if ('GET' == $method && is_array($params)) {
            $url .= '?' . http_build_query($params);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_PORT, $this->port);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ('POST' == $method) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        }
        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);
        curl_close($curl);

        if (isset($headers['http_code']) && $headers['http_code'] == 200) {
            return json_decode($response);
        }
        else {
            throw new \Exception('Return code: ' . $headers['http_code'] . ' - Response: ' . $response);
        }

    }
}
