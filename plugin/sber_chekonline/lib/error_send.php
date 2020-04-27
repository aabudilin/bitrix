<?
	use Bitrix\Main\Mail\Event;
	define('TYPE_SEND', 'ERROR_NOTIFICATION');
	define('ID_SEND', 103);

	function error_send ($email, $title, $message) {
		$arEventFields['EMAIL_TO'] = $email;
		$arEventFields['TITLE'] = $title;
		$arEventFields['MESSAGE'] = $message;

		$result_send = Event::send(array(
		    "EVENT_NAME" => TYPE_SEND,
		    "LID" => "s1",
		    "C_FIELDS" => $arEventFields,
		    'MESSAGE_ID' => ID_SEND,
		)); 
	}