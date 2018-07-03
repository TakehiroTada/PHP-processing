<?php
  include_once 'drillbase.php';
?>
問題2．以下のそれぞれの変数を定義するProcessingの文(変数宣言)を示しなさい。
<pre>
<?php

$set1 = array(array('a','b'), array('i','j'),array('x','y'),array('i','k'),
			array('c','d'),array('f','g'),array('m','n'),array('p','q'),
			array('r','s'),array('v','w'),array('a1','a2'),array('i1','i2'),
			array('x1','x2'),array('v1','v2'),array('b1','b2'),array('z0','z1'));

$set2 = array(array('a', sprintf("%d.0",rand(0,2))),
				array('b',sprintf("%3.1f",rand(1,99)/10)),
				array('pi','3.14'),array('exp','2.71'),array('sq2','1.414'),
				array('sq3','1.732'),array('sq5','2.236'),array('c0','273.15'),
				array('atm','1013.25'));
$set3 = array(array('red','#FF0000'),array('black','#000000'),array('white','#FFFFFF'),
				array('green','#00FF00'),array('blue','#0000FF'),array('cyan','#00FFFF'),
				array('magenta','#FF00FF'),array('yellow', '#FFFF00'),array('gray','#808080'),
				array('pink','#FFC0CB'),array('orange','#FFA500'),array('ivory','#FFFFF0'));
$set4 = array(array('sato','佐藤栄作'),array('suzuki','鈴木貫太郎'),array('ito','伊藤博文'),
				array('tanaka','田中角栄'),array('abe','安部晋三'),array('hara','原敬'),
				array('takahasi','高橋是清'),array('yoshida','吉田茂'),
				array('george','George Washington'),array('john','John Adams'),
				array('abraham','Abraham Lincoln'),array('theodore','Theodore Roosevelt'),
				array('franklin','Franklin Delano Roosevelt'),
				array('harry','Harry S. Truman'),
				array('john','John Fitzgerald Kennedy'),
				array('ronald','Ronald Wilson Reagan'),
				array('barack','Barack Hussein Obama II'));
				
$x = randomArray($set1,2);
$v1 = $x[0][0];
$v2 = $x[0][1];
$v3 = $x[1][0];
$n4 = rand(0,10);
$f = randomArray($set2,1);
$v5 = $f[0][0];
$n6 = $f[0][1];
if(rand(1,10) < 5) {
	$type = 'color';
	$typeName = 'color(色)';
	$c = randomArray($set3,1);
	$v7 = $c[0][0];
	$n8 = $c[0][1];
} else {
	$type = 'String';
	$typeName = '文字列';
	$c = randomArray($set4,1);
	$v7 = $c[0][0];
	$n8 = '"' . $c[0][1] . '"';
}


print <<<EOD
(1) 整数型の変数 $v1 と $v2 (初期値なし)
(2) 整数型の変整 $v3 初期値は $n4
(3) 実数型の変数 $v5 初期値は $n6
(4) $typeName 形の変数 $v7 初期値は $n8

正解
(1): int $v1,$v2;
(2): int $v3 = $n4;
(3): float $v5 = $n6;
(4): $type $v7 = $n8;
EOD;


?>
</pre>
