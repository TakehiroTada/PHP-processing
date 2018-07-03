<?php

include_once "iProgToken.php";
include_once 'iProgParse.php';
include_once 'processing.php';

class EResult {
    const RENULL  = 0;
    const INTEGER = 1;
    const FLOAT   = 2;
    const DOUBLE  = 3;
    const STRING  = 10;
    const COLOR   = 11;
    const BOOLEAN = 20;

    const METHOD  = 50;

    const ERROR   = -1;

    public $type;
    public $value;

    function __construct($t = 0,$v = null) {
	$this->type = $t;
	$this->value = $v;
    }

    function isError() { return $this->type < 0; }
    function isNumber() {
	return $this->type == EResult::INTEGER ||
		$this->type == EResult::FLOAT ||
		$this->type == EResult::DOUBLE;
    }
}

$iProgError = null;

class MethodResult extends EResult {
	public $argList;
	public $name;
	public $rType;
	function __construct($t = 0,$a = null,$r = null) {
		parent::__construct(EResult::METHOD);
		if($t instanceof IProgParse && $t->type == IProgParse::METHOD_EXPRESSION) {
		} else if(is_string($t) && (is_array($a) || is_null($a))) {
			$this->name = $t;
			$this->argList = $a;
			$this->rType = $r;
		}
	}
	function equal($x) {
		global $iProgError;
		if(strcmp($this->name,$x->name) != 0) {
			$iProgError = "メソッド名不一致(". $this->name . "<=>" . $x->name . ")";
			return false;
		}
		if(count($this->argList) != count($x->argList)) {
			$iProgError = "引数の個数不一致(". count($this->argList) . "<=>" .
							count( $x->argList ) . ")";
			return false;
		}
		$yList = $this->argList;
		$xList = $x->argList;
		$n = 0;
		while($yList) {
			$ay = array_shift($yList);
			$ax = array_shift($xList);
			$n ++;
			if($ay->type != $ax->type && !($ay->isNumber() && $ax->isNumber())) {
				$iProgError = "第$n 引数のデータ型が合いません";
				return false;
			}
			if($ay->value != $ax->value) {
				$iProgError = "第$n 引数が合いません(". $ay->value . "<=>" .
								$ax->value . ")";
				return false;
			}
		}
		return true;
	}

	function toString() {
		$s = "";
		$s .= $this->name;
		$s .= "(";
		$a = $this->argList;
		while($a) {
			$v = array_shift($a);
			$s .= $v->value;
			if($a) $s .= ".";
		}
		$s .= ")";
		return $s;
	}
}

$methodList = array(
	new MethodResult("size",array(EResult::INTEGER,EResult::INTEGER)),
	new MethodResult("arc",array(EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT)),
	new MethodResult("ellipse",array(EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT)),
	new MethodResult("line",array(EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT)),
	new MethodResult("point",array(EResult::FLOAT,EResult::FLOAT)),
	new MethodResult("quad",array(EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,
									EResult::FLOAT,EResult::FLOAT)),
	new MethodResult("rect",array(EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT)),
	new MethodResult("rect",array(EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT)),
	new MethodResult("rect",array(EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,
									EResult::FLOAT,EResult::FLOAT)),
	new MethodResult("triangle",array(EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,EResult::FLOAT,
									EResult::FLOAT,EResult::FLOAT)),
	new MethodResult("fill",array(EResult::COLOR)),
	new MethodResult("noFill"),
	new MethodResult("stroke",array(EResult::COLOR)),
	new MethodResult("noStroke"),
	new MethodResult("color",array(EResult::COLOR)),
	new MethodResult("backGround",array(EResult::COLOR)),
	new MethodResult("clear"));



