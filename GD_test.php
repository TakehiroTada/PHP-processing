<?php


// �R���e���c��PNG�摜�ł��邱�Ƃ��u���E�U�[�ɂ��m�点
header ('Content-Type: image/png');
 
// ��������ɉ摜���\�[�X���m��
$img = imagecreatetruecolor(100,100);

// �w�i�F�w��@��
$background = imagecolorallocate($img, 255, 255, 255);
ImageFilledRectangle($img, 0,0, 100,100, $background);

 
// �����̐F���w��i�����ł͍��F�j
$color = imagecolorallocate($img, 0, 0, 0);
 
// �摜���\�[�X�ɒ�����`��
imageline($img, 0, 0, 100, 100, $color);
imageline($img, 0, 100, 100, 0, $color);


 
// �摜���\�[�X����PNG�t�@�C�����o��
imagepng($img);
 
// �摜���\�[�X��j��
imagedestroy($img);

?>