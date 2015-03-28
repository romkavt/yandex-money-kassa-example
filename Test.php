<?php
/**
 * Created by IntelliJ IDEA.
 * User: baibik
 * Date: 06.03.15
 * Time: 20:08
 * To change this template use File | Settings | File Templates.
 */

namespace shop;
require_once "Settings.php";
require_once "YaMoneyCommonHttpProtocol.php";

$test = new Test();
$test->checkOrderPKCS7Request();
//$test->paymentAvisoPKCS7Request();

class Test {

    public function checkOrderPKCS7Request()
    {
        $settings = new Settings("PKCS7","checkOrderPkcs7Request.txt");
        $yaMoneyCommonHttpProtocol = new YaMoneyCommonHttpProtocol("checkOrder", $settings);
        $yaMoneyCommonHttpProtocol->processRequest($_REQUEST);
    }

    public function paymentAvisoPKCS7Request()
    {
        $settings = new Settings("PKCS7","paymentAvisoPkcs7Request.txt");
        $yaMoneyCommonHttpProtocol = new YaMoneyCommonHttpProtocol("checkOrder", $settings);
        $yaMoneyCommonHttpProtocol->processRequest($_REQUEST);
    }
}