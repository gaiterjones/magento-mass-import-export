<?php
// Magento export all prices to csv
//
//


// get Magento
require_once '../app/Mage.php';
umask(0);
Mage::app();
Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);

header("Content-type:text/octect-stream");
header("Content-Disposition:attachment;filename=exportPrices.csv");
//header('Content-Type: text/html; charset=utf-8');

// load collection
$storeId    = Mage::app()->getStore()->getId();
$product    = Mage::getModel('catalog/product');
$products   = $product->getCollection()->addStoreFilter($storeId)->getAllIds();

// configure ean prefix
$eanPrefixCode="4037493";

echo '"SKU","Name","GC","Unit Price","Tier1 Qty","Tier1 Price","Tier2 Qty","Tier2 Price","Tier3 Qty","Tier3 Price","Tier4 Qty","Tier4 Price","Tier5 Qty","Tier5 Price","EAN"'. "\n";

// loop through all products
foreach($products as $productid)
{
// load product data
$product = Mage::getModel('catalog/product')->load($productid);

$existingTierPrice = $product->tier_price;

// ignore grouped products they are a container for child simple products and have no price
if($product->getTypeId() == "grouped") {continue;}

$groupedChild=' ';

// find invisible child simple products belonging to grouped product
if($product->getTypeId() == "simple")
	{
		if ($product->getVisibility()!= "4")
		{
			if (Mage::getVersion() >= 1.4)
			{
			// Magento v1.42 +
			$parentIdArray = Mage::getModel('catalog/product_type_grouped')
				->getParentIdsByChild( $product->getId() );
			} else {
			// pre 1.42
			$parentIdArray = $product->loadParentProductIds()->getData('parent_product_ids');
			}
			if (!empty($parentIdArray[0]))
			{
				// use parent product if parent is grouped otherwise move on, these are not the products you are looking for...
				$groupedProduct = Mage::getModel('catalog/product')->load($parentIdArray[0]);
				
				if($groupedProduct->getTypeId() == "grouped") {
					$groupedChild='X';
				} else {
					continue;					
				}
			}
			
		}
		
	}

	$sku = $product->getSku();
	$name = $product->getName();
	$unitPrice = round($product->getPrice(),2);
	$output='"'. $sku. '","'. $name. '","'. $groupedChild. '","'. $unitPrice. '",';

	// get tier prices
	if ($existingTierPrice)
	{
		$numItems = count($existingTierPrice);
		$i = 0;
		
		foreach($existingTierPrice as $key=>$value)
		{
		
				if($i+1 == $numItems) // check for last item in array
				{
				// last item in array
					$output=$output. '"'. round($value['price_qty'],1). '","'. round($value['price'],2). '",';
						for ($y = $i+2; $y <= 5; $y++) {
							$output=$output. '" "," ",';
						}
				} else {
					$output=$output. '"'. round($value['price_qty'],1). '","'. round($value['price'],2). '",';
				}
				
			$i++;
		}

	} else {
	
		for ($y = 1; $y <= 5; $y++) {
			$output=$output. '" "," ",';
		}

	}
	// ean
	$ean=ean13_check_digit($eanPrefixCode.str_pad($product->getId(), 5, "0", STR_PAD_LEFT));
	$output=$output. '"'. $ean. '"';
	echo $output. "\n";
}

function ean13_check_digit($digits){
//first change digits to a string so that we can access individual numbers
$digits =(string)$digits;
// 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
$even_sum = $digits{1} + $digits{3} + $digits{5} + $digits{7} + $digits{9} + $digits{11};
// 2. Multiply this result by 3.
$even_sum_three = $even_sum * 3;
// 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
$odd_sum = $digits{0} + $digits{2} + $digits{4} + $digits{6} + $digits{8} + $digits{10};
// 4. Sum the results of steps 2 and 3.
$total_sum = $even_sum_three + $odd_sum;
// 5. The check character is the smallest number which, when added to the result in step 4,  produces a multiple of 10.
$next_ten = (ceil($total_sum/10))*10;
$check_digit = $next_ten - $total_sum;
return $digits . $check_digit;
}
?>