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
        $this->log->info("Start listOrder");
        $requestParams = array(
            'requestDT' => Utils::formatDateForMWS(new \DateTime()),
            'outputFormat' => 'XML',
            'shopId' => $this->settings->SHOP_ID,
            'orderCreatedDatetimeLessOrEqual' => Utils::formatDateForMWS(new \DateTime())

        );
        $this->log->info("Request params:");
        $this->log->info($requestParams);
        $result = $this->sendRequest("listOrders", $requestParams);
        $this->log->info($result);
        return $result;
    }

    public function listReturns() {
        $this->log->info("Start listReturns");
        $requestParams = array(
            'requestDT' => Utils::formatDateForMWS(new \DateTime()),
            'outputFormat' => 'XML',
            'shopId' => $this->settings->SHOP_ID,
            'from' => '2015-01-01T00:00:00.000Z',
            'till' => Utils::formatDateForMWS(new \DateTime()),

        );
        $this->log->info("Request params:");
        $this->log->info($requestParams);
        $result = $this->sendRequest("listReturns", $requestParams);
        $this->log->info($result);
        return $result;
    }

    public function returnPayment($invoiceId, $amount) {
        $this->log->info("Start returnPayment");

        $source = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <returnPaymentRequest
            clientOrderId=\"" . mktime() . "\"
            requestDT=\"" . Utils::formatDate(new \DateTime()) . "\"
            invoiceId=\"" . $invoiceId . "\"
            shopId=\"" . $this->settings->SHOP_ID . "\"
            amount=\"" . $amount . ".00\"
            currency=\"" . $this->settings->CURRENCY. "\"
            cause=\"Нет товара\"/>";

        $this->log->info("Request: ".$source);

        $result = $this->sendRequest("returnPayment", $this->singData($source), true);
        $this->log->info($result);
        return $result;
    }

    public function confirmPayment($invoiceId, $amount) {
        $this->log->info("Start confirmPayment");

        $requestParams = "clientOrderId=".mktime()."&requestDT=". Utils::formatDate(new \DateTime())."&orderId=".$invoiceId."&amount=".$amount."&currency=RUB";

        $this->log->info("Request: ".$source);

        $result = $this->sendRequest("confirmPayment", $requestParams);
        $this->log->info($result);
        return $result;
    }

    public function cancelPayment($invoiceId, $amount) {
        $this->log->info("Start cancelPayment");

        $requestParams = "requestDT=".Utils::formatDate(new \DateTime())."&orderId=".$invoiceId."";

        $this->log->info("Request: ".$source);

        $result = $this->sendRequest("cancelPayment", $requestParams);
        $this->log->info($result);
        return $result;
    }

    public function repeatCardPayment($invoiceId, $amount) {
        $this->log->info("Start repeatCardPayment");
        $requestParams = "clientOrderId=".mktime()."&invoiceId=".$invoiceId."&amount=".$amount."";

        $this->log->info("Request: ".$source);

        $result = $this->sendRequest("repeatCardPayment", $requestParams);
        $this->log->info($result);
        return $result;
    }

    private function singData($source_data) {
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

    private function sendRequest($action, $request_params, $useEncryption = false) {
        $curl = curl_init();
        $content_type = $useEncryption ? "application/pkcs7-mime" : "x-www-form-urlencoded";
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array('Content-type: application/' . $content_type),
            CURLOPT_URL => 'https://penelope-demo.yamoney.ru:8083/webservice/mws/api/' . $action,
            CURLOPT_POST => 1,
            /*Отключаем проверку серверного сертификата*/
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSLCERT => $this->settings->mws_cert,
            CURLOPT_SSLKEY => $this->settings->mws_private_key,
            CURLOPT_SSLCERTPASSWD => $this->settings->mws_cert_password,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POSTFIELDS => $request_params
        ));

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

