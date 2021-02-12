<?php

$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/www';

$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"]; 
define("NO_KEEP_STATISTIC", true); 
define("NOT_CHECK_PERMISSIONS", true); 
set_time_limit(0); 
//define("LANG", "ru");

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php"); 

debug::log($_SERVER['DOCUMENT_ROOT'].'/php/log.txt','Начало работы sitemap');

use Bitrix\Main;
use Bitrix\Main\Loader;
use Sotbit\Seometa\ConditionTable;
use Sotbit\Seometa\SeometaUrlTable;
$bIBlock = Main\Loader::includeModule('iblock');
$id_module='sotbit.seometa';
Loader::includeModule($id_module);

define('CITY_IBLOCK', 8);

//Получение городов
$ar_domains = array();
$arSelect = Array();
$arFilter = Array("IBLOCK_ID" => CITY_IBLOCK, "ACTIVE"=>"Y");
$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>150), $arSelect);
while($ob = $res->GetNextElement())
{
  $arFields = $ob->GetFields();
  $arFields["PROP"] = $ob->GetProperties();
  $ar_domains[$arFields['ID']] = array (
        'NAME' => $arFields['NAME'],
        'HOST' => 'https://'.$arFields['PROP']['FULL_URL']['VALUE'],
        'FILE' => $arFields['CODE'].'_sitemap.xml',
    );
} 

//Убираем Москву
unset($ar_domains[42]);

function write_map ($arr, $host,$priority='',$freq='') {
    $sitemap = '';
    $x = 0;
    foreach ($arr as $arPath) {

        if ($host == 'https://pushe.ru') {
            
            //Проверяем ответ сервера только для хоста https://pushe.ru
            if (get_headers($host.$arPath['URL'])[0] == 'HTTP/1.1 200 OK') {
                $sitemap .= '<url><loc>'.$host.$arPath['URL'].'</loc>';
                if($arPath['LASTMOD']) $sitemap .= '<lastmod>'.$arPath['LASTMOD'].'</lastmod>';
                if($freq) $sitemap .= '<changefreq>'.$freq.'</changefreq>';
                if($priority) $sitemap .= '<priority>'.$priority.'</priority>';
                $sitemap .= '</url>';    
            }

        } else {
            $sitemap .= '<url><loc>'.$host.$arPath['URL'].'</loc>';
            if($arPath['LASTMOD']) $sitemap .= '<lastmod>'.$arPath['LASTMOD'].'</lastmod>';
            if($freq) $sitemap .= '<changefreq>'.$freq.'</changefreq>';
            if($priority) $sitemap .= '<priority>'.$priority.'</priority>';
            $sitemap .= '</url>';    
        }
        
        $x++;
    }

    $result = array(
        'MAP' => $sitemap,
        'X'   => $x,
    );
    return $result;
}

function get_iblock($iblock_id) {
    $ar_result = array();
    $arSelect = Array("ID", "NAME", "DETAIL_PAGE_URL", "TIMESTAMP_X");
    $arFilter = Array("IBLOCK_ID"=>$iblock_id, "ACTIVE"=>"Y");
    $res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>5000), $arSelect);
    while($ob = $res->GetNextElement())
    {
      $arFields = $ob->GetFields();
      $ar_result[] = array (
         'URL'     => $arFields['DETAIL_PAGE_URL'],
         'LASTMOD' => date('Y-m-d',strtotime($arFields['TIMESTAMP_X'])),
      );
      $arFields = array();
    }

    return $ar_result;
}

