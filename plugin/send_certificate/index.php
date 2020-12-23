<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
require($_SERVER["DOCUMENT_ROOT"]."/local/src/GDText/Box.php");
require($_SERVER["DOCUMENT_ROOT"]."/local/src/GDText/TextWrapping.php");
require($_SERVER["DOCUMENT_ROOT"]."/local/src/GDText/Color.php");
require($_SERVER["DOCUMENT_ROOT"]."/local/src/GDText/HorizontalAlignment.php");
require($_SERVER["DOCUMENT_ROOT"]."/local/src/GDText/VerticalAlignment.php");
require($_SERVER["DOCUMENT_ROOT"]."/local/src/GDText/Struct/Point.php");
require($_SERVER["DOCUMENT_ROOT"]."/local/src/GDText/Struct/Rectangle.php");
require($_SERVER["DOCUMENT_ROOT"]."/local/src/Standart/Certificate.php");
require($_SERVER["DOCUMENT_ROOT"]."/local/src/Standart/Sender.php");

$APPLICATION->SetTitle("Отправка сертификата");
use Bitrix\Main\Page\Asset;
use Standart\Sender;
Asset::getInstance()->addCss("/local/services/sending_certificate/custom.css");

if(isset($_FILES['csv'])) {
	$arParams['TITLE'] = $_REQUEST['title'];
	$arParams['TYPE_SEND'] = 'CERT';
	$arParams['ID_SEND'] = '92';
	$sender = new Sender($_FILES['csv']['tmp_name'],$arParams);
	$sender->process();
	$sender->viewLog();
}

?><h1>Отправка сертификатов</h1>
<p>
	 Загрузите список контактов в формате csv&nbsp; в следующем виде:
</p>
 <br style="clear:both;">
<table class="table-csv">
<tbody>
<tr>
	<th>
		 Ф.И.О.
	</th>
	<th>
		 Эл. почта
	</th>
</tr>
</tbody>
</table>
 <br style="clear:both;">
<form action="<?=$_SERVER['REQUEST_URI']?>" method="post" enctype="multipart/form-data" class="form-sender">
	<p>
 <input type="file" name="csv" required="">
	</p>
	<p>
 <textarea name="title" placeholder="Описание мероприятия"></textarea>
	</p>
	<p>
 <button type="submit">Отправить рассылку</button>
	</p>
</form><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>