<?
//Создание пользователя с правми админа
//Если вдруг забыл пароль на сайте

require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");
$user = new CUser;
$arFields = Array(
  "NAME"              => "Администратор 2",
  "EMAIL"             => "mail@studio-premium.ru",
  "LOGIN"             => "admin2admin",
  "LID"               => "ru",
  "ACTIVE"            => "Y",
  "GROUP_ID"          => array(1,2,3,4,5,6,7,8),
  "PASSWORD"          => "Admin2admin#",
  "CONFIRM_PASSWORD"  => "Admin2admin#",
);

$ID = $user->Add($arFields);
if (intval($ID) > 0) {
    echo '<p>Пользователь успешно добавлен - можно переходить к <a href="/bitrix/">авторизации</a></p>';
    echo '<p>Логин - admin2admin</p>';
    echo '<p>Пароль - Admin2admin#</p>';
} else {
    echo $user->LAST_ERROR;
}
?>