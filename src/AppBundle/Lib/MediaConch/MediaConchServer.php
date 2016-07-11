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
        $request = array('ANALYZE' => array('args' => array(array('id' => 0, 'file' => $file))));
        $response = $this->callApi('analyze', 'POST', json_encode($request));
        $response = $response->ANALYZE_RESULT;

        $analyseResponse = new AnalyzeResponse($response);

        return $analyseResponse;
    }

    public function status($id)
    {
        $request = array('id' => $id);
        $response = $this->callApi('status', 'GET', $request);
        $response = $response->STATUS_RESULT;

        $statusReponse = new StatusResponse($response);

        return $statusReponse;
    }

    public function report($id, $report, $displayName, $display = null, $policy = null, $verbosity = -1)
    {
        $request = array('REPORT' => array('ids' => array((int) $id), 'reports' => array($report), 'verbosity' => (int) $verbosity));
        if ($display && file_exists($display) && is_readable($display)) {
            $request['REPORT']['display_content'] = file_get_contents($display);
        }
        else {
            $request['REPORT']['display_name'] = $displayName;
        }

        if ($policy && file_exists($policy) && is_readable($policy)) {
            $request['REPORT']['policies_contents'] = array(file_get_contents($policy));
        }

        $response = $this->callApi('report', 'POST', json_encode($request));
        $response = $response->REPORT_RESULT;

        $reportResponse = new ReportResponse($response);

        return $reportResponse;
    }

    public function validate($id, $report, $policy = null)
    {
        $request = array('VALIDATE' => array('ids' => array((int) $id), 'report' => $report));
        if ($policy && file_exists($policy)) {
            $request['VALIDATE']['policies_contents'] = array(file_get_contents($policy));
        }
        $response = $this->callApi('validate', 'POST', json_encode($request));
        $response = $response->VALIDATE_RESULT;

        $reportResponse = new ValidateResponse($response);

        return $reportResponse;
    }

    public function fileFromId($id)
    {
        $request = array('FILE_FROM_ID' => array('id' => (int) $id));

        $response = $this->callApi('file_from_id', 'POST', json_encode($request));
        $response = $response->FILE_FROM_ID_RESULT;

        $reportResponse = new FileFromIdResponse($response);

        return $reportResponse;
    }

    public function policyFromFile($id)
    {
        $request = array('id' => $id);
        $response = $this->callApi('create_policy_from_file', 'GET', $request);
        $response = $response->CREATE_POLICY_FROM_FILE_RESULT;

        $statusReponse = new PolicyFromFileResponse($response);

        return $statusReponse;
    }

    public function valuesFromType($trackType, $field)
    {
        $request = array('type' => $trackType, 'field' => $field);
        $response = $this->callApi('default_values_for_type', 'GET', $request);
        $response = $response->DEFAULT_VALUES_FOR_TYPE_RESULT;

        $valuesFromTypeReponse = new ValuesFromTypeResponse($response);

        return $valuesFromTypeReponse;
    }

    protected function callApi($uri, $method, $params)
    {
        $url = 'http://' . $this->address . '/' . $this->apiVersion . '/' . $uri;

        if ('GET' == $method) {
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
