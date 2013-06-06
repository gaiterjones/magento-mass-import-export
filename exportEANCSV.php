<?php
// Magento export EAN codes
// blog.gaiterjones.com
// 10.10.2012 v0.1
//

require_once '../app/Mage.php';
umask(0);
Mage::app();
Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);

header("Content-type:text/octect-stream");
header("Content-Disposition:attachment;filename=exportMyEANCodes.csv");

// define ean code prefix - country / manufacturer
$eanPrefixCode="0123456";

// load collection
$storeId    = Mage::app()->getStore()->getId();
$product    = Mage::getModel('catalog/product');
$products   = $product->getCollection()->addStoreFilter($storeId)->getAllIds();

// csv header
echo '"sku","ean"'. "\n";

// loop through all products
foreach($products as $productid)
{
// load product data
$product = Mage::getModel('catalog/product')->load($productid);

// get sku
$sku = $product->getSku();
// generate ean13
$ean=ean13_check_digit($eanPrefixCode. str_pad($product->getId(), 5, "0", STR_PAD_LEFT));
// output csv data
$output='"'. $sku. '","'. $ean. '"';
echo $output. "\n";
}

// function to generate ean13 checksum digit
function ean13_check_digit($digits){
$digits =(string)$digits;
$even_sum = $digits{1} + $digits{3} + $digits{5} + $digits{7} + $digits{9} + $digits{11};
$even_sum_three = $even_sum * 3;
$odd_sum = $digits{0} + $digits{2} + $digits{4} + $digits{6} + $digits{8} + $digits{10};
$total_sum = $even_sum_three + $odd_sum;
$next_ten = (ceil($total_sum/10))*10;
$check_digit = $next_ten - $total_sum;
return $digits . $check_digit;
}
?>