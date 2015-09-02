<?php
namespace shop;

require_once "../Settings.php";
require_once "MWS.php";

$mws = new MWS(new Settings());

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta content="width=device-width, initial-scale=1" name="viewport">
</head>
<body>
<code>
    <?php echo htmlspecialchars($mws->listOrders()) ?>
</code>
</body>
</html>
