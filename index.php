<?php
spl_autoload_register(function ($class) {
    include 'Services/' . $class . '.php';
});

$cartRobo = new CartRobo();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Робокасса</title>
</head>
<body>
    <?=$cartRobo->getCheck()?>
    <?=$cartRobo->getPaymentLink()?>
</body>
</html>