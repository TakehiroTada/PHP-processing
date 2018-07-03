<?php
include_once 'drillbase.php';
include_once 'drill1line.php';
include_once 'iProgEval.php';

if(!isset($seed)) {
    $seed = make_seed();
}
srand($seed);
$xseed=sprintf("%x",$seed);
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

?>
問題１．以下の指定された図形を描くメソッド呼出文(プログラム)を示しなさい。なお、正方形や長方形の描画はrect()メソッドを使うこと。
<pre>
<form method=post action="drill1no1ans.php">
<?php print "<input type='hidden' name='seed' value='$xseed'>\n"; 
for($i=0;$i<count($x);$i++) {
    printf("(%d) ",$i+1);
    print $x[$i]->qStr;
    printf("<br>\n<input type='text' name='a%d' size='80'><br>\n",$i+1);
}

?>
<input type="submit" value="採点">
</form>
</pre>

