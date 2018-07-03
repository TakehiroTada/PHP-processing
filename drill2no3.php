<?php
  include_once 'drillbase.php';
?>
問題3．次の計算を行うProcessingの式を記述しなさい。
<pre>
<?php

$oset1 = array(array('×','*'), array('÷','/'));
$oset2 = array(array('+','+'),array('-','-'));
$oset3 = array(array('≦','<='),array('≧','>='));
$oset4 = array(array('＝','=='),array('≠','!='));

$oset5 = array(array('と等しい','=='),array('と等しくない','!='),
				array('以下','<='),array('以上','>='),
				array('より小さい','<'),array('より大きい','>'));

$vset1 = array(array('a','b'), array('i','j'),array('x','y'),array('i','k'),
			array('c','d'),array('f','g'),array('m','n'),array('p','q'),
			array('r','s'),array('v','w'),array('a1','a2'),array('i1','i2'),
			array('x1','x2'),array('v1','v2'),array('b1','b2'),array('z0','z1'));


/*,
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
*/				
$x = randomArray($vset1,2);
$v1 = $x[0][0];
$v2 = $x[0][1];
$v3 = $x[1][0];
$v4 = $x[1][1];

$o = randomArray(array_merge($oset1,$oset1),3);
$p = randomArray(array_merge($oset2,$oset2),3);

$o1 = $o[0][0];
$o2 = $o[1][0];
$o3 = $o[2][0];
$oa1= $o[0][1];
$oa2= $o[1][1];
$oa3= $o[2][1];

$p1 = $p[0][0];
$p2 = $p[1][0];
$p3 = $p[2][0];

$n1 = rand(1,10);
$n2 = rand(1,10);
$n3 = rand(1,10);
$n4 = rand(1,10);

$va2= randomArray(array($v3,$v4,$n3,$n4),4);// $va2に4個のオペランド

$x  = randomArray(alphabet(),4);
$v5 = $x[0];
$v6 = $x[1];
$v7 = $x[2];
$v8 = $x[3];
$n5 = rand(2,9);
$n6 = rand(0,9);
$n7 = rand(0,9);
$n8 = rand(4,9);
$n9 = rand(1,$n8-1);

$o = randomArray($oset3,1);
$p = randomArray($oset4,1);
$r = randomArray($oset5,1);

$o6 = $o[0][0]; $oa6 = $o[0][1];
$o7 = $p[0][0]; $oa7 = $p[0][1];
$o8 = $r[0][0]; $oa8 = $r[0][1];

print <<<EOD
(1) $v1 $o1 $n1 $p1 $v2 $o2 $n2
(2) $va2[0] $p2 $va2[1] $o3 $va2[2] $p3 $va2[3]
(3) $v5 を $n5 で割った余り
(4) $v6 $o6 $n6
(5) $v7 $o7 $n7
(6) $v8 を$n8 で割った余りが$n9$o8


正解
(1): $v1 $oa1 $n1 $p1 $v2 $oa2 $n2
(2): $va2[0] $p2 $va2[1] $oa3 $va2[2] $p3 $va2[3]
(3): $v5 % $n5
(4): $v6 $oa6 $n6
(5): $v7 $oa7 $n7
(6): $v8 % $n8 $oa8 $n9
EOD;


?>
</pre>
