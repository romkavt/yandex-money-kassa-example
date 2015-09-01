<?php
namespace shop;

use HttpException;

require_once "../Log.php";
require_once "../Utils.php";

class MWS {

    private $settings;
    private $log;

    function __construct(Settings $settings) {
        $this->log = new Log($settings);
        $this->settings = $settings;
    }

    public function listOrders() {
        $action = __FUNCTION__;
        $this->log->info("Start " . $action);
        $dateTime = Utils::formatDateForMWS(new \DateTime()) ;
        $requestParams = array(
            'requestDT' => $dateTime,
            'outputFormat' => 'XML',
            'shopId' => $this->settings->SHOP_ID,
            'orderCreatedDatetimeLessOrEqual' => $dateTime
        );
        $useEncryption = false;
        $useXmlFormat = false;
        $result = $this->sendRequest($action, $requestParams, $useEncryption, $useXmlFormat);
        $this->log->info($result);
        return $result;
    }

    public function listReturns() {
        $action = __FUNCTION__;
        $this->log->info("Start " . $action);
        $dateTime = Utils::formatDateForMWS(new \DateTime()) ;
        $requestParams = array(
            'requestDT' => $dateTime,
            'outputFormat' => 'XML',
            'shopId' => $this->settings->SHOP_ID,
            'from' => '2015-01-01T00:00:00.000Z',
            'till' => $dateTime
        );
        $useEncryption = false;
        $useXmlFormat = false;
        $result = $this->sendRequest($action, $requestParams, $useEncryption, $useXmlFormat);
        $this->log->info($result);
        return $result;
    }

    public function returnPayment($invoiceId, $amount) {
        $action = __FUNCTION__;
        $this->log->info("Start " . $action);
        $dateTime = Utils::formatDate(new \DateTime()) ;
        $requestParams = array(
            'clientOrderId' => mktime(),
            'requestDT' => $dateTime,
            'invoiceId' => $invoiceId,
            'shopId' => $this->settings->SHOP_ID,
            'amount' => number_format($amount, 2),
            'currency' => $this->settings->CURRENCY,
            'cause' => 'Нет товара'
        );
        $useEncryption = true;
        $useXmlFormat = true;
        $result = $this->sendRequest($action, $requestParams, $useEncryption, $useXmlFormat);
        $this->log->info($result);
        return $result;
    }

    public function confirmPayment($invoiceId, $amount) {
        $action = __FUNCTION__;
        $this->log->info("Start " . $action);
        $dateTime = Utils::formatDate(new \DateTime()) ;
        $requestParams = array(
            'clientOrderId' => mktime(),
            'requestDT' => $dateTime,
            'orderId' => $invoiceId,
            'amount' => $amount,
            'currency' => 'RUB'
        );
        $useEncryption = false;
        $useXmlFormat = false;
        $result = $this->sendRequest($action, $requestParams, $useEncryption, $useXmlFormat);
        $this->log->info($result);
        return $result;
    }

    public function cancelPayment($invoiceId) {
        $action = __FUNCTION__;
        $this->log->info("Start " . $action);
        $dateTime = Utils::formatDate(new \DateTime()) ;
        $requestParams = array(
            'requestDT' => $dateTime,
            'orderId' => $invoiceId
        );
        $useEncryption = false;
        $useXmlFormat = false;
        $result = $this->sendRequest($action, $requestParams, $useEncryption, $useXmlFormat);
        $this->log->info($result);
        return $result;
    }

    public function repeatCardPayment($invoiceId, $amount) {
        $action = __FUNCTION__;
        $this->log->info("Start " . $action);
        $requestParams = array(
            'clientOrderId' => mktime(),
            'invoiceId' => $invoiceId,
            'amount' => $amount
        );
        $useEncryption = false;
        $useXmlFormat = false;
        $result = $this->sendRequest($action, $requestParams, $useEncryption, $useXmlFormat);
        $this->log->info($result);
        return $result;
    }

    private function prepareRequestData($data = array(), $action = '', $useXmlFormat = false)
    {
        $result = '';
        if (sizeof($data)) {
            if ($useXmlFormat && $action) {
                $result = '<?xml version="1.0" encoding="UTF-8"?>';
                $result .= '<' . $action . 'Request ';
                foreach($data AS $param => $value) {
                    $result .= $param . '="' . $value . '" ';
                }
                $result .= '/>';
            } else {
                $result = http_build_query($data);
            }
        }
        return $result;
    }

    private function signData($source_data) {
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );
        try {
            $open_ssl_comand = 'openssl smime -sign -signer ' . $this->settings->mws_cert .
                ' -inkey ' . $this->settings->mws_private_key .
                ' -nochain -nocerts -outform PEM -nodetach -passin pass:'.$this->settings->mws_cert_password;
            $this->log->info("open_ssl_comand: " . $open_ssl_comand);
            $process = proc_open($open_ssl_comand, $descriptorspec, $pipes);
            if (is_resource($process)) {
                fwrite($pipes[0], $source_data);
                fclose($pipes[0]);
                $pkcs7 = stream_get_contents($pipes[1]);
                $this->log->info($pkcs7);
                fclose($pipes[1]);
                $resCode = proc_close($process);
                if ($resCode != 0) {
                    $error_msg = 'OpenSSL call failed:' . $resCode . '\n' . $pkcs7;
                    $this->log->info($error_msg);
                    throw new \Exception($error_msg);
                }
                return $pkcs7;
            }
        } catch (\Exception $e) {
            $this->log->info($e);
            throw $e;
        }
    }

    private function sendRequest($action, $requestParams, $useEncryption = false, $useXmlFormat = false) {

        $requestData = $this->prepareRequestData($requestParams, $action, $useXmlFormat);
        $this->log->info($action . " Request: " . $requestData);
  
        $curl = curl_init();
        $content_type = $useEncryption ? "application/pkcs7-mime" : "x-www-form-urlencoded";
        $params = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array('Content-type: application/' . $content_type),
            CURLOPT_URL => 'https://penelope-demo.yamoney.ru:8083/webservice/mws/api/' . $action,
            CURLOPT_POST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSLCERT => $this->settings->mws_cert,
            CURLOPT_SSLKEY => $this->settings->mws_private_key,
            CURLOPT_SSLCERTPASSWD => $this->settings->mws_cert_password,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_VERBOSE => 1,
            CURLOPT_POSTFIELDS => $useEncryption ? $this->signData($requestData) : $requestData
        );
        curl_setopt_array($curl, $params);
        $result = null;
        try {
            $result = curl_exec($curl);
            if (!$result) {
              trigger_error(curl_error($curl));
            }
            curl_close($curl);
        } catch (HttpException $ex) {
            echo $ex;
        }
        return $result;
    }
}
