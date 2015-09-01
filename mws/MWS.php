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
        $dateTime = Utils::formatDateForMWS(new \DateTime());
        $requestParams = array(
            'requestDT' => $dateTime,
            'outputFormat' => 'XML',
            'shopId' => $this->settings->SHOP_ID,
            'orderCreatedDatetimeLessOrEqual' => $dateTime
        );
        $result = $this->sendUrlEncodedRequest($action, $requestParams);
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
        $result = $this->sendUrlEncodedRequest($action, $requestParams);
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
        $result = $this->sendXmlRequest($action, $requestParams);
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
        $result = $this->sendUrlEncodedRequest($action, $requestParams);
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
        $result = $this->sendUrlEncodedRequest($action, $requestParams);
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
        $result = $this->sendUrlEncodedRequest($action, $requestParams);
        $this->log->info($result);
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

    private function sendXmlRequest($action, $data) {
        $body = '<?xml version="1.0" encoding="UTF-8"?>';
        $body .= '<' . $action . 'Request ';
        foreach($data AS $param => $value) {
            $body .= $param . '="' . $value . '" ';
        }
        $body .= '/>';

        return $this->sendRequest($action, $this->signData($body), "pkcs7-mime");
    }

    private function sendUrlEncodedRequest($paymentMethod, $data) {
        return $this->sendRequest($paymentMethod, http_build_query($data), "x-www-form-urlencoded");
    }

    private function sendRequest($action, $requestBody, $contentType) {
        $this->log->info($action . " Request: " . $requestBody);
  
        $curl = curl_init();
        $params = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array('Content-type: application/' . $contentType),
            CURLOPT_URL => 'https://penelope-demo.yamoney.ru:8083/webservice/mws/api/' . $action,
            CURLOPT_POST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSLCERT => $this->settings->mws_cert,
            CURLOPT_SSLKEY => $this->settings->mws_private_key,
            CURLOPT_SSLCERTPASSWD => $this->settings->mws_cert_password,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_VERBOSE => 1,
            CURLOPT_POSTFIELDS => $requestBody
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
