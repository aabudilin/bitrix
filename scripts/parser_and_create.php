<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
CModule::IncludeModule("iblock");
$APPLICATION->SetTitle("Парсер");

use DiDom\Document;

if($_REQUEST['url']) {

$path = $_SERVER['DOCUMENT_ROOT'].'/services/parser/DiDom/';

require($path."Document.php");
require($path."Encoder.php");
require($path."Node.php");
require($path."Query.php");
require($path."ClassAttribute.php");
require($path."DocumentFragment.php");
require($path."Element.php");
require($path."Errors.php");
require($path."StyleAttribute.php");

$APPLICATION->SetTitle("Парсинг");


$document = new Document($_REQUEST['url'], true);

$org = $document->find('.market_block');

$result = array();
foreach($org as $data) {
	$item = array();
	$item['NAME'] = $data->find('.title')[0]->text();
	$item['ADDR'] = $data->find('.address span')[0]->text();
	foreach($data->find('.phone span') as $contact) {
		if (strpos($contact->text(),'@') === false) {
			$item['PHONE'] = $contact->text();
		} else {
			$item['EMAIL'] = $contact->text();
		}
	}
	if (count($data->find('.phone a')) > 0) {
    	$item['LINK'] = $data->find('.phone a')[0]->text();
	}
	foreach($data->find('a') as $link) {
		if (strpos($link->attr('href'),'java') !== false) {
			preg_match_all('~\[(.*?)\]~s',$link->attr('href'), $matches);
    		$item['MAP'] = str_replace(' ','',$matches[1][0]);
		}
	}
	$result[] = $item;
}
echo '<pre>';

print_r($result);

foreach($result as $item) {
	$el = new CIBlockElement;
	$PROP = array();
	$PROP['CITY'] = $_REQUEST['id'];
	$PROP['ADDR'] = $item['ADDR'];
	$PROP['URL'] = $item['LINK'];
	$PROP['PHONE'] = $item['PHONE'];
	$PROP['EMAIL'] = $item['EMAIL'];
	$PROP['MAP'] = $item['MAP'];
	$arLoad = Array(  
	   'IBLOCK_SECTION_ID' => false, // ýëåìåíò ëåæèò â êîðíå ðàçäåëà  
	   'IBLOCK_ID' => 6,
	   'PROPERTY_VALUES' => $PROP,  
	   'NAME' => $item['NAME'],  
	   'ACTIVE' => 'Y', 
	);

	if($ID = $el->Add($arLoad)) {
		echo 'New ID: '.$ID.'<br />';
	} else {
	   echo 'Error: '.$el->LAST_ERROR;
	}
}
}

?>

<form action="<?=$_SERVER['REQUEST_URI']?>" method="get">
	<p><input type="text" name="id" style="width:50px;" /> - ID города</p>
	<p><input type="text" name="url" style="width:300px;"/> - URL</p>
	<button type="submit">Спарсить</button>
</form>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
