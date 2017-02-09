<?php

namespace AppBundle\Lib\MediaConch;

use Monolog\Logger;
use Symfony\Component\HttpKernel\Exception\HttpException;

use AppBundle\Lib\MediaConch\PolicyGetPoliciesResponse;
use AppBundle\Lib\Settings\SettingsManager;

class MediaConchServer
{
    protected $address;
    protected $port;
    protected $apiVersion;
    protected $logger;
    protected $userSettings;

    public function __construct($address, $port, $apiVersion, Logger $logger, SettingsManager $userSettings)
    {
        $this->address = $address;
        $this->port = $port;
        $this->apiVersion = $apiVersion;
        $this->logger = $logger;
        $this->userSettings = $userSettings;
    }

    public function analyse($user, $files, $force)
    {
        $args = array();
        foreach ($files as $key => $file) {
            $args[] = array(
                'user' => (int) $user,
                'id' => $key,
                'file' => $file,
                'force' => (bool) $force,
            );
        }

        $request = array('CHECKER_ANALYZE' => array('args' => $args));

        return $this->callApiHandler('checker_analyze', 'POST', json_encode($request), 'CHECKER_ANALYZE_RESULT', 'AnalyzeResponse');
    }

    public function status($user, $id)
    {
        $request = array('user' => (int) $user, 'id' => $id);

        return $this->callApiHandler('checker_status', 'GET', $request, 'CHECKER_STATUS_RESULT', 'StatusResponse');
    }

    public function report($user, $id, $report, $displayName, $display = null, $policy = null, $verbosity = -1)
    {
        $request = array('CHECKER_REPORT' => array('user' => (int) $user,
            'ids' => array((int) $id),
            'reports' => array($report),
            'options' => array('verbosity' => $verbosity)));
        if ($display && file_exists($display) && is_readable($display)) {
            $request['CHECKER_REPORT']['display_content'] = file_get_contents($display);
        }
        else {
            $request['CHECKER_REPORT']['display_name'] = $displayName;
        }

        if (null !== $policy) {
            $request['CHECKER_REPORT']['policies_ids'] = array((int) $policy);
        }

        return $this->callApiHandler('checker_report', 'POST', json_encode($request), 'CHECKER_REPORT_RESULT', 'ReportResponse');
    }

    public function validate($user, $id, $report, $policy = null)
    {
        $request = array('CHECKER_VALIDATE' => array('user' => (int) $user, 'ids' => array((int) $id), 'report' => $report));
        if (null !== $policy) {
            $request['CHECKER_VALIDATE']['policies_ids'] = array((int) $policy);
        }

        return $this->callApiHandler('checker_validate', 'POST', json_encode($request), 'CHECKER_VALIDATE_RESULT', 'ValidateResponse');
    }

    public function fileFromId($user, $id)
    {
        $request = array('CHECKER_FILE_FROM_ID' => array('user' => (int) $user, 'id' => (int) $id));

        return $this->callApiHandler('checker_file_from_id', 'POST', json_encode($request), 'CHECKER_FILE_FROM_ID_RESULT', 'FileFromIdResponse');
    }

    public function policyFromFile($user, $id)
    {
        $request = array('user' => (int) $user, 'id' => (int) $id);

        return $this->callApiHandler('xslt_policy_create_from_file', 'GET', $request, 'XSLT_POLICY_CREATE_FROM_FILE_RESULT', 'PolicyFromFileResponse');
    }

    public function valuesFromType($trackType, $field)
    {
        $request = array('type' => $trackType, 'field' => $field);

        return $this->callApiHandler('default_values_for_type', 'GET', $request, 'DEFAULT_VALUES_FOR_TYPE_RESULT', 'ValuesFromTypeResponse');
    }

    public function policyGetPolicy($user, $id, $format, $mustBePublic = false)
    {
        $request = array('user' => (int) $user, 'id' => (int) $id, 'format' => $format);

        if ($mustBePublic) {
            $request['must_be_public'] = 'true';
        }

        return $this->callApiHandler('policy_get', 'GET', $request, 'POLICY_GET_RESULT', 'PolicyGetPolicyResponse');
    }

    public function policyGetPolicies($user, $ids, $format)
    {
        $request = array('user' => (int) $user, 'format' => $format);
        if (count($ids) > 0) {
            $request['id'] = $ids;
        }

        return $this->callApiHandler('policy_get_policies', 'GET', $request, 'POLICY_GET_POLICIES_RESULT', 'PolicyGetPoliciesResponse');
    }

    public function policyGetPoliciesCount($user)
    {
        $request = array('user' => (int) $user);

        return $this->callApiHandler('policy_get_policies_count', 'GET', $request, 'POLICY_GET_POLICIES_COUNT_RESULT', 'PolicyGetPoliciesCountResponse');
    }

