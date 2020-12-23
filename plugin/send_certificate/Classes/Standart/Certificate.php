<?
namespace Standart;


use GDText\Box;
use GDText\Color;

class Certificate
{

    public $pathCertBase;
    public $pathCertOutput;
    public $pathFont;
    public $width;

    /**
     * ��������� �����������
     * @param  string $name - �������� �����
     * @param  array $arText - ���������� �����������
     * @return string - ���� � �����������
     */
    public function generate($name,$arText) {
        //������� ����������� �� ��������
        $im = @imagecreatefromjpeg($this->pathCertBase);
        $color = array (
            'black' => imagecolorallocate($im, 0, 0, 0),
            'brown' => imagecolorallocate($im, 110, 83, 38),
        );

        foreach($arText as $item) {

            //��������� �������� ���� ��� ����� 
            $certText = $this->fitText($item['TEXT'], $item['FONT_SIZE'], $item['WIDTH']);

            $box = imagettfbbox($item['FONT_SIZE'], 0, $this->pathFont, $certText);
            $left = $this->width/2 - round(($box[2] - $box[0])/2);
            imagettftext($im, $item['FONT_SIZE'], 0, $left, $item['TOP'], $color[$item['COLOR']], $this->pathFont, $certText);        
        }

        if (!imagejpeg($im,$this->getPath($name),70)) {
            echo '������ ������������ �����������';
        }
        imagedestroy($im);
        return $this->getPath($name);
    }

    public function generateGD($name,$arText) {
        $im = @imagecreatefromjpeg($this->pathCertBase);
        $color = array (
            'black' => imagecolorallocate($im, 0, 0, 0),
            'brown' => imagecolorallocate($im, 110, 83, 38),
        );

        foreach($arText as $item) {
            $box = new Box($im);
            $box->setFontFace($this->pathFont);
            $box->setFontColor(new Color(0, 0, 0));
            $box->setFontSize($item['FONT_SIZE']);
            $box->setLineHeight(1.5);
            $box->setBox(170, $item['TOP'], $item['WIDTH'], $item['HEIGHT']);
            $box->setTextAlign('center', 'top');
            $box->draw($item['TEXT']);
        }

        if (!imagejpeg($im,$this->getPath($name),70)) {
            echo '������ ������������ �����������';
        }
        imagedestroy($im);
        return $this->getPath($name);
    }

    /**
     * ���������� ��������� ������
     * @param  string $text
     * @param  int    $fontSize 
     * @param  int    $width
     * @return string
     */
    protected function fitText($text, $fontSize, $width) {
        $text_a = explode(' ', $text);
        $text_new = '';
        foreach($text_a as $word){
            $box = imagettfbbox($fontSize, 0, $this->pathFont, $text_new.' '.$word);
            //���� ������ ������� � �������� ������, �� ��������� ����� � ��������, ���� ��� �� ��������� �� ����� ������
            if($box[2] > $width - $margin*2){
                $text_new .= "\n".$word;
            } else {
                $text_new .= " ".$word;
            }
        }
        return trim($text_new);
    }

    /**
     * ��������� ���� � �����������
     * @param  string $text - �������� �����
     * @return string - ���� � �����������
     */
    protected function getPath($text) {
        return $this->pathCertOutput.md5($text).'.jpg';
    }

}

?>