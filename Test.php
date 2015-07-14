<?php

namespace shop;
require_once "Settings.php";
require_once "YaMoneyCommonHttpProtocol.php";

$test = new Test();
$test->checkOrderPKCS7Request();

class Test {

    public function checkOrderPKCS7Request() {
        $settings = new Settings("PKCS7","checkOrderPkcs7Request.txt");
        $yaMoneyCommonHttpProtocol = new YaMoneyCommonHttpProtocol("checkOrder", $settings);
        $yaMoneyCommonHttpProtocol->processRequest($_REQUEST);
    }

    public function paymentAvisoPKCS7Request() {
        $settings = new Settings("PKCS7","paymentAvisoPkcs7Request.txt");
        $yaMoneyCommonHttpProtocol = new YaMoneyCommonHttpProtocol("checkOrder", $settings);
        $yaMoneyCommonHttpProtocol->processRequest($_REQUEST);
    }
}