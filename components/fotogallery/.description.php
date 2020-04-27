<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => 'Фотогалереия',
	"DESCRIPTION" => 'Фотогалерея на основе инфоблоков',
	"ICON" => "/images/news_detail.gif",
	"SORT" => 30,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "standart",
		"CHILD" => array(
			"ID" => "fotogallery",
			"NAME" => "Фотогалерея",
			"SORT" => 10,
		),
	),
);

?>