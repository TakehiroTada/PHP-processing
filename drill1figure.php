<?php
include_once 'drillbase.php';

include_once "iProgToken.php";
include_once 'iProgParse.php';
include_once 'iProgEval.php';

class FigureDrill extends DrillData {
    static $width=200;
    static $height=200;

    static $imageDir="image/";

    public $image;
    public $methodList;

//    public $vList;
//    public $qTrees=null;
//    public $sTrees=null;
//    public $aStr;
//    public $aStrs;
//    private $eqWork=null; // 一致を見る時の作業領域

    function __construct($img=null,$methods=null) {
	$this->image = $img;
	$this->methodList = $methods;
    }

    function getImageUrl() {
	return self::$imageDir . $this->image;
    } 

    function getMethods($flag=null) {
	if($flag) return $this->methodList;
	$ans = "";
	foreach($this->methodList as $s)
	    $ans .= "$s\n";
	return $ans;
    }

    function answer($arg) {
    }
}


//data
class PermutationFactory {

    public $number;
    public $order  = 0;
    public $member =  null;
    function __construct($arr) {
	if(is_array($arr)) {
	//    print "is_array\n";
	    $this->member = $arr;
	    $this->number = count($arr);
	} else if(is_int($arr)) {
	//    print "is_int\n";
	    $this->number = $arr;
	    $this->member = range(1,$arr);
	} else return;
    }

    function remove_array($a,$i) {
	$ans = array();
	for($n=0;$n<$i;$n++)
	    array_push($ans,array_shift($a));
	array_shift($a);
	return array_merge($ans,$a);
    }

    function getAll($arg=null) {
	if(is_null($arg)) return $this->getAll($this->member);
	if(count($arg)==1) return $arg;
	if(count($arg)==2) return array(array($arg[0],$arg[1]),array($arg[1],$arg[0]));
	$ans = array();
	for($i=0;$i<count($arg);$i++) {
	    $top = $arg[$i];
	    $tail = $this->getAll(self::remove_array($arg,$i));
	    for($j=0;$j<count($tail);$j++) {
		$b = $tail[$j];
		array_unshift($b,$top);
		array_push($ans,$b);
	    }
	}
	return $ans;
    }

}



function makeFigureQuestion($key, $size, $methodList) {
    $a = new PermutationFactory($methodList);
    $al = $a->getAll();
    $n = new PermutationFactory(count($methodList));
    $nl = $n->getAll();
    $am = array_map(null,$nl,$al);
    $fl = array();
    foreach($am as $item) {
	$figure = "$key";
	foreach($item[0] as $i) {
	    $figure .= $i;
	}
	$figure .= ".png";
	$st = array($size);
	foreach($item[1] as $i) {
	    $st[] = $i;
	}
	$fl[] = new FigureDrill($figure,$st);
    }
    return $fl;
}


?>
