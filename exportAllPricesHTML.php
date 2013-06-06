<?php
// Magento export unit and tier prices to html
// blog.gaiterjones.com
// 12.06.2012 v0.10
//
// 12.06.2012 - added no header option &noHeader=1 to hide header form
//
//

$version='0.10';
$eanPrefixCode="0123456";
$url="http://dev.gaiterjones.com/magento/myexport/";
$path2myexport="/home/www/dev/magento/myexport/";

// get Magento
require_once '../app/Mage.php';
umask(0);
Mage::app();
Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);

@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

header('Content-Type: text/html; charset=utf-8');
$enableRounding=false;

// load collection
$storeId    = Mage::app()->getStore()->getId();
$product    = Mage::getModel('catalog/product');
$products   = $product->getCollection()->addStoreFilter($storeId)->getAllIds();

if (empty($_GET["maxTiersToExport"])) { $maxTiersToExport=4; } else { $maxTiersToExport=$_GET["maxTiersToExport"]; }
if (empty($_GET["maxTierQty"])) { $maxTierQty=0; } else { $maxTierQty=$_GET["maxTierQty"]; }
if (empty($_GET["percentChange"])) { $percentChange=0; } else { $percentChange=$_GET["percentChange"]; }
if (empty($_GET["percentVAT"])) { $percentVAT=0; } else { $percentVAT=$_GET["percentVAT"]; }
if (empty($_GET["enableRounding"])) { $enableRounding=false; } else { $enableRounding=true; }
if (empty($_GET["noHeader"])) { $noHeader=false; } else { $noHeader=true; }
if (empty($_GET["barcode"])) { $displayBarcode=false; } else { $displayBarcode=true; }
if (empty($_GET["limit"])) { $displayLimit=false; } else { $displayLimit=true; }
if (empty($_GET["allproducts"])) { $allProducts=false; } else { $allProducts=true; }



