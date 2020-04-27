<?php
@define("PAGE_TYPE", "static");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Оплата");
CModule::IncludeModule("iblock");
$err = false;
define('TYPE_SEND', 'POST_OF_DOPLATA');
define('ID_SEND', 102);
use Bitrix\Main\Mail\Event;
require($_SERVER["DOCUMENT_ROOT"]."/doplata/lib/save_log.php");
require($_SERVER["DOCUMENT_ROOT"]."/doplata/lib/error_send.php");
?>

<?
if (!$_REQUEST['orderId']) {
	$err[] = 'Отсутствует номер заказа';
}

if (!$err) {
	//Проверка на существование заказа и смена статуса
	$arFilter = Array("IBLOCK_ID"=>IBLOCK_DOPLATA, "PROPERTY_ORDER_ID_SBER"=> $_REQUEST['orderId']);
	$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>1), array());
	if ($ob = $res->GetNextElement()) {
		$arFields = $ob->GetFields();
		$arFields['PROP'] = $ob->GetProperties();
		//Смена статуса оплаты на ОПЛАЧЕНО
		$PROPERTY_VALUES['STATUS'] = array(296);
		CIBlockElement::SetPropertyValuesEx($arFields['ID'], IBLOCK_DOPLATA, $PROPERTY_VALUES);
		save_log($arFields['ID'], 'Смена статуса на `Оплачено`');

		//Отправка уведомления в ОП
		$rsUser = CUser::GetByID($arFields['PROP']['LINK_USER']['VALUE']);
		$arUser = $rsUser->Fetch();

		$arEventFields['ID'] = $ORDER_ID;
		$arEventFields['EMAIL_TO'] = $arUser['EMAIL'];
		$arEventFields['TIME'] = date("d.m.Y - H:i:s");
		$arEventFields['NAME'] = $arFields['NAME'];
		$arEventFields['ORDER'] = $arFields['PROP']['NUM_ORDER']['VALUE'];
		$arEventFields['CUSTOMER'] = $arFields['PROP']['CUSTOMER']['VALUE'];
		$arEventFields['SUMM'] =$arFields['PROP']['SUMM']['VALUE'];
		$result_send = Event::send(array(
		    "EVENT_NAME" => TYPE_SEND,
		    "LID" => "s1",
		    "C_FIELDS" => $arEventFields,
		    'MESSAGE_ID' => ID_SEND,
		)); 
	} else {
		$err[] = 'Заказ не найден';
	}

	//Получение информации об ИП
	if (!$err) {
		$arFilter_org = Array("IBLOCK_ID"=>59, "ID"=> $arFields['PROP']['LINK_OP']['VALUE']);
		$res_org = CIBlockElement::GetList(Array(), $arFilter_org, false, Array("nPageSize"=>1), array());
		if ($ob_org = $res_org->GetNextElement()) {
			$arFields_org = $ob_org->GetFields();
			$arFields_org['PROP'] = $ob_org->GetProperties();
		} else {
			$err[] = 'Ошибка получения данных для чека';
		}
	}

	if (!$err && $arFields['PROP']['STATUS_CASHBOX']['VALUE'] == '') {

		//$url = "https://fce.chekonline.ru:4443/fr/api/v2/Complex";
		$url = "https://kkt.chekonline.ru:4443/fr/api/v2/Complex";

		$data = array(
			"ClientId" => $arFields_org['PROP']['CLIENT_ID']['VALUE'],
			//"Password" => '1',
			"Device" => "auto",
			'RequestId' => $arFields['ID'].uniqid(),
			"Lines" => array(),
			"NonCash" => array($arFields['PROP']['SUMM']['VALUE'] * 100),
			//"TaxMode" => 2,
			"PhoneOrEmail" => $arFields['PROP']['EMAIL']['VALUE'],
		);

		$data['Lines'][]=array(
			"Qty" => 1000, 
			"Price" => $arFields['PROP']['SUMM']['VALUE'] * 100,
			"PayAttribute" => 4,
			"TaxId" => 4,
			"Description"=> $arFields['NAME'],
		);

			/*echo '<pre>';
			print_r($data);
			echo '</pre><br /><br />';*/

		$mydata = json_encode($data);
			//echo 'json - '.$mydata.'<br /><br />';

		$cert_url = $_SERVER["DOCUMENT_ROOT"].'/doplata/'.$arFields_org['ID'].'/certificate.pem';
		$key_url = $_SERVER["DOCUMENT_ROOT"].'/doplata/'.$arFields_org['ID'].'/privateKey.pem';

			/*echo $cert_url.'<br />';
			echo $key_url.'<br /><br />';
			echo $url.'<br />';

			echo '<pre>';
			$key = file_get_contents($key_url);
			echo $key.'<br /><br />';

			$cert = file_get_contents($cert_url);
			echo $cert.'<br />';

			echo '</pre>';*/

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $mydata);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,60);

		// Сертификат
		curl_setopt($curl,CURLOPT_SSLCERT, $cert_url);
		// Закрытый ключ
		curl_setopt($curl,CURLOPT_SSLKEY, $key_url);
		curl_setopt($curl, CURLOPT_CAINFO, $_SERVER["DOCUMENT_ROOT"].'/doplata/cacert.pem');
		//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$json_response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$lastError = curl_error($curl);
		curl_close($curl);
		$response = json_decode($json_response);

		$log_error = "Статус CURL ".$status."\r\n";
		$log_error .= "Last Error ".$lastError."\r\n";
		$log_error .= "Response ".$json_response."\r\n";

		if($response->Response->Error == 0) {
			//Меняем статус чека на успешно
			$PROPERTY_VALUES['STATUS_CASHBOX'] = array(302);
			CIBlockElement::SetPropertyValuesEx($arFields['ID'], IBLOCK_DOPLATA, $PROPERTY_VALUES);

			//---LOG---
			save_log($arFields['ID'], "Чек напечатан\r\n"."Номер документа - ".$response->DocNumber."\r\n"."Номер фискального документа - ".$response->FiscalDocNumber."\r\n");
		} else {
			//Меняем статус чека на ошибку печати
			$PROPERTY_VALUES['STATUS_CASHBOX'] = array(301);
			CIBlockElement::SetPropertyValuesEx($arFields['ID'], IBLOCK_DOPLATA, $PROPERTY_VALUES);

			//---LOG---
			save_log($arFields['ID'], "Ошибка печати чека - ".$response->Response->Error."\r\n".$log_error);
		}
	}


?>

<?if(!$err):?>
	<p>Заказ успешно оплачен</p>
<?endif?>

<!--<p>Информация о чеке</p>
<p>Номер документа - <?=$response->DocNumber?></p>
<p>Номер фискального документа - <?=$response->FiscalDocNumber?></p>
<p>Код ошибки - <?=$response->Response->Error?></p>-->


<?
	
} else {?>
	<?foreach($err as $error):?>
		<div class="alert-block alert-block_error">
			<?=$error?>
		</div>
	<?endforeach?>
<?}?>


<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>
