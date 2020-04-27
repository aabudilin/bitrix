<?
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Main\Mail\Event;
define('TYPE_SEND', 'NOTIFICATIONS');
define('ID_SEND', 84);

$message = '<p>Имя - '.$_REQUEST['name'].'</p>';
$message .= '<p>Телефон - '.$_REQUEST['tel'].'</p>';
$arEventFields['EMAIL_TO'] = DEFAULT_NOTIFICATION_EMAIL;
$arEventFields['TITLE'] = 'Заявка на обратный звонок';
$arEventFields['MESSAGE'] = $message;

		$result_send = Event::send(array(
		    "EVENT_NAME" => TYPE_SEND,
		    "LID" => "s1",
		    "C_FIELDS" => $arEventFields,
		    'MESSAGE_ID' => ID_SEND,
		)); 


// сюда нужно вписать токен вашего бота
define('TELEGRAM_TOKEN', '1223131685:AAEciaLofreY09WVzVVXYFuWF4OIFOe8LWU');

// сюда нужно вписать ваш внутренний айдишник
define('TELEGRAM_CHATID', '-423653255');

$telegram_message = 'На сайте оставлена заявка на обратный звонок. Имя - '.$_REQUEST['name'].', телефон - '.$_REQUEST['tel'];

function message_to_telegram($text)
{
    $ch = curl_init();
    curl_setopt_array(
        $ch,
        array(
            CURLOPT_URL => 'https://api.telegram.org/bot' . TELEGRAM_TOKEN . '/sendMessage',
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => array(
                'chat_id' => TELEGRAM_CHATID,
                'text' => $text,
            ),
        )
    );
    $result = curl_exec($ch);
	//print_r($result);

    curl_close($ch);
}

message_to_telegram($telegram_message);



$result = array(
	'status' => 'success',
	'message' => 'Сообщение отрпавлено',
);

echo json_encode($result);
?>