<?php
// Magento export unit prices to CSV and optionally manipulate pricing
//
// blog.gaiterjones.com
// 21.05.2012 v0.5

//

require_once '../app/Mage.php';
umask(0);
Mage::app();
Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);

header("Content-type:text/octect-stream");
header("Content-Disposition:attachment;filename=exportMyUnitPrices.csv");
$enableRounding=false;

if (empty($_GET["maxTiersToExport"])) { $maxTiersToExport=4; } else { $maxTiersToExport=$_GET["maxTiersToExport"]; }
if (empty($_GET["percentChange"])) { $percentChange=0; } else { $percentChange=$_GET["percentChange"]; }
if (empty($_GET["percentVAT"])) { $percentVAT=0; } else { $percentVAT=$_GET["percentVAT"]; }
if (empty($_GET["enableRounding"])) { $enableRounding=false; } else { $enableRounding=true; }

// load collection
$storeId    = Mage::app()->getStore()->getId();
$product    = Mage::getModel('catalog/product');
$products   = $product->getCollection()->addStoreFilter($storeId)->getAllIds();

echo '"sku","price"'. "\n";

// loop through all products
foreach($products as $productid)
{
// load product data
$product = Mage::getModel('catalog/product')->load($productid);

// ignore grouped products they are a container for child simple products and have no price
if($product->getTypeId() == "grouped") {continue;}

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
					// child of group, go get em cowboy.
				} else {
					continue;					
				}
			}
			
		}
		
	}

	$sku = $product->getSku();
	$name = $product->getName();
	$unitPrice=round($product->getPrice(),2);

	if ($percentChange != 0) { $unitPrice=priceManipulation($unitPrice,$percentChange,$percentVAT,$enableRounding); }
				
	$output='"'. $sku. '","'. $unitPrice. '"';


	echo $output. "\n";
}

function priceManipulation ($unitPrice,$percentChange,$percentVAT,$rounding)
{
		// price manipulation
		// add percent increase to unit price
		$newUnitPrice=$unitPrice + ($unitPrice * ($percentChange / 100));
		// add VAT %
		if ($percentVAT != 0) { $newUnitPrice=$newUnitPrice + ($newUnitPrice * ($percentVAT / 100)); }
		// round down to 0.x9 cents (if price is not zero)
		if ($newUnitPrice > 0 && $rounding) { $newUnitPrice=round($newUnitPrice,1) -0.01; }
		
		if ($newUnitPrice > 0 && !$rounding) { $newUnitPrice=round($newUnitPrice,2); }
		
		return($newUnitPrice);
}
?>