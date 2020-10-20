<?
class GetPriceCsv{
	
	private $path = 'price.csv';
	private $iblockId = '33';
	private $step = 10;
	public $filter = array();
	public $section_map = array();

	public function __construct($filter) {
		$this->path = 'price_'.$filter['SECTION_ID'].'_'.$filter['PROPERTY_BRANDS_REF'].'.csv';
		$this->filter = $filter;
		$this->section_map();
	}

	public function set_path($path) {
		$this->path = $path;
	}

	public function set_iblock($iblock_id) {
		$this->iblockId = $iblock_id;
	}

	public function get_path() {
		return $this->path;
	}

	public function write($row) {
		$fp = fopen($this->path, 'a');
		fwrite($fp, implode(';',$row).PHP_EOL);
		fclose($fp);
	}

	public function clear_path() {
		unlink($this->path);
	}

	public function process () {
		$this->clear_path();
		$result = false;

		$arSelect = array (
			'ID',
			'NAME',
			'IBLOCK_SECTION_ID',
			'CATALOG_GROUP_1',
			'PROPERTY_BRANDS_REF',
		);

		$this->filter["IBLOCK_ID"] = $this->iblockId;
		$this->filter["ACTIVE"] = "Y";
		$this->filter["INCLUDE_SUBSECTIONS"] = "Y";

		$res = CIBlockElement::GetList(Array(), $this->filter, false, Array("nPageSize"=>$this->step), $arSelect);
		while($arFields = $res->Fetch())
		{

			/*echo '<pre>';
			//print_r($arFields);
			print_r($ob);*/
			$row = array (
			  	$arFields['ID'],
			  	iconv('UTF-8','Windows-1251',$arFields['NAME']),
				iconv('UTF-8','Windows-1251',$this->section_map[$arFields['IBLOCK_SECTION_ID']]),
				$arFields['IBLOCK_SECTION_ID'],
				$arFields['PROPERTY_BRANDS_REF_VALUE'],
			  	$arFields['CATALOG_PRICE_1'],
			);
			$this->write($row);
		}

		if (isset($arFields)) {
			$result = true;
		}

		return $result;
	}

	public function section_map() {
		$res = CIBlockSection::GetList(
			 Array("SORT"=>"ASC"), 
			 Array("IBLOCK_ID"=>33, "ACTIVE"=>"Y"), 
			 true,
			 Array("ID", "NAME")
		  );
		while($arSection = $res->GetNext()) {
			  $this->section_map[$arSection['ID']] = $arSection['NAME'];
		};
	}
}	
?>