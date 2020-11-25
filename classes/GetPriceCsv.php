<?
class GetPriceCsv{
	
	protected $path = 'price.csv';
	protected $iblockId = '33';
	protected $step = 40000;
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
		ob_implicit_flush(true);

		$this->clear_path();
		$result = false;

		$arSelect = array (
			'ID',
			'NAME',
			'IBLOCK_SECTION_ID',
			'CATALOG_GROUP_1',
			'PROPERTY_BRANDS_REF',
			'PURCHASING_PRICE',
		);

		$this->filter["IBLOCK_ID"] = $this->iblockId;
		$this->filter["ACTIVE"] = "Y";
		$this->filter["INCLUDE_SUBSECTIONS"] = "Y";

		$count = CIBlockElement::GetList(false, $this->filter, array(), false, array('ID'));

		if($count > 0) {

			$process_step = intval($count / 1000) + 1;

			echo 'Найдено товаров - '.$count;

			for ($i = 1; $i <= $process_step; $i++) {

				$res = \CIBlockElement::GetList(Array(), $this->filter, false, Array("nPageSize"=>1000, 'iNumPage' => $i), $arSelect);
				while($arFields = $res->Fetch())
				{
					$row = array (
					  	$arFields['ID'],
					  	iconv('UTF-8','Windows-1251',$arFields['NAME']),
						iconv('UTF-8','Windows-1251',$this->section_map[$arFields['IBLOCK_SECTION_ID']]),
						$arFields['IBLOCK_SECTION_ID'],
						$arFields['PROPERTY_BRANDS_REF_VALUE'],
					  	intval($arFields['CATALOG_PRICE_1']),
					  	intval($arFields['CATALOG_PURCHASING_PRICE']),
					);
					$this->write($row);
					unset($arFields);
					unset($row);
				}
				unset($res);
			}
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