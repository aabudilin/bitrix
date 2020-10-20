<?
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER['DOCUMENT_ROOT']."/local/classes/get_price_csv.php");

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL; 
use Bitrix\Main\Entity;

Loader::includeModule("iblock");

$sections = array();
$res = CIBlockSection::GetList(
     Array("SORT"=>"ASC"), 
     Array("IBLOCK_ID"=>33, "ACTIVE"=>"Y"), 
     true,
     Array("ID", "NAME")
  );
while($arSection = $res->GetNext()) {
      $sections[$arSection['ID']] = $arSection['NAME'];
};

$brands = array();

$hlbl = 2; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
$hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch(); 
$entity = HL\HighloadBlockTable::compileEntity($hlblock); 
$entity_data_class = $entity->getDataClass();

$rsData = $entity_data_class::getList(array(
   "select" => array("*"),
   "order" => array("ID" => "ASC"),
));

while($arData = $rsData->Fetch()){
	$brands[$arData['UF_XML_ID']] = $arData['UF_NAME'];
}

if (isset($_POST['section'])) {
	if(!empty($_POST['section'])) $filter['SECTION_ID'] = intval($_POST['section']);
	if(!empty($_POST['brand'])) $filter['PROPERTY_BRANDS_REF'] = $_POST['brand'];

	$ob = new GetPriceCsv($filter);
	$result = $ob->process();
}
?>

<form action="index.php" method="post">
	<p>Выберите раздел</p>
	<select name="section">
		<option></option>
		<?foreach($sections as $id => $name):?>
		<option value="<?=$id?>"><?=$name?></option>
		<?endforeach?>
	</select>
	<p>Выберите бренд</p>
	<select name="brand">
		<option></option>
		<?foreach($brands as $id => $name):?>
			<option value="<?=$id?>"><?=$name?></option>
		<?endforeach?>
	</select>
	<p><button type="sumbit">Сгенерировать прайс</button></p>
</form>

<?if ($result):?>
	<a href="<?=$ob->get_path()?>">Скачать прайс</a>
<?elseif (isset($ob)):?>
	<p>Товаров для выгрузки не найдено</p>
<?endif?>