function iProgEval($list,$proc=null) { //&$environment = null) {
	if($list instanceof IProgToken) {
		switch($list->type) {
		case IProgToken::INTEGER_LITERAL:
			return new EResult(EResult::INTEGER,$list->value+0);
		case IProgToken::OCTAL_LITERAL:
			return new EResult(EResult::INTEGER,base_convert($list->value,8,10)+0);
		case IProgToken::HEX_LITERAL:
			return new EResult(EResult::INTEGER,base_convert(substr($list->value,2),16,10)+0);
		case IProgToken::CHAR_LITERAL:
			return new EResult(EResult::INTEGER,ord(substr($list->value,1,1)));
		case IProgToken::FLOAT_LITERAL:
			return new EResult(EResult::FLOAT,$list->value+0.0);
		case IProgToken::COLOR_LITERAL:
			return new EResult(EResult::COLOR,substr($list->value,1));
		case IProgToken::STRING_LITERAL:
			return new EResult(EResult::STRING,substr($list->value,1,strlen($list->value)-2));
		case IProgToken::IDENTIFIER: // valiable
			if($proc instanceof Processing) {
				$v = $proc->getResult($list->value);
				return $v;
			}
/*
			if(isset($environment['constant']) && isset($environment['constant'][$list->value])) {
				if($environment['constant'][$list->value] instanceof EResult)
					return new EResult($environment['constant'][$list->value]->type,
								$environment['constant'][$list->value]->value);
				else if(is_int($environment['constant'][$list->value]))
					return new EResult(EResult::INTEGER,$environment['constant'][$list->value]);
				else if(is_float($environment['constant'][$list->value]))
					return new EResult(EResult::FLOAT,$environment['constant'][$list->value]);
			}
			if(isset($environment['valiable']) && isset($environment['valiable'][$list->value])) {
				if($environment['valiable'][$list->value] instanceof EResult)
					return new EResult($environment['valiable'][$list->value]->type,
								$environment['valiable'][$list->value]->value);
				else if(is_int($environment['valiable'][$list->value]))
					return new EResult(EResult::INTEGER,$environment['valiable'][$list->value]);
				else if(is_float($environment['valiable'][$list->value]))
					return new EResult(EResult::FLOAT,$environment['valiable'][$list->value]);
			}
			return new EResult(EResult::ERROR,"undefined valiable '".$list->value."' at line ".
				 $list->line. " chars " . $list->cPos . ".");
			*/
		default:
			return new EResult(EResult::ERROR,"invalid context '".$list->type."'.");
		}
	} else if($list instanceof IProgParse) {
		switch($list->type) {
		case IProgParse::EXPRESSION:
		case IProgParse::LITERAL_EXPRESSION:
		case IProgParse::NUMERIC_EXPRESSION:
		case IProgParse::BOOLEAN_EXPRESSION:
			return iProgEvalExpression($list,$proc); // environment);
		case IProgParse::PAREN_EXPRESSION:
			return iProgEvalExpression($list->tokenList[1],$proc); // environment); // ( expression )
		case IProgParse::METHOD_EXPRESSION:
			return iProgEvalMethod($list,$proc); //environment); // method calling
		default:
			return null;
		}
	} else return null;
}

