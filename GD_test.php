<?php


// コンテンツがPNG画像であることをブラウザーにお知らせ
header ('Content-Type: image/png');
 
// メモリ上に画像リソースを確保
$img = imagecreatetruecolor(100,100);

// 背景色指定　白
$background = imagecolorallocate($img, 255, 255, 255);
ImageFilledRectangle($img, 0,0, 100,100, $background);

 
// 直線の色を指定（ここでは黒色）
$color = imagecolorallocate($img, 0, 0, 0);
 
// 画像リソースに直線を描画
imageline($img, 0, 0, 100, 100, $color);
imageline($img, 0, 100, 100, 0, $color);


 
// 画像リソースからPNGファイルを出力
imagepng($img);
 
// 画像リソースを破棄
imagedestroy($img);

?>