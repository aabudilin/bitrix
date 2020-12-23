<?
use Bitrix\Main\Mail\Event;
use Standart\Certificate;

namespace Standart;

class Sender {

	protected $pathCsv;
	protected $arParams; //[TYPE_SEND,ID_SEND_TITLE]
	protected $certHelper;
	protected $log;


	public function __construct ($path,$arParams) {
		 if ($path) {
		 	$this->pathCsv = $path;
		 } else {
        	throw new \Exception('Не указан путь файлу');
    	}

    	$this->arParams = $arParams;

    	$this->certHelper = new Certificate;
		$this->certHelper->pathCertBase = $_SERVER['DOCUMENT_ROOT'].'/upload/cert/data/cert_new.jpg';
		$this->certHelper->pathFont = $_SERVER['DOCUMENT_ROOT'].'/upload/cert/data/9041.ttf';
		$this->certHelper->pathCertOutput = $_SERVER['DOCUMENT_ROOT'].'/upload/cert/';
		$this->certHelper->width = 1900;
	}

	public function process() {
		foreach ($this->read($this->pathCsv) as $data) {
			if(!empty(trim($data))) {
				$data = explode(';',$data);
				$name = $data[0];
				$arText = array();
				$arText[] = array (
					'TEXT' => $this->to_utf8($data[0]),
					'TOP' => 530,
					'WIDTH' => 1530,
					'FONT_SIZE' => 50,
					'COLOR' => 'brown',
				);
				$arText[] = array (
					'TEXT' => $this->to_utf8($this->arParams['TITLE']),
					'TOP' => 690,
					'WIDTH' => 1530,
					'FONT_SIZE' => 25,
					'COLOR' => 'black',
				);

				$pathFile = $this->certHelper->generateGD($name,$arText);
				$arEvent = array (
					'EMAIL_TO' => $data[1]
				);
				
				$this->send($arEvent,$pathFile);

				$this->log .= '<p>Отправлен сертификат на почту - '.$data[1].'</p>';

				//Чистим данные
				unset($arText);
				unset($pathFile);
			}
		}
	}

	protected function send($arEvent,$file) {
		return \Bitrix\Main\Mail\Event::send(array(
		    "EVENT_NAME" => $this->arParams['TYPE_SEND'],
		    "LID" => "s1",
		    "C_FIELDS" => $arEvent,
		    'MESSAGE_ID' => $this->arParams['ID_SEND'],
		    "FILE" => array($file), 
		));
	}

	protected function read($path) {
		$handle = fopen($path, 'rb');
	    if (!$handle) {
	        throw new Exception();
	    }
	   
	    // пока не достигнем конца файла
	    while (!feof($handle)) {
	        yield fgets($handle);
	    }
	   
	    fclose($handle);
	}

	protected function to_utf8 ($arr) {
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

	public function viewLog() {
		echo $this->log;
	}

}
?>