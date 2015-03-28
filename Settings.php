<?php
/**
 * Created by IntelliJ IDEA.
 * User: baibik
 * Date: 06.03.15
 * Time: 19:28
 * To change this template use File | Settings | File Templates.
 */

namespace shop;

class Settings {
    public $SHOP_PASSWORD = "123456";
    public $SECURITY_TYPE;
    public $LOG_FILE;
    public $SHOP_ID = 151;
    public $CURRENCY = 10643;
    public $REQUEST_SOURCE;
    public $request_source;
    public $mws_cert;
    public $mws_private_key;
    public $mws_cert_password = "123456";

    function __construct($SECURITY_TYPE = "PKCS7" /* MD5 | PKCS7 */, $request_source = "php://input")
    {
        $this->SECURITY_TYPE = $SECURITY_TYPE;
        $this->request_source = $request_source;
        $this->LOG_FILE = dirname(__FILE__)."/log.txt";
        $this->mws_cert = dirname(__FILE__)."/mws/baibik.cer"; //$_SERVER['DOCUMENT_ROOT']."/shop/mws/baibik.cer";
        $this->mws_private_key = dirname(__FILE__)."/mws/private.key";

        //print_r($this);
        //exit;
    }

}