    public function policyGetPolicyName($user, $id)
    {
        $request = array('user' => (int) $user, 'id' => (int) $id);

        return $this->callApiHandler('policy_get_name', 'GET', $request, 'POLICY_GET_NAME_RESULT', 'PolicyGetPolicyNameResponse');
    }

    public function policyGetPoliciesNamesList($user)
    {
        $request = array('user' => (int) $user);

        return $this->callApiHandler('policy_get_policies_names_list', 'GET', $request, 'POLICY_GET_POLICIES_NAMES_LIST_RESULT', 'PolicyGetPoliciesNamesListResponse');
    }

    public function policyGetPublicPolicies() {
        $request = array();

        return $this->callApiHandler('policy_get_public_policies', 'GET', $request, 'POLICY_GET_PUBLIC_POLICIES_RESULT', 'PolicyGetPublicPoliciesResponse');
    }

    public function policyCreate($user, $parentId, $type)
    {
        $request = array('user' => (int) $user, 'parent_id' => (int) $parentId);
        if (null !== $type) {
            $request['type'] = $type;
        }

        return $this->callApiHandler('xslt_policy_create', 'GET', $request, 'XSLT_POLICY_CREATE_RESULT', 'PolicyCreateResponse');
    }

    public function policySave($user, $policyId)
    {
        $request = array('user' => (int) $user, 'id' => (int) $policyId);

        return $this->callApiHandler('policy_save', 'GET', $request, 'POLICY_SAVE_RESULT', 'PolicySaveResponse');
    }

    public function policyImport($user, $xml)
    {
        $request = array('POLICY_IMPORT' => array('user' => (int) $user, 'xml' => $xml));

        return $this->callApiHandler('policy_import', 'POST', json_encode($request), 'POLICY_IMPORT_RESULT', 'PolicyImportResponse');
    }

    public function policyExport($user, $policyId, $mustBePublic = false)
    {
        $request = array('user' => (int) $user, 'id' => (int) $policyId);

        if ($mustBePublic) {
            $request['must_be_public'] = 'true';
        }

        return $this->callApiHandler('policy_dump', 'GET', $request, 'POLICY_DUMP_RESULT', 'PolicyExportResponse');
    }

    public function policyEdit($user, $policyId, $name, $description, $license)
    {
        $request = array('POLICY_CHANGE_INFO' => array('user' => (int) $user,
            'id' => (int) $policyId,
            'name' => null == $name ? '' : $name,
            'description' => null == $description ? '' : $description,
            'license' => $license));

        return $this->callApiHandler('policy_change_info', 'POST', json_encode($request), 'POLICY_CHANGE_INFO_RESULT', 'PolicyEditResponse');
    }

    public function policyEditType($user, $policyId, $type)
    {
        $request = array('POLICY_CHANGE_TYPE' => array('user' => (int) $user, 'id' => (int) $policyId, 'type' => $type));

        return $this->callApiHandler('policy_change_type', 'POST', json_encode($request), 'POLICY_CHANGE_TYPE_RESULT', 'PolicyEditTypeResponse');
    }

    public function policyEditVisibility($user, $policyId, $visibility)
    {
        $request = array('POLICY_CHANGE_IS_PUBLIC' => array('user' => (int) $user, 'id' => (int) $policyId, 'is_public' => (bool) $visibility));

        return $this->callApiHandler('policy_change_is_public', 'POST', json_encode($request), 'POLICY_CHANGE_IS_PUBLIC_RESULT', 'PolicyEditVisibilityResponse');
    }

    public function policyDelete($user, $policyId)
    {
        $request = array('user' => (int) $user, 'id' => (int) $policyId);

        return $this->callApiHandler('policy_remove', 'GET', $request, 'POLICY_REMOVE_RESULT', 'PolicyDeleteResponse');
    }

    public function policyDuplicate($user, $policyId, $dstPolicyId, $dstUser = null, $mustBePublic = false)
    {
        $request = array('user' => (int) $user, 'id' => (int) $policyId, 'dst_policy_id' => (int) $dstPolicyId);

        if (null !== $dstUser) {
            $request['dst_user'] = (int) $dstUser;
        }

        if (false !== $mustBePublic) {
            $request['must_be_public'] = 'true';
        }

        return $this->callApiHandler('policy_duplicate', 'GET', $request, 'POLICY_DUPLICATE_RESULT', 'PolicyDuplicateResponse');
    }

