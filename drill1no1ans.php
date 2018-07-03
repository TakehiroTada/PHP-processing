<?php
include_once 'drillbase.php';
include_once 'drill1line.php';
include_once 'iProgEval.php';

if( ! isset($_POST['seed']) ) gotoScript('drill1No1.php');

$seed = hexdec($_POST['seed']);
$a = getAnswerFromForm();

srand($seed);
$x = array();
$x[] = randomArray(array(mkMethodHLine(),mkMethodVLine(),mkMethodLine()))[0];
$x[] = randomArray(array(mkMethodTriangle(),mkMethodTriangle2()))[0];
$x[] = randomArray(array(mkMethodRect(),mkMethodSquare()))[0];
$x[] = randomArray(array(mkMethodQuad(),mkMethodDiamond()))[0];
$x[] = randomArray(array(mkMethodCircle(),mkMethodCircleR(),mkMethodEllipse()))[0];

$x[] = randomArray(array(mkMethodHLine(true),mkMethodVLine(true),mkMethodLine(true)))[0];
$x[] = randomArray(array(mkMethodTriangle(true),mkMethodTriangle2(true)))[0];
$x[] = randomArray(array(mkMethodRect(true),mkMethodSquare(true)))[0];
$x[] = randomArray(array(mkMethodQuad(true),mkMethodDiamond(true)))[0];
$x[] = randomArray(array(mkMethodCircle(true),mkMethodCircleR(true),mkMethodEllipse(true)))[0];

$ans = array();
for($i=0; $i <count($x); $i++) {
    $ans[] = $x[$i]->answer($a[$i]);
}

?>
問題１．以下の指定された図形を描くメソッド呼出文(プログラム)を示しなさい。なお、正方形や長方形の描画はrect()メソッドを使うこと。
<pre>
<?php
for($i=0;$i<count($x);$i++) {
    printf("(%d) ",$i+1);
    print $x[$i]->qStr;
    print "\n回答: [".$a['fix'][$i]."]";
    if($ans[$i])  print "○<br>\n";
    else {
	print "× --- 正解は[".$x[$i]->aStr."]<br>\n";
    }
}

?>
<a href="drill1no1.php">もう一度</a>

