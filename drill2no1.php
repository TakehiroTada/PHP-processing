<?php
  include_once 'drillbase.php';
?>
問題１．以下の各コードは処理を何回するか答えなさい。
<pre>
<?php

$x = randomArray(alphabet(),4);
$v1 = $x[0];
$n1 = rand(5,12);
$ans1 = $n1; // 答えは$n1

print <<<EOD
int $v1 = 1;
while($v1 <= $n1)
{
    処理;
    $v1 = $v1 + 1;
}

正解：$ans1


EOD;

$v2 = $x[1];
$n2 = rand(3,10);
$ans2 = $n2; // 答えは$n2

print <<<EOD
int $v2 = 0;
while($v2 < $n2)
{
    処理;
    $v2 = $v2 + 1;
}

正解：$ans2


EOD;

$v3 = $x[2];
$n3 = rand(5,12);
$n4 = rand(0,1);
$ops= randomArray(array('>=','>','!='),1);
$op1 = $ops[0];
$ans3 = getansfor($n3,$n4,-1,$op1);

print <<<EOD
int $v3 = $n3;
while($v3 $op1 $n4)
{
    処理;
    $v3 = $v3 - 1;
}

正解：$ans3


EOD;

// 増分が大きい場合
if(rand(0,2) == 1) {
	$v4 = $x[3];
	$n5 = 1;
	$n6 = rand(20,100);
	$inc = "$v4 = $v4 * 2";
	$ans4= getansforby2($n5,$n6);
} else {
	$v4 = $x[3];
	$n5 = 1;
	$n6 = rand(20,100);
	$n7 = rand(2,10);
	$inc = "$v4 = $v4 + $n7";
	$ans4 = getansfor($n5,$n6,$n7,'<');
}

print <<<EOD
int $v4 = $n5;
while($v4 < $n6)
{
    処理;
    $inc;
}

正解：$ans4


EOD;

?>
</pre>