$html='
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" >
		<title>My Magento - Unit and Tier Price Export</title>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
		<script src="js/fancybox/jquery.fancybox.js?v=2.0.6"></script>
		<link rel="stylesheet" type="text/css" href="js/fancybox/jquery.fancybox.css?v=2.0.6" media="screen" />
		<style type="text/css">
			body,td { color:#2f2f2f; font:11px/1.35em Verdana, Arial, Helvetica, sans-serif; }
		</style>
		<script type="text/javascript">
			function toTop(id) {
				document.getElementById(id).scrollTop = 0
			}
			defaultStep = 1
			step = defaultStep
			function scrollDivDown(id) {
				document.getElementById(id).scrollTop += step
				timerDown = setTimeout("scrollDivDown(\'" + id + "\')", 10)
			}
			function scrollDivUp(id) {
				document.getElementById(id).scrollTop -= step
				timerUp = setTimeout("scrollDivUp(\'" + id + "\')", 10)
			}
			function toBottom(id) {
				document.getElementById(id).scrollTop = document.getElementById(id).scrollHeight
			}
			function toPoint(id) {
				document.getElementById(id).scrollTop = 100
			}
		</script>
		<script type="text/javascript">
			$(document).ready(function(){
		
				$(\'.fancybox\').fancybox();
         
			});
		</script>
	</head>
<body>
<br>
';

echo $html;
 
require_once 'ProgressBar.class.php';
$p = new ProgressBar();
echo '<div style="width: 300px;">';
$p->render();
echo '</div>';

$output="";
$productCounter=0;
$totalProducts = count($products);

// loop through all products
foreach($products as $productid)
{
$productCounter++;
$p->setProgressBarProgress(round(($productCounter/$totalProducts) * 100,0));

// load each product
$product = Mage::getModel('catalog/product')->load($productid);

$existingTierPrice = $product->tier_price;

// ignore grouped products they have no price
if($product->getTypeId() == "grouped") {continue;}

$groupedChild='<td valign="top" style="padding:7px 9px 9px 9px; border:1px solid #bebcb7; border-top:0; background:#f8f7f5;"> </td>';

// find any invisible simple products belonging to grouped products
if($product->getTypeId() == "simple")
	{
		if ($product->getVisibility()!= "4" || $allProducts)
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
					$groupedChild='<td valign="top" style="padding:7px 9px 9px 9px; border:1px solid #bebcb7; border-top:0; background:#f8f7f5;">X</td>';
				} else {
					continue;					
				}
			}
			
		}
		
	}

	$output=$output. '<tr>
	';
	$sku = htmlspecialchars($product->getSku());
	$name = htmlspecialchars($product->getName());
	$unitPrice = round($product->getPrice(),2);
	
	$productDescription=clean_up($product->getDescription());
	$productShortDescription=clean_up($product->getShortDescription());
	
	if ($percentChange != 0) { $unitPrice=priceManipulation($unitPrice,$percentChange,$percentVAT,$enableRounding); }

	
	$output=$output. '<td valign="top" style="padding:7px 9px 9px 9px; border:1px solid #bebcb7; border-top:0; background:#f8f7f5;"><a class="fancybox" href="#'. $sku. '">'. $sku.
	'</a><div id="'. $sku. '" style="width: 700px; display: none;"><h2>'. $name. ' - '. $sku. '</h2><p>'. $productShortDescription. '</p><p>'. $productDescription.
	'</p></div></td><td valign="top" style="padding:7px 9px 9px 9px; border:1px solid #bebcb7; border-top:0; background:#f8f7f5;">'.
	$name. '</td>'. $groupedChild. '<td valign="top" style="padding:7px 9px 9px 9px; border:1px solid #bebcb7; border-top:0; background:#f8f7f5;">'. $unitPrice. '</td>';

	// get tier prices
	if ($existingTierPrice)
	{
		$numItems = count($existingTierPrice);
		$i = 0;
		
		foreach($existingTierPrice as $key=>$value)
		{
				$tierPrice = round($value['price'],2);
				$tierQty = round($value['price_qty'],1);
				
				if ($percentChange != 0) { $tierPrice=priceManipulation($tierPrice,$percentChange,$percentVAT,$enableRounding); }
				$tierPercentofUnit = round(($unitPrice - $tierPrice) / $unitPrice * 100,2);
				
				if($i+1 == $numItems) // check for last tier price
				{
				// html for last tier price
					$output=$output. '<td valign="top" style="padding:7px 9px 9px 9px; border:1px solid #bebcb7; border-top:0; background:#f8f7f5;">'. $tierQty. ':'. $tierPrice. ' <em>('. $tierPercentofUnit. '%)</em></td>';
						for ($y = $i+2; $y <= $maxTiersToExport; $y++) {
							$output=$output. '<td valign="top" style="padding:7px 9px 9px 9px; border:1px solid #bebcb7; border-top:0; background:#f8f7f5;"></td>';
						}
				} else {
				// html for normal tier price
					$output=$output. '<td valign="top" style="padding:7px 9px 9px 9px; border:1px solid #bebcb7; border-top:0; background:#f8f7f5;">'. $tierQty. ':'. $tierPrice. ' <em>('. $tierPercentofUnit. '%)</em></td>';
				}
				
			$i++;
			// restrict tiers to max tiers
			if ($i+1 > $maxTiersToExport) { break; }
		}

	} else {
	
		for ($y = 1; $y <= $maxTiersToExport; $y++) {
			$output=$output. '<td valign="top" style="padding:7px 9px 9px 9px; border:1px solid #bebcb7; border-top:0; background:#f8f7f5;"></td>';
		}
	
	}
	
	if ($displayBarcode) {
		$ean=ean13_check_digit($eanPrefixCode.str_pad($product->getId(), 5, "0", STR_PAD_LEFT));
	if (!file_exists($path2myexport. "ean/images/". $ean. ".png"))
	{
		grab_image($url. "ean/barcode.php?code=". $ean ."&encoding=EAN&scale=4&mode=png",$path2myexport. "ean/images/". $ean. ".png");
	}	
	$output=$output. '<td valign="top" style="padding:7px 9px 9px 9px; border:1px solid #bebcb7; border-top:0; background:#f8f7f5;">
	<a target="_blank" title="'. $ean. '" href="'. $url. 'ean/barcode.php?code='. $ean .'&encoding=EAN&scale=4&mode=png"><img width="144" height="80" src="'. $url. 'ean/images/'.$ean. '.png"></a><p>'. $ean. '</p></td>';
	}
	
	$output=$output. '</tr>
	';
	
}

if (!$noHeader)
{
$html=$html.'
<div id="mainHead">
<h1>My Magento - Unit & Tier Price Export</h1>
<form action="exportAllPricesHTML.php" method="get">
<p>Price Percent Change = '. $percentChange. '% >> <input type="text" name="percentChange" value="'. $percentChange. '" ></p>
<p>VAT Rate = '. $percentVAT. '% >> <input type="text" name="percentVAT" value="'. $percentVAT. '" ></p>
<p>Max Tiers = '. $maxTiersToExport. ' >> <input type="text" name="maxTiersToExport" value="'. $maxTiersToExport. '" ></p>
<p>For Tier Price CSV export only restrict max tier unit quantity to = '. $maxTierQty. ' >> <input type="text" name="maxTierQty" value="'. $maxTierQty. '" ></p>';

if ($enableRounding)
{
$html=$html.'<p>Rounding Enabled</p>';
} else {
$html=$html.'<p>Rounding Disabled</p>';
}
$html=$html.'
<input type="submit" >
</form>
<p>
Get Tier Price <a href="exportTierPricesCSV.php?percentChange='.$percentChange.'&amp;percentVAT='.$percentVAT.'&amp;maxTiersToExport='.$maxTiersToExport.'&amp;maxTierQty='.$maxTierQty.'">CSV</a><br>
Get Unit Price <a href="exportUnitPricesCSV.php?percentChange='.$percentChange.'&amp;percentVAT='.$percentVAT.'&amp;maxTiersToExport='.$maxTiersToExport.'&amp;maxTierQty='.$maxTierQty.'">CSV</a><br>
Get EAN code <a href="exportEANCSV.php">CSV</a><br>
<a href="importUnitPrices.php">Import</a> Unit Prices - CAUTION!
</p>
</div>';
}