    public function policyMove($user, $policyId, $dstPolicyId)
    {
        $request = array('user' => (int) $user, 'id' => (int) $policyId, 'dst_policy_id' => (int) $dstPolicyId);

        return $this->callApiHandler('policy_move', 'GET', $request, 'POLICY_MOVE_RESULT', 'PolicyMoveResponse');
    }

    public function policyRuleCreate($user, $policyId)
    {
        $request = array('user' => (int) $user, 'policy_id' => (int) $policyId);

        return $this->callApiHandler('xslt_policy_rule_create', 'GET', $request, 'XSLT_POLICY_RULE_CREATE_RESULT', 'PolicyRuleCreateResponse');
    }

    public function policyRuleEdit($user, $ruleData, $policyId)
    {
        $request = array('XSLT_POLICY_RULE_EDIT' => array('user' => (int) $user,
            'policy_id' => (int) $policyId,
            'rule' => $ruleData));

        return $this->callApiHandler('xslt_policy_rule_edit', 'POST', json_encode($request), 'XSLT_POLICY_RULE_EDIT_RESULT', 'PolicyRuleEditResponse');
    }

    public function policyRuleDelete($user, $ruleId, $policyId)
    {
        $request = array('user' => (int) $user, 'policy_id' => (int) $policyId, 'id' => (int) $ruleId);

        return $this->callApiHandler('xslt_policy_rule_delete', 'GET', $request, 'XSLT_POLICY_RULE_DELETE_RESULT', 'PolicyRuleDeleteResponse');
    }

    public function policyRuleDuplicate($user, $ruleId, $policyId, $dstPolicyId)
    {
        $request = array('user' => (int) $user,
            'policy_id' => (int) $policyId,
            'id' => (int) $ruleId,
            'dst_policy_id' => (int) $dstPolicyId);

        return $this->callApiHandler('xslt_policy_rule_duplicate', 'GET', $request, 'XSLT_POLICY_RULE_DUPLICATE_RESULT', 'PolicyRuleDuplicateResponse');
    }

    public function policyRuleMove($user, $ruleId, $policyId, $dstPolicyId)
    {
        $request = array('user' => (int) $user,
            'policy_id' => (int) $policyId,
            'id' => (int) $ruleId,
            'dst_policy_id' => (int) $dstPolicyId);

        return $this->callApiHandler('xslt_policy_rule_move', 'GET', $request, 'XSLT_POLICY_RULE_MOVE_RESULT', 'PolicyRuleMoveResponse');
    }

    public function policyGetRule($user, $ruleId, $policyId)
    {
        $request = array('user' => (int) $user, 'policy_id' => (int) $policyId, 'id' => (int) $ruleId);

        return $this->callApiHandler('xslt_policy_rule_get', 'GET', $request, 'XSLT_POLICY_RULE_GET_RESULT', 'PolicyGetRuleResponse');
    }

    protected function callApiHandler($uri, $method, $params, $responseString, $responseClass) {
        try {
            $response = $this->callApi($uri, $method, $params);

            if (isset($response->$responseString)) {
                $response = $response->$responseString;
                $responseClass = 'AppBundle\Lib\MediaConch\\' . $responseClass;

                return new $responseClass($response);
            }
            else {
                throw new MediaConchServerException('Invalid response');
            }
        }
        catch (HttpException $e) {
            $this->logger->error($e->getMessage());

            throw new MediaConchServerException($e->getMessage(), $e->getStatusCode());
        }

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

        $header = array();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_PORT, $this->port);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this, 'HandleHeaderLine'));

        // Add POST fields
        if ('POST' == $method) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            $header[] = 'Content-type: application/json';
        }

        // Add MediaConch-Instance-ID
        if (null !== $this->userSettings->getMediaConchInstanceID()) {
            $header[] = 'X-App-MediaConch-Instance-ID: ' . $this->userSettings->getMediaConchInstanceID();
        }

        // Add HTTP header
        if (0 < count($header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);
        curl_close($curl);

        if (isset($headers['http_code']) && $headers['http_code'] == 200) {
            return json_decode($response);
        }
        else {
            if (!isset($headers['http_code']) || 0 == $headers['http_code']) {
                $headers['http_code'] = 503;
            }

            throw new HttpException($headers['http_code'], 'MediaConch-Server error - Return code: ' . $headers['http_code'] . ' - Response: ' . $response, null, $headers);
        }
    }

    /**
     * Get custom header sent by MediaConchServer and store MediaConch-Instance-ID
     *
     */
    public function HandleHeaderLine($curl, $headerLine) {
        if (preg_match('/X-App-MediaConch-Instance-ID: ([0-9]+)/', $headerLine, $matches)) {
            $this->userSettings->setMediaConchInstanceID($matches[1]);
        }

        return strlen($headerLine);
    }
}
