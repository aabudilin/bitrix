<?
$_SERVER['DOCUMENT_ROOT'] = '/home/t/tdtibenl/opttorg62.ru/public_html';
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER['DOCUMENT_ROOT']."/php/debug_class.php");
$APPLICATION->SetTitle("Загрузка данных");



use Bitrix\Main\Loader;

Loader::includeModule("iblock");
Loader::includeModule("catalog");

//ID инфоблока
define("CATALOG_IBLOCK", 14);
define("BRAND_IBLOCK", 6);
define("PATH_CSV", $_SERVER['DOCUMENT_ROOT'].'/upload.csv');
define('PATH_LOG', $_SERVER['DOCUMENT_ROOT'].'/manager/upload/log.txt');
define('PATH_STEP', $_SERVER['DOCUMENT_ROOT'].'/manager/upload/step.txt');
define('STEP', 500);

//Вывод лога
define('LOGGED', false);

$info = array(
  'new'    => 0,
  'update' => 0,
  'err'    => 0,
);

$log ='';

Debug::log(PATH_LOG,'Файл синхронизации запущен');

function to_utf8 ($arr) {
  if (is_array($arr)) {
    $new_arr = array();
    foreach ($arr as $item) {
      $new_arr[] = iconv("Windows-1251", "UTF-8", trim($item));
    }
  } else {
    $new_arr = iconv("Windows-1251", "UTF-8", trim($arr));
  }

  return $new_arr;
}

//Функция создания раздела
function create_section($name, $parent = false) {
   $section = new CIBlockSection;
     $arFields = Array(
        "ACTIVE" => "Y", 
        "IBLOCK_ID" => CATALOG_IBLOCK,
        "NAME" => $name,
    );

    if ($parent) {
      $arFields["IBLOCK_SECTION_ID"] = $parent;
    }

    if ($id_section = $section->Add($arFields)) {
      return $id_section;
    } else {
        return $section->LAST_ERROR;
    }
}

//Функция создания элемента
function create_element($name, $id_section, $prop, $q, $price, $xml_id, $descr) {
  $el = new CIBlockElement;
  $elArray = Array(
    "IBLOCK_ID"           => CATALOG_IBLOCK,
    "IBLOCK_SECTION_ID" => $id_section,       
    "PROPERTY_VALUES"   => $prop,
    "NAME"              => $name,
    'DETAIL_TEXT'   => $descr,
    "XML_ID"      => $xml_id,
    "ACTIVE"            => "Y",
  );

  if($ID = $el->Add($elArray)) {
    //Создаем товар
    $productID = CCatalogProduct::add(array("ID" => $ID, "QUANTITY" => $q));

    //Добавляем цену
    $arFields = Array(
      "CURRENCY"         => "RUB",       // валюта
      "PRICE"            => $price, //$data[16],    // значение цены
      "CATALOG_GROUP_ID" => 1,           // ID типа цены
      "PRODUCT_ID"       => $ID,  // ID товара
    );

    CPrice::Add($arFields);

    //Количество по складам
    /*$arFields = Array(
      "PRODUCT_ID" => $productID,
      "STORE_ID"   => $storeID,
      "AMOUNT"     => $rest,
    )
    CCatalogStoreProduct::Add($arFields);/**/
    return $ID;
  } else {
    return false;
  }

}

//Функция проверки существования
function isset_element($param, $val) {
  $arFilter = Array("IBLOCK_ID"=>CATALOG_IBLOCK, $param => $val);
  $res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>1), array('ID'));
  if ($ob = $res->GetNextElement())
  {
    $arFields = $ob->GetFields();
    return $arFields['ID'];
  } else {
    return false;
  }

}

function deactivate() {
  $ar_map = array();
  $arFilter = Array("IBLOCK_ID"=>CATALOG_IBLOCK);
  $res = CIBlockElement::GetList(Array(), $arFilter, false, array(), array('ID'));
  while ($ob = $res->GetNextElement()) {
    $arFields = $ob->GetFields();
    //Деактивируем элементы
    $el = new CIBlockElement;
    $ElementArray = Array("ACTIVE" => "N",);
    $arFields = $ob->GetFields();
    $el->Update($arFields['ID'], $ElementArray);
  }
}

