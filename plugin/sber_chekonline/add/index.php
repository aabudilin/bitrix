<?php
@define("PAGE_TYPE", "static");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
CModule::IncludeModule("iblock");
require_once($_SERVER['DOCUMENT_ROOT']."/doplata/lib/functions.php");
$APPLICATION->SetTitle("Доплата");
$err = false;

global $USER;
$rsUser = CUser::GetList(($by="ID"), ($order="desc"), array("ID"=>$USER->GetID()),array("SELECT"=>array("UF_*")));
$arUser = $rsUser->Fetch();

$id_org = $arUser['UF_LINK_ORG'];

if ($id_org) {
	$res = CIBlockElement::GetByID($id_org);
	$ob = $res->GetNextElement();
	$arOrg = $ob->GetFields();
} else {
	$err[] = 'Отсутсвует привязка к организации';
}


if($_REQUEST['PROP']) {
	$result = save_order($_REQUEST);
	//print_r($result);
}
?>

<style>
	.form-pay__op {
		margin-bottom:30px;
	}

	.form-item {
		float:left;
		width:100%;
		margin-bottom:20px;
	}

	.form-item label{
		display:inline-block;
		width:270px;
	}

	.form-item input {
		padding:10px;
		border:solid 1px #dedede;
		width:250px;
	}

	.form-item .input-long {
		width:600px;
	}

	.form-item textarea {
		height:150px;
		padding:10px;
		border:solid 1px #dedede;
	}

	.form-title {
		font-size:18px !important;
		color:#000;
		padding:30px 0;
	}

	.btn-left {
		margin:0;
	}

	.success-message {
		color:#729e00;
	}

	.error-message {
		color:red;
	}

	.filter-btn {
		padding:15px 40px;
		border-radius:30px;
	}

	.block-org {
		padding:10px;
		background:#f1f1f1;
		color:#474747;
	}

	.bx-auth a {
		display:none;
	}
</style>

<?if($result['status'] == 'success'):?>
	<?foreach($result['message'] as $message):?>
		<p class="success-message"><?=$message?></p>
	<?endforeach?>
<?endif?>

<?if($result['status'] == 'error'):?>
	<?foreach($result['message'] as $message):?>
		<p class="error-message"><?=$message?></p>
	<?endforeach?>
<?endif?>

<?if ($err):?>
	<?foreach($err as $message):?>
		<p class="error-message"><?=$message?></p>
	<?endforeach?>
<?else:?>

<p><span class="block-org">Организация - <?=$arOrg['NAME']?></span></p>

<form action="<?=$APPLICATION->GetCurPage()?>" class="form-pay__op" method="POST">
	<p class="form-title"><b>Параметры заказа</b></p>
	<div class="form-item">
		<label for="f1">Наименование товара *</label>
		<input type="text" name="NAME" placeholder="Наименование товара" class="input-long" id="f1" requred />
	</div>

	<div class="form-item">
		<label for="f1-1"></label>
		<textarea name="PREVIEW_TEXT" placeholder="Дополнительное описание" class="input-long" id="f1-1"></textarea>
	</div>


	<div class="form-item">
		<label for="f2">Фабричный номер заказа *</label>
		<input type="text" name="PROP[NUM_ORDER]" placeholder="Номер заказа" id="f2"  required />
	</div>

	<div class="form-item">
		<label for="f3">Сумма доплаты (руб.) *</label>
		<input type="text" name="PROP[SUMM]" placeholder="Сумма" id="f3"  required />
	</div>

	<p class="form-title"><b>Информация о заказчике</b></p>
	<div class="form-item">
		<label for="f4">Ф.И.О. *</label>
		<input type="text" name="PROP[CUSTOMER]" placeholder="Ф.И.О." class="input-long" id="f4" requred />
	</div>

	<div class="form-item">
		<label for="f5">Телефон *</label>
		<input type="text" name="PROP[PHONE]" placeholder="Телефон" id="f5" required />
	</div>

	<div class="form-item">
		<label for="f6">Эл. почта *</label>
		<input type="text" name="PROP[EMAIL]" placeholder="Эл. почта" id="f6" required />
	</div>

	<input type="hidden" name="PROP[LINK_OP]" value="<?=$id_org?>" />

	<p><br /></p>
	<button type="submit" class="c-btn is-color-green filter-btn btn-left">Оформить доплату</button>

</form>

<hr />
<p class="form-title"><b>Справочная информация</b></p>
<p>Внимательно вводите адрес электронной почты клиента, т.к. на него будет выслана ссылка на оплату.</p>

<?endif?>

<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>