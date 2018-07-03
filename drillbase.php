<?php
include_once 'iProgParse.php';

$RootMenu = "index.php";


class DrillData {
    public $qStr = null;
    public function answer($arg) { return false; }
//    void __construct($str=null) { $qStr=$str;}
}

function tryParseStatement($text) {
    $mess = "";
    $tl = new TokenList($text);
    if(!$tl->tokenList) {
	$mess = "字句解析に失敗しました。<br>";
	goto next;
    }

    $message = $tl->getTokenErrors();
    if(strlen($message)>0) {
	$mess .= "$message<hr>\n";
	goto next;
    }
    $tl->removeComment();
    $result = parseStatement($tl->tokenList);

    $parse = $result[0];

    if(!$parse) {
	$mess .= "構文エラーがありました。<br>";
	$mess .= "<pre>\n" . print_r($tl,true) . "</pre>\n";
	goto next;
    }

    if($parse->isError()) {
	$mess .= sprintf("構文エラーがありました。<br>%s<br>", $parse->message);
	goto next;
    }

    return array($parse,null);
    next:
    return array(false,$mess);
}

function tryParseStatementList($text) {
    $mess = "";
    $tl = new TokenList($text);
    if(!$tl->tokenList) {
	$mess = "字句解析に失敗しました。<br>";
	goto next;
    }

    $message = $tl->getTokenErrors();
    if(strlen($message)>0) {
	$mess .= "$message<hr>\n";
	goto next;
    }
    $tl->removeComment();

    $parseList= array();
    while($tl->tokenList && $result = parseStatement($tl->tokenList)) {


	$parse = $result[0];

	if(!$parse) {
	    $mess .= "構文エラーがありました。<br>";
	    $mess .= "<pre>\n" . print_r($tl,true) . "</pre>\n";
	    goto next;
    	}

    	if($parse->isError()) {
	    $mess .= sprintf("構文エラーがありました。<br>%s<br>", $parse->message);
	    goto next;
    	}
	
	array_push($parseList,$parse);
	$tl->tokenList = $result[1];
    }
    return array($parseList,null);
    next:
    return array(false,$mess);
}

function randomArray($in,$n=1) {
	if(! is_array($in)) return null;

	$ans = array();

	while($n > 0 && $in) {
		$ans = array_merge($ans,array_splice($in,rand(0,count($in)-1),1));
		$n--;
	}
	return $ans;
}

function make_seed()
{
  list($usec, $sec) = explode(' ', microtime());
  return $sec + $usec * 1000000;
}

function alphabet() {
	$a = 'a';

	$ans = array($a);

	do {
		$a++;
		$ans[] = $a;
	} while($a < 'z');
	return $ans;
}

// スクリプトに飛ぶ
function gotoScript($script = false) {
    global $RootMenu;
    if(! $script) $script = $RootMenu;

    print "<html>\n" .
        "<body onLoad=\"location.href='$script'\">\n" .
       "</body>\n</html>\n";
    exit;
}

// 個別問題用
function cmpEmu($v1,$op,$v2) {
	switch($op) {
	case '<':
		return ($v1 < $v2);
	case '<=':
		return ($v1 <= $v2);
	case '==':
		return ($v1 == $v2);
	case '!=':
		return ($v1 != $v2);
	case '>':
		return ($v1 > $v2);
	case '>=':
		return ($v1 >= $v2);
	default:
		return false;
	}
}

// 等差数列を作る range() でできる
/*function getSequence($start,$end,$step = 1) {
    $seq = array();
    if(is_numeric($start)) {
	for($i=$start; $i <= $end; $i += $step)
	    array_push($seq,$i);
    } else if(preg_match('/^([a-zA-Z_]+)([\d]+)$/',$start,$matches,PREG_OFFSET_CAPTURE)) {
	
    }
    return $seq;
}
*/
// form から連続したデータを得る
function getAnswerFromForm($letter='a',$i=1) {
    $ans = array('fix'=>array());
    while(isset($_POST["$letter$i"])) {
	$ans[] = $_POST["$letter$i"];
	$ans['fix'][] = htmlspecialchars($_POST["$letter$i"]);
	$ans['length'] = $i;
	$i++;
    }
    return $ans;
}

// for文の回数チェック
function getansfor($start,$end,$step,$op) {
	$count = 0;
	for($v = $start; cmpEmu($v,$op,$end) ; $v += $step) {
		$count++;
	}
	return $count;
}

// for文の回数チェック（2倍バージョン)
function getansforby2($start,$end,$step=2) {
	$count = 0;
	for($v = $start; $v<$end; $v *= $step) {
		$count++;
	}
	return $count;
}

1;
?>