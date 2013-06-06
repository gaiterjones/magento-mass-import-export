<?php
// Magento export tier price of all product and calculate % increase + VAT formatted for MAGMI import
// blog.gaiterjones.com
// 21.05.2012 v0.5
//

// get Magento
require_once '../app/Mage.php';
umask(0);
Mage::app();
Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);

header("Content-type:text/octect-stream");
header("Content-Disposition:attachment;filename=exportMyTierPrices.csv");
$enableRounding=false;

if (empty($_GET["maxTierQty"])) { $maxTierQty=0; } else { $maxTierQty=$_GET["maxTierQty"]; }
if (empty($_GET["maxTiersToExport"])) { $maxTiersToExport=4; } else { $maxTiersToExport=$_GET["maxTiersToExport"]; }
if (empty($_GET["percentChange"])) { $percentChange=0; } else { $percentChange=$_GET["percentChange"]; }
if (empty($_GET["percentVAT"])) { $percentVAT=0; } else { $percentVAT=$_GET["percentVAT"]; }
if (empty($_GET["enableRounding"])) { $enableRounding=false; } else { $enableRounding=true; }

// load store
$storeId    = Mage::app()->getStore()->getId();
$product    = Mage::getModel('catalog/product');
$products   = $product->getCollection()->addStoreFilter($storeId)->getAllIds();

// write header
echo '"sku","tier_price:_all_"'. "\n";

// loop through all products
foreach($products as $productid)
{
// load product data
$product = Mage::getModel('catalog/product')->load($productid);

// ignore grouped products
if($product->getTypeId() == "grouped") {continue;}

// get tier price data
$existingTierPrice = $product->tier_price;

	// get tier prices
	if ($existingTierPrice)
	{

		$sku = $product->getSku();
		$tier=array();
		$i=0;
		
		foreach($existingTierPrice as $key=>$value)
		{

			// get tier qty and price - load new arrays
			if ($key+1 <= $maxTiersToExport) // limit number of tiers
			{
				// get tier qty and price
				$tierPrice = round($value['price'],2);
				$tierQty = round($value['price_qty'],1);
				
				// update pricing
				if ($percentChange != 0) { $tierPrice=priceManipulation($tierPrice,$percentChange,$percentVAT,$enableRounding); }
				
				if ($maxTierQty ===0 || $tierQty <= $maxTierQty) // limit max tier qty
				{
					// populate new tier array
					$tier[$i][qty]=$tierQty;
					$tier[$i][price]=$tierPrice;
					
					$i++;
				}
			}
		}	
			
		if ($tier)
		{	// ouput tier info
			$numItems = count($tier); // get number of tiers
			$output= '"'. $sku. '","';
			foreach($tier as $key=>$value)
			{
				if($key+1 == $numItems)
				{
					// format last tier
					$output=$output. $value['qty']. ':'. $value['price']. '"';
				} else {
					$output=$output. $value['qty']. ':'. $value['price']. ';';
				}
			}
			
			echo $output. "\n";
		}
		
		unset($tier);
	}
		
}

function priceManipulation ($unitPrice,$percentChange,$percentVAT,$rounding)
{
		// price manipulation
		// add percent change to unit price
		$newUnitPrice=$unitPrice + ($unitPrice * ($percentChange / 100));
		// add VAT %
		if ($percentVAT != 0) { $newUnitPrice=$newUnitPrice + ($newUnitPrice * ($percentVAT / 100)); }
		// round down to 0.x9 cents (if price is not zero)
		if ($newUnitPrice > 0 && $rounding) { $newUnitPrice=round($newUnitPrice,1) -0.01; }
		
		if ($newUnitPrice > 0 && !$rounding) { $newUnitPrice=round($newUnitPrice,2); }
		
		return($newUnitPrice);
}
?>