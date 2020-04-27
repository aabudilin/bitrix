<?
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");
define('IBLOCK_ORDER', 58);
define('TYPE_SEND', 'POST_OF_DOPLATA');
define('ID_SEND', 100);
use Bitrix\Main\Mail\Event;


function save_order($arParam) {
	//print_r($arParam);
	$result = array();
	$PROP = $arParam['PROP'];
	$PROP['STATUS'] = array(295);
	$PROP['LINK_USER'] = $GLOBALS['USER']->GetID();

	//Форматирование суммы
	$PROP['SUMM'] = str_replace(',','.',$PROP['SUMM']);
	$PROP['SUMM'] = str_replace(' ','',$PROP['SUMM']);

	$el = new CIBlockElement;
	$arLoad = Array(  
	   'MODIFIED_BY' => $GLOBALS['USER']->GetID(),
	   'IBLOCK_SECTION_ID' => false,
	   'IBLOCK_ID' => IBLOCK_ORDER,
	   'PROPERTY_VALUES' => $PROP,  
	   'NAME' => $arParam['NAME'],  
	   'PREVIEW_TEXT' => $arParam['PREVIEW_TEXT'],
	   'ACTIVE' => 'Y',
	);

	if($ORDER_ID = $el->Add($arLoad)) {
		$result['status'] = 'success';
		$arLog[] = date('d.m.Y H:i:s').' - оплата создана';

		$arEventFields['ID'] = $ORDER_ID;
		$arEventFields['EMAIL_TO'] = $arParam['PROP']['EMAIL'];
		$arEventFields['TIME'] = date("d.m.Y - H:i:s");
		$arEventFields['NAME'] = $arParam['NAME'];
		$arEventFields['ORDER'] = $arParam['PROP']['NUM_ORDER'];
		$arEventFields['CUSTOMER'] = $arParam['PROP']['CUSTOMER'];
		$arEventFields['SUMM'] = $arParam['PROP']['SUMM'];
		$arEventFields['PHONE'] = $arParam['PROP']['PHONE'];

		$result_send = Event::send(array(
		    "EVENT_NAME" => TYPE_SEND,
		    "LID" => "s1",
		    "C_FIELDS" => $arEventFields,
		    'MESSAGE_ID' => ID_SEND,
		)); 
		//print_r($result_send);

		$result['message'][] = 'Оплата успешно создана, информация о платеже отправлена покупателю';
	} else {
		$result['status'] = 'error';
		$result['message'][] = $el->LAST_ERROR;
	}

	return $result;

}