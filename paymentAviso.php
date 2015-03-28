<?php

namespace shop;

require_once "YaMoneyCommonHttpProtocol.php";
require_once "Settings.php";

$settings = new Settings();
$yaMoneyCommonHttpProtocol = new YaMoneyCommonHttpProtocol("paymentAviso", $settings);
$yaMoneyCommonHttpProtocol->processRequest($_REQUEST);
exit;