<?php
	@define("PAGE_TYPE", "static");
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	$APPLICATION->SetTitle("Оплата");
	define('GATE_TRY', 30);
	use Bitrix\Main\Loader;
	Loader::includeModule("iblock");
	require($_SERVER["DOCUMENT_ROOT"]."/doplata/lib/save_log.php");


	function get_order($id_order) {
		$arFilter = Array("IBLOCK_ID"=>IBLOCK_DOPLATA, "ID"=> $id_order);
		$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>1), array());
		if ($ob = $res->GetNextElement()) {
			  $arFields = $ob->GetFields();
			  $arFields["PROP"] = $ob->GetProperties();
			}
		return $arFields;;
	}

	function get_org_info ($id_user) {
		//Получаем данные юзера
		//$id_user = $GLOBALS['USER']->GetID();
		$rsUser = CUser::GetList(($by="ID"), ($order="desc"), array("ID"=>$id_user),array("SELECT"=>array("UF_*")));
		$arUser = $rsUser->Fetch();
		if ($arUser['UF_LINK_ORG']) {
			$arFilter = Array("IBLOCK_ID"=>59, "ID"=> $arUser['UF_LINK_ORG']);
			$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>1), array());
			if ($ob = $res->GetNextElement()) {
				$arFields = $ob->GetFields();
				$arFields["PROP"] = $ob->GetProperties();
			}
			return $arFields;

		} else {
			return false;
		}
	}

	class SberPay {

		const test_url = 'https://3dsec.sberbank.ru/payment/rest/';
	    const prod_url = 'https://securepayments.sberbank.ru/payment/rest/';
	    const return_page = 'https://pushe.ru/doplata/result.php';
	    const method = 'register.do';
	    public $data;


		public function prepare_data ($bundle) {
			$this->data['language'] = 'ru';
			$this->data['returnUrl'] = self::return_page;
			$this->data['order_bundle'] = $bundle['order_bundle'];
			$this->data['order_bundle'] = \Bitrix\Main\Web\Json::encode($this->data['order_bundle']);
		}

		public function set_data($key,$param) {
			$this->data[$key] = $param;
		}

		public function register_order($order_number) {

			$this->set_data('orderNumber',$order_number);

			//$this->view_log('this->data',$this->data);

			$dataEncoded = http_build_query($this->data);

			//$this->view_log('dataEncoded',$dataEncoded);

			$url = self::prod_url.self::method;
			//$this->view_log('url',$url);

			//Отправка запроса
			$curl = curl_init();
		    curl_setopt_array($curl, array(
			    CURLOPT_URL => $url,
			    CURLOPT_RETURNTRANSFER => true,
			    CURLOPT_POST => true,
			    CURLOPT_POSTFIELDS => $dataEncoded,
			    //CURLOPT_HTTPHEADER => array('CMS: Bitrix', 'Module-Version: ' . RBS_VERSION),
			    CURLOPT_SSLVERSION => 6
		    ));
		    $response = curl_exec($curl);
		    curl_close($curl);
			$arAnswer = json_decode($response,true);
			return $arAnswer;
		}

		public function view_log($name,$value) {
			if (is_array($value)) {
				echo '<p><b>Массив: '.$name.'</b><p>';
				echo '<pre>'.print_r($value,true).'</pre>';
			} else {
				echo '<p><b>'.$name.'</b><br />'.$value.'</p>';
			}
		}
	}

/***********END CLASS ***********/

		//Получение заказа
		$arOrder = get_order($_REQUEST['id']);
		//print_r($arOrder);

		$arOrg = get_org_info($arOrder['PROP']['LINK_USER']['VALUE']);

		if (!$arOrg) {
			?>
				<p style="color:red;"><b>Ошибка:</b> не получены данные организации, возможно к вашему аккаунту нет привязки</p>
			<?
		} else {

		$order = new SberPay();
		$order->set_data('userName',$arOrg['PROP']['LOGIN_SBER']['VALUE']);
		$order->set_data('password',$arOrg['PROP']['PASS_SBER']['VALUE']);
		$order->set_data('amount',round($arOrder['PROP']['SUMM']['VALUE'] * 100));
		$order->set_data('description',$arOrder['NAME'].' - '.$arOrder['PROP']['NUM_ORDER']['VALUE']);

		//orderNumber

		$arr_items[] = array (
			'positionId' => 1,
			'name' => $arOrder['NAME'],
			'quantity' => array (
				'value' => 1,
				'measure' => 'шт.',
			),
			'itemAmount' => round($arOrder['PROP']['SUMM']['VALUE'] * 100),
			'itemPrice' => round($arOrder['PROP']['SUMM']['VALUE'] * 100),
			'itemCode' => $arOrder['ID'],
			'tax' => array (
				'taxType' => 0
			)
		);

		$order_bundle_arr = array(
			'order_bundle' => array (
				'customerDetails' => array (
					'email' => $arOrder['PROP']['EMAIL']['VALUE'],
					'phone' => $arOrder['PROP']['PHONE']['VALUE'],
				),
				'cartItems' => array (
					'items' => $arr_items,
				),
			),
		);

		$order->prepare_data($order_bundle_arr);

		//$data['order_bundle'] = \Bitrix\Main\Web\Json::encode($data['order_bundle']);

		for ($i = 0; $i <= GATE_TRY; $i++) {
		    $response = $order->register_order($arOrder['ID'] . '_' . $i);
		    if ($response['errorCode'] != 1) break;
		}

		//Запись  [orderId] => f8e487ff-9d33-72da-a6bb-9e575e2e14d8
		if ($response['orderId']) {
			$PROPERTY_VALUES['ORDER_ID_SBER'] = $response['orderId'];
			CIBlockElement::SetPropertyValuesEx($_REQUEST['id'], IBLOCK_DOPLATA, $PROPERTY_VALUES);
			save_log($_REQUEST['id'],'Сформирована ссылка на оплату');
		} else {
			save_log($_REQUEST['id'],"Ошибка получения ссылки на оплату\r\n".implode('; '.$response));
		}

	?>

	<style>
		.btn-sale {
			background:#b41e87;
			color:#fff;
			display:inline-block;
			width:auto;
			padding:10px 50px;
			box-shadow:0px 3px 5px #dedede;
			transition:0.3s;
			border-radius:50px;
		}

		.btn-sale:hover {
			background:#861764;
			color:#fff;
			box-shadow:0px 7px 10px #dedede;
		}
	</style>

	<p>Оплата заказа #<?=$arOrder['PROP']['NUM_ORDER']['VALUE']?> на сумму <?=$arOrder['PROP']['SUMM']['VALUE']?> руб.</p>
	<p>Наименование: <?=$arOrder['NAME']?></p>
	<p><br /></p>
	<?if ($response['formUrl']):?>
		<p><a href="<?=$response['formUrl']?>" class="btn-sale">Оплатить заказ</a></p>
	<?else:?>
		<p>Ошибка формирования ссылки на оплату. Обратитесь к администрации сайта</p>
	<?endif?>

	<?}//endif arOrg?>


	<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>