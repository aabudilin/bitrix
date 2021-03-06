<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

IncludeModuleLangFile(__FILE__);

CModule::IncludeModule('sale');
CModule::IncludeModule('catalog');

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/update_class.php');

session_start();

require_once(realpath(dirname(dirname(__FILE__))) . "/config.php");

require_once("rbs.php");
require_once('rbs-discount.php');

$module_id = RBS_MODULE_ID;

$MODULE_PARAMS = [];
$MODULE_PARAMS['RETURN_PAGE'] = '/doplata/result.php';
$MODULE_PARAMS['GATE_TRY'] = API_GATE_TRY;
$MODULE_PARAMS['GATE_SEND_COMMENT'] = '';

$test_mode = true;

/*if (CSalePaySystemAction::GetParamValue("TWO_STAGE") == 'Y') {
    $two_stage = true;
} else {
    $two_stage = false;
}*/

$logging = true;

$auto_open_form = true;

global $APPLICATION;

$curUrl = $APPLICATION->GetCurDir();

$ffd_format = CSalePaySystemAction::GetParamValue("FFD_FORMAT");
$ffd_paymentMethod = CSalePaySystemAction::GetParamValue("FFD_PAYMENT_METHOD");
$ffd_paymentObject = CSalePaySystemAction::GetParamValue("FFD_PAYMENT_OBJECT");

$params['user_name'] = 'pushe_3-api';
$params['password'] = 'pushe_3';
$params['two_stage'] = $two_stage;
$params['test_mode'] = $test_mode;
$params['logging'] = $logging;

$params['language'] = LANGUAGE_ID;

$rbs = new RBS($params);

$rbsArrTax = $rbs->get_tax_list();

$app = \Bitrix\Main\Application::getInstance();

$request = $app->getContext()->getRequest();

$order_number = CSalePaySystemAction::GetParamValue("ORDER_NUMBER");

if (CUpdateSystem::GetModuleVersion('sale') <= "16.0.11") {
    $orderId = $order_number;
} else {
    $entityId = CSalePaySystemAction::GetParamValue("ORDER_PAYMENT_ID");
    list($orderId, $paymentId) = \Bitrix\Sale\PaySystem\Manager::getIdsByPayment($entityId);
}


if (!$order_number)
    $order_number = $orderId;
if (!$order_number)
    $order_number = $GLOBALS['SALE_INPUT_PARAMS']['ID'];

if (!$order_number)
    $order_number = $_REQUEST['ORDER_ID'];

//Получение заказа
$arOrder = CSaleOrder::GetByID($orderId);

//Получение списка оплат
$dbRes = \Bitrix\Sale\PaymentCollection::getList([
    'select' => ['*'],
    'filter' => [
        '=ORDER_ID' => $orderId, 
    ]
]);

$this_payment = array();//Массив оплаты
while ($item = $dbRes->fetch())//Получаем первую неоплаченную оплату
{
    if($item['PAID'] == 'N') {
        $this_payment = $item;
        break;
    }
}

//print_r($payment);

$currency = $arOrder['CURRENCY'];

//$amount = CSalePaySystemAction::GetParamValue("AMOUNT") * 100; -- old
$amount = round($this_payment['SUM']) * 100;
$new_order = $orderId.'/'.$this_payment['ID'];
$order_number = $new_order;


$rbs_discount = new rbsDiscount([
    'order_id' => $new_order,
    'amount' =>  $this_payment['SUM'],
]);

if (is_float($amount)) {
    $amount = round($amount);
}
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off" ? 'https://' : 'http://';
$return_url = $protocol . $_SERVER['SERVER_NAME'] . $MODULE_PARAMS['RETURN_PAGE'] . '?ID=' . $arOrder['ID'];

$FISCALIZATION = COption::GetOptionString($module_id, "FISCALIZATION", serialize(array()));
$FISCALIZATION = unserialize($FISCALIZATION);