function get_section ($iblock_id) {
    $result = array();
    $res = CIBlockSection::GetList(
        Array("SORT"=>"ASC"), 
        Array("IBLOCK_ID"=>$iblock_id, "ACTIVE"=>"Y", "GLOBAL_ACTIVE"=>"Y"), 
        true,
        Array("ID", "NAME", "SECTION_PAGE_URL", 'TIMESTAMP_X')
    );
    while($arSection = $res->GetNext()) {
        $result[] =  array (
         'URL'     => $arSection['SECTION_PAGE_URL'],
         'LASTMOD' => date('Y-m-d',strtotime($arSection['TIMESTAMP_X'])),
      );
    };

    return $result;
}


    //Глобальные переменные
    $messages = array();

    //Подготавливаем список доменов
    $ar_host = $ar_domains;

    $ar_host[] = array (
        'NAME' => 'Основной домен',
        'HOST' => 'https://pushe.ru',
        'FILE' => 'sitemap.xml',
    );

    //////////////////////////Получаем данные для выгрузки
    //Архив статичных файлов
    $staticPath = array(
        '/clients/delivery/companies/',// 'Доставка',
        '/contacts/address/',// 'Магазины',
        '/horeca/', //'Нестандарт',
        '/blog/', // => 'Блог',
        '/weeklysale/', // => 'Акции',
        '/readymade/', // => 'Сейчас в салонах',
        '/company/about/', // => 'О компании',
        '/company/ekskursiya-na-fabriku/', //Экскурсия на фабрику
        '/company/principles/', //Наши принципы
        '/company/pushe-na-tv/', //Пуше на ТВ
        '/company/blagotvoritelnost/', //Благотворительность
        '/clients/designers/', //Дизайнерам
        '/clients/arendodatelyam/', //Арендодателям
        '/company/vakansii/', //Вакансии
        '/clients/guarantee/', //Гарантия
        '/clients/return/', //Возврат
        '/clients/avtorskoe-pravo/', //Авторское право
    );

    $new_path = array();
    foreach ($staticPath as $url) {
        $new_path[] = array(
            'URL' => $url,
        );
    }
    $staticPath = $new_path;	

    ///Сбор по инфоблокам
    //Модульная мебель
    $arMebel = get_iblock(28);

    //Блог
    $arBlog = get_iblock(4);

    //Разделы
    $arSection = get_section(28);



    //Сбор SEO фильтра
    $ar_noindex = array();
	$rsData=ConditionTable::getList(array(
		'select' => array('NAME','ID','NO_INDEX'),
		'filter' => array('NO_INDEX' => 'Y'),
		'limit' =>1000,
	));
	while($arRes = $rsData->Fetch())
	{
		$ar_noindex[$arRes['ID']] = $arRes;
	}

	//print_r($ar_noindex);

    $arSEO = array();
    $arFilter['CATEGORY_ID'] = 0;
    $rsData = SeometaUrlTable::getList(array(
		'select' => array('ID', 'NAME', 'CONDITION_ID', 'ACTIVE', 'REAL_URL', 'NEW_URL', 'IN_SITEMAP', 'iblock_id', 'section_id', 'PRODUCT_COUNT', 'DATE_CHANGE', 'PROPERTIES')
        //'filter' => $arFilter
    ));

    while($arRes = $rsData->Fetch())
    {
		
        if ($arRes['ACTIVE'] == 'Y' && !is_array($ar_noindex[$arRes['CONDITION_ID']])) {
            $arSEO[] = array (
                'URL'     => $arRes['NEW_URL'],
                'LASTMOD' => date('Y-m-d',strtotime($arRes['DATE_CHANGE'])),
            );
        } else {
        	//print_r($arRes);
        }
    }

    //---------------------------------------

    foreach ($ar_host as $host) {

        $filename = $host['FILE'];
        $path_sitemap =  $_SERVER['DOCUMENT_ROOT'].'/'.$filename;
        if ($host['HOST'] != 'https://pushe.ru') $path_sitemap =  $_SERVER['DOCUMENT_ROOT'].'/sitemap/'.$filename;
        $x = 0; //Счетчик количества записей
        $sitemap = '';

        if ($host['HOST'] == 'https://pushe.ru') {
            $ar_res = array();
            $ar_res = write_map($staticPath,$host['HOST'],'0.8','weekly');
            $sitemap .= $ar_res['MAP'];
            $x = $x + $ar_res['X'];

            $ar_res = array();
            $ar_res = write_map($arBlog,$host['HOST'],'0.8','weekly');
            $sitemap .= $ar_res['MAP'];
            $x = $x + $ar_res['X'];
        }

        $ar_res = array();
        $ar_res = write_map($arMebel,$host['HOST'],'1','weekly');
        $sitemap .= $ar_res['MAP'];
        $x = $x + $ar_res['X'];

        $ar_res = array();
        $ar_res = write_map($arSEO,$host['HOST'],'1','weekly');
        $sitemap .= $ar_res['MAP'];
        $x = $x + $ar_res['X'];

        $ar_res = array();
        $ar_res = write_map($arSection,$host['HOST'],'1','weekly');
        $sitemap .= $ar_res['MAP'];
        $x = $x + $ar_res['X'];

        //формирование sitemap
        $result = '<?xml version="1.0" encoding="UTF-8"?>
                   <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $result .= $sitemap;
        $result .= '</urlset>';

        //Пишем в файл
        file_put_contents($path_sitemap, $result, LOCK_EX);

        //Пишем в csv
        if ($host['HOST'] == 'https://pushe.ru') {
            $csvname = 'sitemap.csv';
            $path_csv =  $_SERVER['DOCUMENT_ROOT'].'/'.$csvname;

            $new_arr = array();
            foreach ($staticPath as $field) {
                $new_arr[] = array('https://pushe.ru'.$field['URL']);
            }

            foreach ($arBlog as $field) {
                $new_arr[] = array('https://pushe.ru'.$field['URL']);
            }

            foreach ($arSEO as $field) {
                $new_arr[] = array('https://pushe.ru'.$field['URL']);
            }

            foreach ($arMebel as $field) {
                $new_arr[] = array('https://pushe.ru'.$field['URL']);
            }

            foreach ($arSection as $field) {
                $new_arr[] = array('https://pushe.ru'.$field['URL']);
            }

            $fp = fopen($path_csv, 'w');
            foreach ($new_arr as $fields) {
                fputcsv($fp, $fields, ';');
            }

            fclose($fp);

            $messages[] = 'CSV на '.$host['NAME'].' сгенерирован - <a href="'.$_SERVER[HTTP_HOST].'/'.$csvname.'" target="_blanck">'.$_SERVER[HTTP_HOST].'/'.$csvname.'</a>';

        }
        if ($host['HOST'] == 'https://pushe.ru') {
        $messages[] = 'Sitemap на '.$host['NAME'].' сгенерирован - <a href="'.$_SERVER[HTTP_HOST].'/'.$filename.'" target="_blanck">'.$_SERVER[HTTP_HOST].'/'.$filename.'</a>';
         } else {
                 $messages[] = 'Sitemap на '.$host['NAME'].' сгенерирован - <a href="'.$_SERVER[HTTP_HOST].'/sitemap/'.$filename.'" target="_blanck">'.$_SERVER[HTTP_HOST].'/sitemap/'.$filename.'</a>';
            }
        $messages[] = 'Количество записей - '.$x;
    }
/**/


//debug::log($_SERVER['DOCUMENT_ROOT'].'/php/log.txt','Конец работы sitemap');

?>