function iProgEvalExpression($list,$proc = null) { // &$environment = null) {
	if(! $list->isExpression()) return null;
	if(count($list->tokenList) == 1) return iProgEval($list->tokenList[0],$proc); // environment);
	if(count($list->tokenList) == 2) { // 前置き演算子か後おき演算子
		if( $list->tokenList[0] instanceof IProgToken && $list->tokenList[0]->type == IProgToken::OPERATOR) { // 前置き
			switch( $list->tokenList[0]-> value ) {
			case "-": // 単項のマイナス
				$val = iProgEval($list->tokenList[1],$proc); // $environment);
				if(! isset($val) ) return new EResult(EResult::ERROR,"単項演算子'-'には数値の対象が必要です" .
													atLinePos($list->tokenList[0]));
				if($val->type == EResult::INTEGER || $val->type == EResult::FLOAT) {
					$val->value = 0 - $val->value;
					return $val;
				}
				return new EResult(EResult::ERROR,"単項演算子'-'には数値の対象が必要です" .
													atLinePos($list->tokenList[0]));
			case "+": // 単項のプラス
				$val = iProgEval($list->tokenList[1],$proc); // environment);
				if(! isset($val) ) return new EResult(EResult::ERROR,"単項演算子'+'には数値の対象が必要です" .
													atLinePos($list->tokenList[0]));
				if($val->type == EResult::INTEGER || $val->type == EResult::FLOAT) return $val;
				return new EResult(EResult::ERROR,"単項演算子'+'には数値の対象が必要です" .
													atLinePos($list->tokenList[0]));
			case "!": // 論理の否定
				$val = iProgEval($list->tokenList[1],$proc); // $environment);
				if(! isset($val) ) return new EResult(EResult::ERROR,"単項演算子'!'には論理型の対象が必要です" .
													atLinePos($list->tokenList[0]));
				if($val->type == EResult::BOOLEAN) {
					$val->value = ! $val->value;
					return $val;
				}
				return new EResult(EResult::ERROR,"単項演算子'!'には論理型の対象が必要です" .
													atLinePos($list->tokenList[0]));
			default:
				return new EResult(EResult::ERROR,"扱えない演算子'" . $list->tokenList[0]->value ."です。" .
													atLinePos($list->tokenList[0]));
			}
		} else if( $list->tokenList[1] instanceof IProgToken && $list->tokenList[1]->type == IProgToken::OPERATOR) { //後おき
			switch( $list->tokenList[1]-> value ) {
			default:
				return new EResult(EResult::ERROR,"扱えない演算子'" . $list->tokenList[1]->value ."です。" .
													atLinePos($list->tokenList[1]));
			}
		}
		// 構文エラー
	}
	if(count($list->tokenList) == 3) { // 中置き演算子
		if(! ($list->tokenList[1] instanceof IProgToken) || $list->tokenList[1]->type != IProgToken::OPERATOR) // 構文エラー
				return new EResult(EResult::ERROR,"演算子が必要です" . atLinePos($list->tokenList[1]));
		switch( $list->tokenList[1]-> value ) {
		case "+":
			$val1 = iProgEval($list->tokenList[0],$proc); // $environment);
			if(!isset($val1) || $val1->type==EResult::RENULL)
				return new EResult(EResult::ERROR,"この演算子'+'には左辺値が必要です。" . atLinePos($list->tokenList[1]));
			if($val1->type <= 0) return $val1;
			$val2 = iProgEval($list->tokenList[2],$proc); // $environment);
			if(!isset($val2) || $val2->type==EResult::RENULL)
				return new EResult(EResult::ERROR,"この演算子'+'には右辺値が必要です。" . atLinePos($list->tokenList[1]));
			if($val2->type <= 0) return $val2;
			if($val1->type == EResult::INTEGER) {
				switch($val2->type) {
				case EResult::INTEGER: return new EResult(EResult::INTEGER,$val1->value + $val2->value);
				case EResult::FLOAT: return new EResult(EResult::FLOAT,$val1->value + $val2->value);
				case EResult::STRING: return new EResult(EResult::STRING, $val1->value . $val2->value);
				default:
					return new EResult(EResult::ERROR,"右辺値は数値か、文字列でなければなりません" . atLinePos($list->tokenList[1]));
				}
			}
			if($val1->type == EResult::FLOAT) {
				switch($val2->type) {
				case EResult::INTEGER:
				case EResult::FLOAT: return new EResult(EResult::FLOAT,$val1->value + $val2->value);
				case EResult::STRING: return new EResult(EResult::STRING, $val1->value . $val2->value);
				default:
					return new EResult(EResult::ERROR,"右辺値は数値か、文字列でなければなりません" . atLinePos($list->tokenList[1]));
				}
			}
			if($val1->type == EResult::STRING || $val2->type ==　EResult::STRING) {
				return new EResult(EResult::STRING, $val1->value . $val2->value);
			}
			return new EResult(EResult::EROOR,"+演算子には扱えないデータ型です" . atLinePos($list->tokenList[1]));
		case "-":
			$val1 = iProgEval($list->tokenList[0],$proc); // $environment);
			if(!isset($val1) || $val1->type==EResult::RENULL)
				return new EResult(EResult::ERROR,"この演算子'-'には左辺値が必要です。" . atLinePos($list->tokenList[1]));
			if($val1->type <= 0) return $val1; // error
			$val2 = iProgEval($list->tokenList[2],$proc); // $environment);
			if(!isset($val2) || $val2->type==EResult::RENULL)
				return new EResult(EResult::ERROR,"この演算子'-'には右辺値が必要です。" . atLinePos($list->tokenList[1]));
			if($val2->type <= 0) return $val2; // error
			if($val1->type == EResult::INTEGER && $val2->type == EResult::INTEGER) // int - int  
				return new EResult(EResult::INTEGER,$val1->value - $val2->value);
			if(($val1->type == EResult::INTEGER || $val1->type == EResult::FLOAT)&&
				($val2->type == EResult::INTEGER || $val2->type == EResult::FLOAT)) //float - float
				return new EResult(EResult::FLOAT,$val1->value - $val2->value);
			return new EResult(EResult::EROOR,"-演算子には扱えないデータ型です" . atLinePos($list->tokenList[1]));
		case "*":
			$val1 = iProgEval($list->tokenList[0],$proc); // $environment);
			if(!isset($val1) || $val1->type==EResult::RENULL)
				return new EResult(EResult::ERROR,"この演算子'*'には左辺値が必要です。" . atLinePos($list->tokenList[1]));
			if($val1->type <= 0) return $val1; // error
			$val2 = iProgEval($list->tokenList[2],$proc); //$environment);
			if(!isset($val2) || $val2->type==EResult::RENULL)
				return new EResult(EResult::ERROR,"この演算子'*'には右辺値が必要です。" . atLinePos($list->tokenList[1]));
			if($val2->type <= 0) return $val2; // error
			if($val1->type == EResult::INTEGER && $val2->type == EResult::INTEGER) // int * int  
				return new EResult(EResult::INTEGER,$val1->value * $val2->value);
			if(($val1->type == EResult::INTEGER || $val1->type == EResult::FLOAT)&&
				($val2->type == EResult::INTEGER || $val2->type == EResult::FLOAT)) //float * float
				return new EResult(EResult::FLOAT,$val1->value * $val2->value);
			return new EResult(EResult::EROOR,"*演算子には扱えないデータ型です" . atLinePos($list->tokenList[1]));
		case "/":
			$val1 = iProgEval($list->tokenList[0],$proc); // $environment);
			if(!isset($val1) || $val1->type==EResult::RENULL)
				return new EResult(EResult::ERROR,"この演算子'/'には左辺値が必要です。" . atLinePos($list->tokenList[1]));
			if($val1->type <= 0) return $val1; // error
			$val2 = iProgEval($list->tokenList[2],$proc); //$environment);
			if(!isset($val2) || $val2->type==EResult::RENULL)
				return new EResult(EResult::ERROR,"この演算子'/'には右辺値が必要です。" . atLinePos($list->tokenList[1]));
			if($val2->type <= 0) return $val2; // error
			if($val2->value == 0) 
				return new EResult(EResult::ERROR,"0による除算が発生しました。" . atLinePos($list->tokenList[1]));

			if($val1->type == EResult::INTEGER && $val2->type == EResult::INTEGER) // int / int  
				return new EResult(EResult::INTEGER,(int)floor($val1->value / $val2->value));
			if(($val1->type == EResult::INTEGER || $val1->type == EResult::FLOAT)&&
				($val2->type == EResult::INTEGER || $val2->type == EResult::FLOAT)) //float / float
				return new EResult(EResult::FLOAT,$val1->value / $val2->value);
			return new EResult(EResult::EROOR,"/演算子には扱えないデータ型です" . atLinePos($list->tokenList[1]));

		case "%":
			$val1 = iProgEval($list->tokenList[0],$proc); // $environment);
			if(!isset($val1) || $val1->type==EResult::RENULL)
				return new EResult(EResult::ERROR,"この演算子'%'には左辺値が必要です。" . atLinePos($list->tokenList[1]));
			if($val1->type <= 0) return $val1; // error
			$val2 = iProgEval($list->tokenList[2],$proc); // $environment);
			if(!isset($val2) || $val2->type==EResult::RENULL)
				return new EResult(EResult::ERROR,"この演算子'%'には右辺値が必要です。" . atLinePos($list->tokenList[1]));
			if($val2->type <= 0) return $val2; // error
			if($val2->value == 0) 
				return new EResult(EResult::ERROR,"0による除算が発生しました。" . atLinePos($list->tokenList[1]));

			if($val1->type == EResult::INTEGER && $val2->type == EResult::INTEGER) // int % int  
				return new EResult(EResult::INTEGER,$val1->value % $val2->value);
			if(($val1->type == EResult::INTEGER || $val1->type == EResult::FLOAT)&&
				($val2->type == EResult::INTEGER || $val2->type == EResult::FLOAT)) //float % float
				return new EResult(EResult::FLOAT,fmod($val1->value , $val2->value));
			return new EResult(EResult::EROOR,"/演算子には扱えないデータ型です" . atLinePos($list->tokenList[1]));
		default:
			return new IProgEval(EResult::ERROR,"扱えない演算子'" . $list->tokenList[1]->value ."です。" .
													atLinePos($list->tokenList[1]));
		}
	}
}