if ($FISCALIZATION['ENABLE'] == 'Y') {

    $arFiscal = array(
        'orderBundle' => array(
            'orderCreationDate' => strtotime($arOrder['DATE_INSERT']),
            'customerDetails' => array(
                'email' => false,
                'contact' => false,
            ),
            'cartItems' => array(
                'items' => array(),
            ),
        ),
        'taxSystem' => $FISCALIZATION['TAX_SYSTEM']
    );
    $db_props = CSaleOrderPropsValue::GetOrderProps($arOrder['ID']);

    while ($props = $db_props->Fetch()) {
        if ($props['IS_PAYER'] == 'Y') {
            $arFiscal['orderBundle']['customerDetails']['contact'] = $props['VALUE'];
        } elseif ($props['IS_EMAIL'] == 'Y') {
            $arFiscal['orderBundle']['customerDetails']['email'] = $props['VALUE'];
        }
    }
    if (!$arFiscal['orderBundle']['customerDetails']['email'] || !$arFiscal['orderBundle']['customerDetails']['contact']) {
        global $USER;
        if (!$arFiscal['orderBundle']['customerDetails']['email'])
            $arFiscal['orderBundle']['customerDetails']['email'] = $USER->GetEmail();
        if (!$arFiscal['orderBundle']['customerDetails']['contact'])
            $arFiscal['orderBundle']['customerDetails']['contact'] = $USER->GetFullName();
    }

    $measureList = array();
    $dbMeasure = CCatalogMeasure::getList();
    while ($arMeasure = $dbMeasure->GetNext()) {
        $measureList[$arMeasure['ID']] = $arMeasure['MEASURE_TITLE'];
    }


    $vatGateway = unserialize(COption::GetOptionString($module_id, "VAT_LIST", serialize(array())));
    $vatDeliveryGateway = unserialize(COption::GetOptionString($module_id, "VAT_DELIVERY_LIST", serialize(array())));

    $itemsCnt = 1;
    $arCheck = null;

    $dbRes = CSaleBasket::GetList(array(), array('ORDER_ID' => $orderId));
    $priceSumm = 0;
    while ($arRes = $dbRes->Fetch()) {
        $itemsCnt++;

        $arProduct = CCatalogProduct::GetByID($arRes['PRODUCT_ID']);

        $productVatItem = CCatalogVat::GetByID($arProduct['VAT_ID'])->Fetch();
        $productVatValue = 0;
        foreach ($rbsArrTax as $key => $value) {
            if ($value == $productVatItem['RATE']) {
                $productVatValue = $key;
            }
        }


        $itemAmount = $arRes['PRICE'] * 100;
        if(is_float($itemAmount)) {
            $itemAmount = round($itemAmount);
        }
        $arrSetProduct = [
            'id' => $arRes['PRODUCT_ID'], 
            'name' => $arRes['NAME'],
            'priceBase' => $arRes['PRICE'],
            'count' => $arRes['QUANTITY'],
            'arrGate' => [
                'quantity' => array(
                    'measure' => $measureList[$arProduct['MEASURE']] ? $measureList[$arProduct['MEASURE']] : GetMessage('RBS_PAYMENT_MEASURE_DEFAULT'),
                ),
                'tax' => array(
                    'taxType' => $productVatValue,
                ),
            ]
        ];
        if($ffd_format == 'v2') {
            $arrSetProduct['itemAttributes'] = [
                'attributes' => [
                    [
                        'name' => 'paymentMethod',
                        'value' => $ffd_paymentMethod
                    ],
                    [
                        'name' => 'paymentObject',
                        'value' => $ffd_paymentObject
                    ],
                ]
            ];
        }
        $rbs_discount->addProduct($arrSetProduct);
        $priceSumm += round($arRes['PRICE'] * $arRes['QUANTITY'],2);
    }
    $checkSumm = round($priceSumm-($arOrder['PRICE'] - $arOrder['PRICE_DELIVERY']),2);
    if($checkSumm == 0 ) {
        $needSetDiscount = 0;
    } else if($checkSumm > 0 ) {
        $needSetDiscount = $checkSumm;
    } else if($checkSumm < 0) {
        echo "<b>ERROR: checkSumm < 0</b>";
        die;
    }
    $rbs_discount->setOrderDiscount($needSetDiscount);
    $rbs_discount->updateOrder();
    $arFiscal['orderBundle']['cartItems']['items'] = $rbs_discount->getBasketResult();

    if ($arOrder['PRICE_DELIVERY'] > 0) {

        if (!$arDelivery = CSaleDelivery::GetByID($arOrder['DELIVERY_ID'])) {
            $filter = is_numeric($arOrder['DELIVERY_ID']) ? ['ID' => $arOrder['DELIVERY_ID']] : ['CODE' => $arOrder['DELIVERY_ID']];
            $arDelivery = \Bitrix\Sale\Delivery\Services\Table::getList(array(
                'order' => array('SORT' => 'ASC', 'NAME' => 'ASC'),
                'filter' => $filter
            ))->Fetch();
        }

        $deliveryVatItem = CCatalogVat::GetByID($arDelivery['VAT_ID'])->Fetch();
        $deliveryVatValue = 0;
        foreach ($rbsArrTax as $key => $value) {
            if ($value == $deliveryVatItem['RATE']) {
                $deliveryVatValue = $key;
            }
        }

        $arrSetDelivery = array(
            'positionId' => $itemsCnt++,
            'name' => GetMessage('RBS_PAYMENT_DELIVERY_TITLE'),
            'quantity' => array(
                'value' => 1,
                'measure' => GetMessage('RBS_PAYMENT_MEASURE_DEFAULT'),
            ),
            'itemAmount' => round($arOrder['PRICE_DELIVERY'] * 100),
            'itemCode' => $arOrder['ID'] . "_DELIVERY",
            'itemPrice' => round($arOrder['PRICE_DELIVERY'] * 100),
            'tax' => array(
                'taxType' => $deliveryVatValue,
            ),
        );

        if($ffd_format == 'v2') {
            $arrSetDelivery['itemAttributes'] = [
                'attributes' => [
                    [
                        'name' => 'paymentMethod',
                        'value' => '4'
                    ],
                    [
                        'name' => 'paymentObject',
                        'value' => '4'
                    ],
                ]
            ];
        }
        $arFiscal['orderBundle']['cartItems']['items'][] = $arrSetDelivery;

    }
}

