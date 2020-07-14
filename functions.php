//Получения ключа гугл карт или яндекс для данного домена
//$key = get_map_key('Y') - получить ключ Яндекс, без параметра Google
function get_map_key($map)
{
   if($map=="Y") $map='map_yandex_keys';
   else $map='map_google_keys';
   
   $MAP_KEY = '';
   $strMapKeys = COption::GetOptionString('fileman', $map);

   $strDomain = $_SERVER['HTTP_HOST'];
   $wwwPos = strpos($strDomian, 'www.');
   if ($wwwPos === 0)
      $strDomain = substr($strDomain, 4);

   if ($strMapKeys)
   {
      $arMapKeys = unserialize($strMapKeys);
      
      if (array_key_exists($strDomain, $arMapKeys))
         $MAP_KEY = $arMapKeys[$strDomain];
   }
   
   if (!$MAP_KEY)
   {
      return false;
   }
   else return $MAP_KEY;
}