function iProgEvalMethod($list,$proc = null){ //&$environment = null) {
	global $methodList;
}

function iProgEvalArgList($list,$proc=null){ // &$environment = null) {
	if($list->type != IProgParse::ARGUMENT_LIST)
		return new EResult(EResult::ERROR,"引数リストではありません。");
	$l = $list->tokenList;
	$al = array();
	while(count($l) > 0) {
		$al[] = iProgEval(array_shift($l),$proc); //$environment);
		if(count($l) > 0) {
			$c = array_shift($l);
			if($c->value != ',')
				return new EResult(EResult::ERROR,"引数リストはコンマで区切られていなければいけません。");
		}
	}
	return $al;
}

function iProgEvalGetMethodResult($parse,$proc) { // $environment = null) {
	if(is_null($parse) || $parse->type != IProgParse::METHOD_EXPRESSION) return null;
	$name = $parse->tokenList[0]->value; // method name
	if($parse->tokenList[2]->type == IProgParse::ARGUMENT_LIST) {
		$argList = iProgEvalArgList($parse->tokenList[2],$environment);
		return new MethodResult($name,$argList);
    }
	return new MethodResult($name);
}

function getMethodResultByStrings($str,$proc) { // $environment=null) {
	$ss = preg_split('/\/\//',$str);// print_r($ss);
	$mr = array();
	while($ss) {
		$tl = new TokenList(array_shift($ss)); // print_r($tl);
		$result = parseExpression($tl->tokenList);
		if(is_array($result) && $result[0] instanceof IProgParse && $result[0]->type == IProgParse::METHOD_EXPRESSION)
			$mr[] = iProgEvalGetMethodResult($result[0],$proc); // $environment);
		else if(is_array($result) && $result[0] instanceof IProgParseError) 
			$mr[] = $result[0];
	}
	return $mr;
}

// iProgEquals($a,$b); a と b の一致を見る
function iProgEquals($a,$b,$proc=null){ // $environment = null) {
	if($a instanceof IProgParse && $a instanceof IProgParse) {
		if($a->type == $b->type) {
			if(count($a->tokenList) == count($b->tokenList)) {
				for($i=0;$i<count($a->tokenList); $i++) {
					if(! iProgEquals($a->tokenList[$i],$b->tokenList[$i],$proc)) // $environment))
						return false;
				}
				return true;
			} else {
				return false;
			}

		} else {
			return false;
		}
	} else if($a instanceof IProgToken && $a instanceof IProgToken) {
		if($a->type == $b->type && $a->value == $b-> valse) return true;
		// 比較しなければいけないものがいっぱいある
		return false;
	}
	// 構文のちょっとした違いを吸収しなければいけない
	return false;
}

1;
?>