$gate_comment = '';

if (in_array('FIO', $MODULE_PARAMS['GATE_SEND_COMMENT'])) {
    $gate_comment .= $arOrder['USER_NAME'] . ' ' . $arOrder['USER_LAST_NAME'] . "\n";
}
if (in_array('COMMENT', $MODULE_PARAMS['GATE_SEND_COMMENT']) || empty($MODULE_PARAMS['GATE_SEND_COMMENT'])) {
    $gate_comment .= $arOrder['USER_DESCRIPTION'];
}

for ($i = 0; $i <= $MODULE_PARAMS['GATE_TRY']; $i++) {
    $response = $rbs->register_order($order_number . '_' . $i, $amount, $return_url, $currency, $gate_comment, $arFiscal);
    if ($response['errorCode'] != 1) break;
}

?>

<div class="sale-paysystem-wrapper">
    <?
    if (in_array($response['errorCode'], array(999, 1, 2, 3, 4, 5, 7, 8))) {

        $error = GetMessage('RBS_PAYMENT_PAY_ERROR_NUMBER') . ' ' . $response['errorCode'] . ': ' . $response['errorMessage'];
        ?><span><?= $error ?></span><?

    } elseif ($response['errorCode'] == 0) {

        $_SESSION['ORDER_NUMBER'] = $order_number;


        if ($auto_open_form && $curUrl != '/personal/orders/') {
            if ($request->get('ORDER_ID')) {
                echo '<script>window.location="' . $response['formUrl'] . '"</script>';
                // LocalRedirect($response['formUrl'],true);
            }
        }


        $arUrl = parse_url($response['formUrl']);
        parse_str($arUrl['query'], $arQuery);
        ?>
        <b><?= GetMessage('RBS_PAYMENT_PAY_SUM') ?><?= CurrencyFormat(CSalePaySystemAction::GetParamValue("AMOUNT"), $currency) ?></b>
        <!--<?=$order_number?>-->
        <form action="<?= $response['formUrl'] ?>" method="get">
            <? foreach ($arQuery as $key => $value): ?>
                <input type="hidden" name="<?= $key ?>" value="<?= $value ?>">
            <? endforeach ?>
            <div class="sale-paysystem-button-container" style="padding:10px 0">
                <input class="btn btn-default btn-buy btn-md"
                       value="<?= GetMessage('RBS_PAYMENT_PAY_BUTTON') ?>"
                       type="submit"/>
            </div>
            <p>
            <span class="tablebodytext sale-paysystem-description">
                <?= GetMessage('RBS_PAYMENT_PAY_DESCRIPTION') ?>
            </span>
            </p>
        </form>


        <?


    } else {
        $error = GetMessage('RBS_PAYMENT_PAY_ERROR');
        ?><span><?= $error ?></span><?
    }
    ?>
</div>