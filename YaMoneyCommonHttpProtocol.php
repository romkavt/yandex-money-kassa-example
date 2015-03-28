<?php
/**
 * Created by IntelliJ IDEA.
 * User: baibik
 * Date: 04.03.15
 * Time: 2:48
 * To change this template use File | Settings | File Templates.
 */

namespace shop;
require_once 'Utils.php';
require_once 'Log.php';

use DateTime;


class YaMoneyCommonHttpProtocol
{
    private $action;
    private $settings;
    private $log;

    public function __construct($action, Settings $settings)
    {
        $this->action = $action;
        $this->settings = $settings;
        $this->log = new Log($settings);
    }

    /**
     * Основной метод, обрабаывающий запрос checkOrder и paymentAviso
     * @param $request - массив параметров HTTP-запроса
     */
    public function processRequest($request)
    {
        $this->log("Start " . $this->action);
        $this->log("Security type " . $this->settings->SECURITY_TYPE);
        if ($this->settings->SECURITY_TYPE == "MD5") {
            $this->log("Request: " . print_r($request, true));
            // Если подпись не сошлась, сформируем и отправим ответ с кодом "1"
            if (!$this->checkMD5($request)) {
                $response = $this->buildResponse($this->action, $request['invoiceId'], 1);
                $this->sendResponse($response);
            }
        } else if ($this->settings->SECURITY_TYPE == "PKCS7") {
            // Проверим подпись запроса и получим исходные данные. Если подпись не сошлась, отправляем ответ с кодом "200"
            if (($request = $this->verifySign()) == null) {
                $response = $this->buildResponse($this->action, null, 200);
                $this->sendResponse($response);
            }
            $this->log("Request: " . print_r($request, true));
        }
        $response = null;
        if ($this->action == 'checkOrder') {
            // Проверим параметры заказа и сформируем ответ
            $response = $this->checkOrder($request);
        } else {
            // Проведём подтверждение покупки и сформируем ответ
            $response = $this->paymentAviso($request);
        }
        // Отправим ответ
        $this->sendResponse($response);
    }

    /**
     * Бизнесовая логика проверки корректности параметров заказа.
     * Пускай, в нашем магазине нет товаров дешевле 100 р.
     * @param $request - массив с параметрами запроса
     * @return string - сформированный ответ в формате XML
     */
    private function checkOrder($request)
    {
        $response = null;
        if ($request['orderSumAmount'] < 100) {
            $response = $this->buildResponse($this->action, $request['invoiceId'], 100, "Сумма должна быть больше 100 руб.");
        } else {
            $response = $this->buildResponse($this->action, $request['invoiceId'], 0);
        }
        return $response;
    }

    /**
     * Бизнесовая логика обработки нотификации о совершённом платеже
     * @param $request - массив с параметрами запроса
     * @return string - сформированный ответ в формате XML
     */
    private function paymentAviso($request)
    {
        return $this->buildResponse($this->action, $request['invoiceId'], 0);
    }


    /**
     * Проверка MD5-подписи парметров запроса
     * @param $request - массив с парметрами запроса
     * @return bool - результат проверки
     */
    private function checkMD5($request)
    {
        $str = $request['action'] . ";" .
            $request['orderSumAmount'] . ";" . $request['orderSumCurrencyPaycash'] . ";" .
            $request['orderSumBankPaycash'] . ";" . $request['shopId'] . ";" .
            $request['invoiceId'] . ";" . $request['customerNumber'] . ";" . $this->settings->SHOP_PASSWORD;
        $this->log("String to md5: " . $str);
        // Полученную строку приведём к верхнему регистру
        $md5 = strtoupper(md5($str));
        // Параметр md5 запроса так же приведём к верхнему регистру
        if ($md5 != strtoupper($request['md5'])) {
            $this->log("Wait for md5:" . $md5 . ", recieved md5: " . $request['md5']);
            return false;
        }
        return true;
    }

    /**
     * Формирование строки ответа в формате XML
     * @param $functionName - checkOrder или paymentAviso
     * @param $invoiceId    - идентификатор транзакции
     * @param $result_code  - код ответа
     * @param $message      - текст ошибки. Может отсутствовать
     * @return string       - сформированный XML
     */
    private function buildResponse($functionName, $invoiceId, $result_code, $message = null)
    {
        try {
            $performedDatetime = Utils::formatDate(new DateTime());
            $response = '<?xml version="1.0" encoding="UTF-8"?><' . $functionName . 'Response performedDatetime="' . $performedDatetime .
                '" code="' . $result_code . '" ' . ($message != null ? 'message="' . $message . '"' : "") . ' invoiceId="' . $invoiceId . '" shopId="' . $this->settings->SHOP_ID . '"/>';
            return $response;
        } catch (\Exception $e) {
            $this->log($e);
        }
        return null;
    }

    /**
     * Проверка подписи запроса при взаимодействии по схеме XML/PKCS#7
     * @return ассоциативный массив или null, в случае ошибки разбора
     */
    private function verifySign()
    {
        $descriptorspec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));
        $certificate = 'yamoney.pem';
        $process = proc_open('openssl smime -verify -inform PEM -nointern -certfile ' . $certificate . ' -CAfile ' . $certificate,
            $descriptorspec, $pipes);
        if (is_resource($process)) {
            //Получим данные из тела запроса
            $data = file_get_contents($this->settings->request_source); // "php://input"
            fwrite($pipes[0], $data);
            fclose($pipes[0]);
            $content = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $resCode = proc_close($process);
            if ($resCode != 0) {
                return null;
            } else {
                $this->log("Row xml: " . $content);
                // Преобразуем строку в XML
                $xml = simplexml_load_string($content);
                // Сконвертируем атрибуты XML в ассоциативный массив
                $array = json_decode(json_encode($xml), TRUE);
                return $array["@attributes"];
            }
        }
        return null;
    }

    private function log($str)
    {
        $this->log->info($str);
    }

    private function sendResponse($responseBody)
    {
        $this->log("Response: " . $responseBody);
        header("HTTP/1.0 200");
        header("Content-Type: application/xml");
        echo $responseBody;
        exit;
    }




}