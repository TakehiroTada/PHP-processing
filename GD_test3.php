 <?php


// エラー出力する場合
ini_set( 'display_errors', 1 );

include_once "iProgToken.php";
include_once 'iProgParse.php';
include_once 'iProgEval.php';
include_once 'drillbase.php';
include_once 'drill1line.php';

$x = array();
$x[] = mkMethodLine();
var_dump($x);