$html=$html.'
<div style="margin-bottom:10px; width:400px">
        <input type="button" style="cursor: pointer; color: Blue" onclick="toTop(\'mainContent\')"
            value="Top">
        <input type="button" value="ScrollDown" style="cursor: pointer; color: Blue" onmousedown="scrollDivDown(\'mainContent\')"
            onmouseup="clearTimeout(timerDown)">
        <input type="button" style="cursor: pointer; color: Blue" onmousedown="scrollDivUp(\'mainContent\')"
            value="Scroll Up" onmouseup="clearTimeout(timerUp)">
        <input type="button" style="cursor: pointer; color: Blue" value="Bottom" onclick="toBottom(\'mainContent\')">
</div>
<div id="mainContent" style="height:400px; width:800px; overflow:scroll; border-style:double">
<table cellspacing="0" cellpadding="0" border="0" width="98%" style="margin-top:10px; margin-left:10px; font:11px/1.35em Verdana, Arial, Helvetica, sans-serif; margin-bottom:10px;">
			<thead>
			 <tr>
				<th align="left" bgcolor="#d9e5ee" style="padding:5px 9px 6px 9px; border:1px solid #bebcb7; border-bottom:none; line-height:1em;">SKU</th>
				<th align="left" bgcolor="#d9e5ee" style="padding:5px 9px 6px 9px; border:1px solid #bebcb7; border-bottom:none; line-height:1em;">Name</th>
				<th align="left" bgcolor="#d9e5ee" style="padding:5px 9px 6px 9px; border:1px solid #bebcb7; border-bottom:none; line-height:1em;" title="These products are associated with a grouped product">GC</th>
				<th align="left" bgcolor="#d9e5ee" style="padding:5px 9px 6px 9px; border:1px solid #bebcb7; border-bottom:none; line-height:1em;">Unit Price</th>
';
for ($x = 1; $x <= $maxTiersToExport; $x++)
	{
				$html=$html.'<th align="left" bgcolor="#d9e5ee" style="padding:5px 9px 6px 9px; border:1px solid #bebcb7; border-bottom:none; line-height:1em;">Tier '. $x. ' Qty:Price</th>
				';
	}

	if ($displayBarcode) {
	$html=$html.'
<th align="left" bgcolor="#d9e5ee" style="padding:5px 9px 6px 9px; border:1px solid #bebcb7; border-bottom:none; line-height:1em;">EAN</th>
';
	}
	
$html=$html.'
</tr>
</thead>
';
echo $html;

echo $output;

$html='
</table>
</div>
<p>'. $productCounter. ' products.</p>
<p>Magento Unit &amp; Tier Price Mass Export &amp; Import v'. $version. '</p>
';
if ($displayBarcode) {$html=$html.'<p>Barcodes courtesy of <a href="http://www.ashberg.de/php-barcode">Folke Ashberg</a></p>';}
$html=$html.'
<p>
    <a href="http://validator.w3.org/check?uri=referer"><img
      src="http://www.w3.org/Icons/valid-html401" alt="Valid HTML 4.01 Transitional" height="31" width="88"></a>
</p>
</body>
</html>
';

echo $html;

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
function grab_image($url,$saveto){
    $ch = curl_init ($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $raw=curl_exec($ch);
    curl_close ($ch);
    if(file_exists($saveto)){
        unlink($saveto);
    }
    $fp = fopen($saveto,'x');
    fwrite($fp, $raw);
    fclose($fp);
}

// clean up text
function clean_up ($text)
{
	$cleanText=replaceHtmlBreaks($text," ");
	$cleanText=strip_html_tags($cleanText);
	$cleanText=preg_replace("/&#?[a-z0-9]+;/i"," ",$cleanText);
	$cleanText=htmlspecialchars($cleanText);
	
	return $cleanText;
}
function strip_html_tags( $text )
{
    $text = preg_replace(
        array(
          // Remove invisible content
            '@<head[^>]*?>.*?</head>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<object[^>]*?.*?</object>@siu',
            '@<embed[^>]*?.*?</embed>@siu',
            '@<applet[^>]*?.*?</applet>@siu',
            '@<noframes[^>]*?.*?</noframes>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu',
            '@<noembed[^>]*?.*?</noembed>@siu',
          // Add line breaks before and after blocks
            '@</?((address)|(blockquote)|(center)|(del))@iu',
            '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
            '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
            '@</?((table)|(th)|(td)|(caption))@iu',
            '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
            '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
            '@</?((frameset)|(frame)|(iframe))@iu',
        ),
        array(
            ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
            "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
            "\n\$0", "\n\$0",
        ),
        $text );
    return strip_tags( $text );
}
function replaceHtmlBreaks($str, $replace, $multiIstance = FALSE)
{
  
    $base = '<[bB][rR][\s]*[/]*[\s]*>';
    
    $pattern = '|' . $base . '|';
    
    if ($multiIstance === TRUE) {
        //The pipe (|) delimiter can be changed, if necessary.
        
        $pattern = '|([\s]*' . $base . '[\s]*)+|';
    }
    
    return preg_replace($pattern, $replace, $str);
}
?>