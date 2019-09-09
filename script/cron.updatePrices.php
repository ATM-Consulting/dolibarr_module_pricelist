<?php
require '../config.php';
dol_include_once('pricelist/class/pricelist.class.php');
dol_include_once('product/class/product.class.php');
$pricelist = new Pricelist($db);
$product = new Product($db);
$TPrLi = $pricelist->getAllToday();

$i = 0;

foreach ($TPrLi as $idPL) {
	$pricelist->fetch($idPL);
	$product->fetch($pricelist->fk_product);

	if ($pricelist->reduction != ''){
		$new_price = $product->price + $product->price * $pricelist->reduction/100;
	}
	else {
		$new_price =$pricelist->price;
	}
	$product->updatePrice($new_price, 'HT', $user);
	$i++;
}

dol_syslog('Cron PriceList Done : '.dol_now() . ', '. $i . ' products affected');
