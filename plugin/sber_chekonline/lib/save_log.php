<?
	function save_log($id_order,$string) {
		$arFilter = Array("IBLOCK_ID"=>IBLOCK_DOPLATA, "ID"=> $id_order);
		$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>1), array('PROPERTY_LOG'));
		if ($ob = $res->GetNextElement()) {
			$arFields = $ob->GetFields();
			//print_r($arFields);
			$log = $arFields['PROPERTY_LOG_VALUE']['TEXT'];
			$log_string = $log."\r\n\r\n";
			$log_string .= date('d.m.Y H:i:s').' - '.$string;
			$PROPERTY_VALUES['LOG'] = $log_string;
			CIBlockElement::SetPropertyValuesEx($id_order, IBLOCK_DOPLATA, $PROPERTY_VALUES);
			//echo 'Лог записан';
			return true;
		} else {
			return false;
			//echo 'Ошибка записи лога';
		}
	}
?>