//Получение всех элементов ARTICLE => ID
function get_map_element() {
  $ar_map = array();
  $arFilter = Array("IBLOCK_ID"=>CATALOG_IBLOCK);
  $res = CIBlockElement::GetList(Array(), $arFilter, false, array(), array('ID', 'PROPERTY_ARTICLE'));
  while ($ob = $res->GetNextElement()) {
    $arFields = $ob->GetFields();
    $ar_map[$arFields['PROPERTY_ARTICLE_VALUE']] = $arFields['ID'];
  }
  return $ar_map;
}

//Получение всех элементов ARTICLE => ID
function get_map_brand() {
  $ar_map = array();
  $arFilter = Array("IBLOCK_ID"=>BRAND_IBLOCK);
  $res = CIBlockElement::GetList(Array(), $arFilter, false, array(), array('ID', 'NAME'));
  while ($ob = $res->GetNextElement()) {
    $arFields = $ob->GetFields();
    $ar_map[mb_strtoupper($arFields['NAME'])] = $arFields['ID'];
  }
  return $ar_map;
}

//Функция обновления элемента
function update_element($id, $prop, $id_section, $data) {
  $el = new CIBlockElement;

  $arLoad = Array(
      "IBLOCK_ID"           => CATALOG_IBLOCK,
      "IBLOCK_SECTION_ID" => $id_section,       
      "PROPERTY_VALUES"=> $prop,
      "NAME"           => $data[1],
      "ACTIVE"         => "Y",
  );

  if ($res = $el->Update($id, $arLoad)) {
    return true;
  } else {
    return $el->LAST_ERROR;
  }

}

function get_step() {
  if (!file_exists(PATH_STEP)) {
    file_put_contents(PATH_STEP,'0');
  }

  return file_get_contents(PATH_STEP);
}

function save_step($step) {
  file_put_contents(PATH_STEP,$step);
}

function getRows($file) {
    $handle = fopen($file, 'rb');
    if (!$handle) {
        throw new Exception();
    }
   
    // пока не достигнем конца файла
    while (!feof($handle)) {
        // читаем строку
        // и генерируем значение
        yield fgets($handle);
    }
   
    // закрываем
    fclose($handle);
}

?>

<?  /*-------Настройки-------*/

  //Сопоставление неизменяемых полей

  $massProp = array(
    '2' => 'ARTICLE', //IE_CODE - Артикул 1
    '3' => 'CODE_2', //IP_PROP7 - Артикул 2
    '4' => 'COUNTRY', //IP_PROP16 - Страна
    '18' => 'BRAND',
    '20' => 'BRAND_2',
    '22' => 'NAME_IMG_FILE'
  );

  //Массив для удаления пробелов
  //$mass_trim = array(4);

  //Сопоставление под статус
  /*$status = array {
    '1' => '5', //Свободна
    '2' => '6', //Продана
    '3' => '7'  //Зарезервирована
  }*/

?>

<?
  //Получение списка секций
  $mass_section = array();
  $res = CIBlockSection::GetList(
       Array('LEFT_MARGIN' => 'ASC'), 
       Array("IBLOCK_ID"=>CATALOG_IBLOCK, "ACTIVE"=>"Y"), 
       true,
       Array('ID','NAME','IBLOCK_SECTION_ID','DEPTH_LEVEL')
  );
  while($arSection = $res->GetNext()) {
    $mass_section[$arSection['NAME']] = $arSection;
  };

  //print_r($mass_section);
?>

<?
  //Получение списка свойств
  $mass_props = array();
  $properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$iblock_id));
  while ($prop_fields = $properties->GetNext())
  {
    $mass_props[$prop_fields['NAME']] = array (
      'CODE' => $prop_fields['CODE'],
      'ID' => $prop_fields['ID'],
    );
  }
?>

