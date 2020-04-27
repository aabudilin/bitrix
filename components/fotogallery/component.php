<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Context,
	Bitrix\Main\Type\DateTime,
	Bitrix\Main\Loader,
	Bitrix\Iblock;

Loader::includeModule("iblock");

$arGallery = array();

$arFilter = Array("IBLOCK_ID"=>$arParams['IBLOCK_ID'], "ID"=> $arParams['ELEMENT_ID']);
$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>1), array());
if ($ob = $res->GetNextElement()) {
	$arFields = $ob->GetFields();
	$arFields["PROP"] = $ob->GetProperties();
}

if (count($arFields['PROP']) > 0) {
	foreach ($arFields['PROP']['GALLERY']['VALUE'] as $id_img) {
		$full_img = CFile::GetFileArray($id_img);
		$prev_img = CFile::ResizeImageGet($id_img, array('width'=>800, 'height'=>600), BX_RESIZE_IMAGE_EXACT, true);
		$arGallery[] = array (
			'PREV_IMG' => $prev_img['src'],
			'FULL_IMG' => $full_img['SRC'],
		);
	}
}

$arResult['ITEMS'] = $arGallery;

$this->IncludeComponentTemplate();