<?

  $ok = false;

  //--Получение файла
  setlocale(LC_ALL, 'ru_RU');
  setlocale(LC_TIME, 'ru_RU.UTF-8');
  setlocale(LC_NUMERIC, "en_US.utf8");
  $row = 0;
  $start = get_step();
  $end = $start + STEP;
  $count_job = 0;

  echo '<p>Старт - '.$start.'; Конец - '.$end.'</p>';

  if (file_exists(PATH_CSV)) {

  global $ar_map_brand;
  $ar_map = get_map_element();
  $ar_map_brand = get_map_brand();
  if ($start == 0) deactivate();

  foreach (getRows(PATH_CSV) as $data) {
    $data = to_utf8(explode(';',$data));
    
    if ($row == 0) {
      $first_data = $data;
    } elseif($row >= $start) {
      $err = false;

      $PROP_ADD = array();
      //Подготовка свойств
      /*for ($i=0; $i<$num; $i++) {
        if (isset($mass_props[$first_data[$i]])) {
          $PROP_ADD[$mass_props[$first_data[$i]]['CODE']] = $data[$i];
        }
      }*/

      foreach($massProp as $key => $prop) {
        $PROP_ADD[$prop] = $data[$key];
      }

      $id_section = '';

      //---Проверка на существование раздела
      if (!$mass_section[$data[6]]) { //Если нет головного раздела создаем его
        $id_section = create_section($data[6]);
        //Добавляем раздел в список
        $mass_section[$data[6]] = array('ID' => $id_section);
      } else {
        $id_section = $mass_section[$data[6]]['ID'];
      }

      if (!$mass_section[$data[8]] && !empty($data[8])) { //Если нет подраздела создаем его
        $id_section = create_section($data[8], $id_section);
        //Добавляем раздел в список
        $mass_section[$data[8]] = array('ID' => $id_section);
      } elseif (!empty($data[8])) {
        $id_section = $mass_section[$data[8]]['ID'];
      }

      if (!$mass_section[$data[10]] && !empty($data[10])) { //Если нет подраздела создаем его
        $id_section = create_section($data[10], $id_section);
        //Добавляем раздел в список
        $mass_section[$data[10]] = array('ID' => $id_section);
      } elseif (!empty($data[10])) {
        $id_section = $mass_section[$data[10]]['ID'];
      }

      /*Обработка брендов*/
      $prepare_name = trim(mb_strtoupper($data[20]));
      if (isset($ar_map_brand[$prepare_name])) {
        //echo '<p>Найден бренд - '.$prepare_name.'</p>';
        $id_brand =  $ar_map_brand[$prepare_name];
      } else {
        $el = new CIBlockElement;
        $elArray = Array(
          "IBLOCK_ID"         => BRAND_IBLOCK,
          "NAME"              => trim($data[20]),
          "ACTIVE"            => "Y",
        );
        $id_brand = $el->Add($elArray);
        $ar_map_brand[$prepare_name] = $id_brand;
        /*echo '<p>Новый бренд - '.$prepare_name.'</p>';
        echo '<pre>';
        print_r($ar_map_brand);
        echo '</pre>';*/
      }
      $PROP_ADD['BRAND'] = $id_brand;
      
      /*---*/

      if (!$err && !empty($id_section)) {

        //Проверка на существование элемента
        //if ($update_id = isset_element('PROPERTY_ARTICLE', $data[2])) {
        if (isset($ar_map[$data[2]])) {
          update_element($ar_map[$data[2]], $PROP_ADD, $id_section, $data);
          if(LOGGED) $log .= 'Обновление элемента - '.$data[1].'('.$update_id.') -- '.$id_section.'<br />';
          $info['update']++;
        } else {
          $result = create_element($data[1], $id_section, $PROP_ADD, $data[12], $data[16], $data[0], $data[23]);
          if ($result) {
            if(LOGGED) $log .= 'Добавлен элемент - '.$data[1].'('.$result.')'.' -- '.$id_section."<br />";
            $info['new']++;
          } else {
            if(LOGGED) $log .= 'Ошибка добавления элемента - '.$data[1].'<br />';
            $info['err']++;
          }
        }
      }//ERR
      else {
        //Вывод ошибки
        foreach ($err as $error) {
          ?>
            <p style="color:red;"><?=$error?></p>
          <?
        }
      }/**/

      $count_job++;
    }

    if ($row > $end) break;
    $row++;
   
  }

    save_step($row);
    echo '<p>Обработано '.$count_job.' строк</p>';
    echo '<p>Всего '.$row.' строк</p>';

    //Удаляем файл
    Debug::log(PATH_LOG,$info);
    if ($count_job < STEP) {
      //unlink(PATH_CSV);
      echo '<p>Обработка закончена</p>';  
      echo '<p>Всего обработано '.$row.' строк</p>';
      save_step(0);
    }
        if (LOGGED) {
          echo $log;
        }
    //print_r($info);
  } else {
    echo '<p>Файл отсутствует</p>';
  }
?>
