<?php

// エラー出力する場合
ini_set( 'display_errors', 1 );

include_once "iProgToken.php";

class IProgParse {
	const EXPRESSION = 1000;
	const LITERAL_EXPRESSION = 1010;
	const NUMERIC_EXPRESSION = 1020;
	const BOOLEAN_EXPRESSION = 1021;
	const PAREN_EXPRESSION =   1030;
	const METHOD_EXPRESSION = 1040;
	const VALIABLE_EXPRESSION = 1050;
	const ARRAY_ITEM_EXPRESSION = 1060;
	const EXPRESSION_LIST = 1500;

	public $type;
	public $tokenList;

	function __construct($t = 0) {
		$this->type = $t;
		$this->tokenList = array();
	}

	function isError() { return $this->type <= 0; }

}

class IProgEnvironment {
	var $constants;
	var $variables;
	var $fonctions;
}

class IProgParseError {
	var $message;

	function __construct($t = null) {
		if(is_null($t)) {
			$this->message = "unknown error";
		} else {
			$this->message = $t;
		}
	}

	function isError() { return true; }

}


class IProgParseFactory {
	public $tokenList;

	function __construct($in = null) {
		if(is_null($in)) {
		} else if(is_string($in)) {
			$this->tokenList = new TokenList($in);
			$this->tokenList->removeComment(true);
		}
	}

}

function tokenListRemoveComment($tokenList) {
	$result = array();
	if(is_null($tokenList)) return null;

	foreach($tokenList as $token) {
		if($token->type >= 100) { // 簡易的判断
			array_push($result,$token);
		}
	}
	if(count($result) > 0) return $result;
	return null;
}

	// 中置表現の数式の解析、引数は左因子、演算子、残りのトークンリスト、環境
function parseInfixExpression($leftOperand,$op,$tokenList,$environment=null){
//	print "infix operator = ". $op->value . "<br>\n";
	global $InfixOperatorList;
	$opa = $InfixOperatorList[$op->value];

	$result = parseExpression($tokenList,$environment,$opa->getPrece());
	if(is_null($result)) { // 右因子が無い：エラー
		$e = new IProgParseError("演算子の右因子がありません". atLinePos($op));
		return array($e,$tokenList);
	}
	if($result[0]->isError()) return $result; // エラーをそのまま返す。
	$extype = IProgParse::NUMERIC_EXPRESSION;
	if($opa->type == IProgOperator::BOOLEAN) $extype = IProgParse::BOOLEAN_EXPRESSION;
	$parse = new IProgParse($extype);
	$parse->tokenList = array($leftOperand,$op,$result[0]);
	return array($parse,$result[1]);
}

function parseExpression($tokenList,$environment=null,$prece=100) {
	global $InfixOperatorList;
	if(is_null($tokenList)) return null;
	$parse = null;

	while(!is_null($tokenList)) {
		$top = $tokenList[0];
		if(is_null($parse) && count($tokenList)>=2 && isset($PrefixOperatorList[$top->value])) { // 
			$opa = $PrefixOperatorList[$top->value];
			if($opa->prece < $prece) {
				array_shift($tokenList);
				$result = parseExpression($tokenList,$environment,$opa->getPrece());
				if(is_null($result)) { // エラー
					$err = new IProgParseError("演算子(".$top->value.")は、この場所には不適当です。". atLinePos($top));
					return array($err,$tokenList);
				}
				if($result[0]->isError()) return $result;
				$parse = new IProgParse(IProgParse::NUMERIC_EXPRESSION);
				$parse->tokenList = array($top,$result[0]);
				$tokenList = $result[1];
				continue;
			}
		}
		if(! is_null($parse) && count($tokenList)>=2 && isset($InfixOperatorList[$top->value])) { // 
			$opa = $InfixOperatorList[$top->value];
			if($opa->prece < $prece) {
				$op = array_shift($tokenList);
				$top = $parse;
				$result = parseInfixExpression($top,$op,$tokenList,$environment,$opa->getPrece());
				if(is_null($result)) { // 無いはず

				}
				if($result[0]->isError()) return $result;

				$parse     = $result[0];
				$tokenList = $result[1];
				continue;
			}
		}
		if(! is_null($parse) && count($tokenList)>=1 && isset($PostfixOperatorList[$top->value])) { // 
			$opa = $PostfixOperatorList[$top->value];
			if($opa->prece < $prece) {
				array_shift($tokenList);
				$p2 = $parse;
				$parse = new IProgParse(IProgParse::NUMERIC_EXPRESSION);
				$parse->tokenList = array($p2, $top);
				continue;
			}
		}


		if(is_null($parse) && $top->isLiteral()) {
			$parse = array_shift($tokenList);
			continue;
		}

		if(is_null($parse) && $top->type == IProgToken::IDENTIFIER) {
			$parse = array_shift($tokenList);
			continue;
		}

		if(is_null($parse) && strcmp($top->value,'(')==0) { // ( expression ): キャスト演算子の処理は入っていない
			array_shift($tokenList);
			$result = parseExpression($tokenList,$environment);
			if($result == null) { // エラー
				$err = new IProgParseError("括弧の中が存在しないか、この場所には不適当です。". atLinePos($top));
				return array($err,$tokenList);
			}
			if($result[0]->isError()) return $result; // エラーをそのまま返す。
			$tokenList = $result[1];
			if(is_null($tokenList) || strcmp($tokenList[0]->value,')')!=0) {
				$err = new IProgParseError("対応する閉じ括弧が見つかりません。". atLinePos($top));
				return array($err,$tokenList);
			}
			$parse = new IProgParse(IProgParse::NUMERIC_EXPRESSION);
			$parse->tokenList = array($top,$result[0],array_shift($tokenList));
			continue;
		}

		if(! is_null($parse) && strcmp($top->value,'(')==0) { // expression ( expression ): 関数の呼び出し
			array_shift($tokenList);
			if(is_null($tokenList)) { // エラー
			}

			if(strcmp($tokenList[0]->value,')')==0) { // 引数なし関数
				$p2 = $parse;
				$parse = new IProgParse(IProgParse::METHOD_EXPRESSION);
				$parse->tokenList = array($p2,$top,array_shift($tokenList));
				continue;
			}
			$result = parseExpression($tokenList,$environment);
			if($result == null) { // エラー
				$err = new IProgParseError("括弧の中が存在しないか、この場所には不適当です。". atLinePos($top));
				return array($err,$tokenList);
			}
			if($result[0]->isError()) return $result; // エラーをそのまま返す。
			$tokenList = $result[1];
			if(is_null($tokenList) || strcmp($tokenList[0]->value,')')!=0) {
				$err = new IProgParseError("対応する閉じ括弧が見つかりません。". atLinePos($top));
				return array($err,$tokenList);
			}
			$p2 = $parse;
			$parse = new IProgParse(IProgParse::METHOD_EXPRESSION);
			$parse->tokenList = array($p2,$top,$result[0],array_shift($tokenList));
			continue;
		}

		if(! is_null($parse) && strcmp($top->value,'[')==0) { // expression [ expression ]: 配列
			array_shift($tokenList);
			if(is_null($tokenList)) { // エラー
			}

			$result = parseExpression($tokenList,$environment);
			if($result == null) { // エラー
				$err = new IProgParseError("添え字が存在しないか、この場所には不適当です。". atLinePos($top));
				return array($err,$tokenList);
			}
			if($result[0]->isError()) return $result; // エラーをそのまま返す。
			$tokenList = $result[1];
			if(is_null($tokenList) || strcmp($tokenList[0]->value,']')!=0) {
				$err = new IProgParseError("対応する閉じ括弧が見つかりません。". atLinePos($top));
				return array($err,$tokenList);
			}
			$p2 = $parse;
			$parse = new IProgParse(IProgParse::ARRAY_ITEM_EXPRESSION);
			$parse->tokenList = array($p2,$top,$result[0],array_shift($tokenList));
			continue;
		}

		if(! is_null($parse)) break;
		$err = new IProgParseError("この場所には不適当です。". atLinePos($top));
		return array($err,$tokenList);
	}
	if(! is_null($parse)) {
		if(get_class($parse) == "IProgToken") {
			$et = IProgParse::NUMERIC_EXPRESSION;
			$p2 = $parse;
			$parse = new IProgParse($et);
			$parse->tokenList = array($p2);
		}
		return array($parse,$tokenList);
	}
	return null;
}

// 文
function parseStatement($tokenList,$environment=null) {
	
}



/*
		// エラーだよね。
		$err = IProgParseError("この場所には不適当です。". atLinePos($top));

		if(count($tokenList)>=3 && isset($InfixOperatorList[$tokenList[1]->value])) {
			$opa = $InfixOperatorList[$tokenList[1]->value];
			if($opa->prece < $prece) {
				array_shift($tokenList);
				$op = array_shift($tokenList);
				$result = parseInfixExpression($top,$op,$tokenList,$environment,$opa->getPrece());
				if(is_null($result)) { // 無いはず
				}
				if($result[0]->isError()) return $result;
				return $result;
			}
		}
		if(count($tokenList)>=2 && isset($PostfixOperatorList[$tokenList[1]->value])) {
			$opa = $PostfixOperatorList[$tokenList[1]->value]; // リテラルの後ろに単項演算子はほんとは無い
			if($opa->prece < $prece) {
				array_shift($tokenList);
				$op = array_shift($tokenList);
				$parse = new IProgParse(IProgParse::NUMERIC_EXPRESSION);
				$parse->tokenList = array($top,$op);
				return array($parse,$tokenList);
			}
		}
		$lit = new IProgParse(IProgParse::LITERAL_EXPRESSION);
		array_push($lit->tokenList,$top);
		array_shift($tokenList);
		return array($lit,$tokenList);
 
	} else if($top->type == IProgToken::OPERATOR) {
		array_shift($tokenList);
		switch($top->value) {
		case '-':   // 単項のマイナス。単項プラスはJava/Processingにはない
			$result = parseExpression($tokenList,$environment);
			if(! is_null($result)) {
				if(! $result[0]->isError()) {
					$parse = new IProgParse(IProgParse::NUMERIC_EXPRESSION);
					array_push($parse->tokenList,$top);
					array_push($parse->tokenList,$result[0]);
					return array($parse,$result[1]);
				}
				return $result; // エラーをそのまま返す。
			}
			break;
		default:
			break;
		}
		$err = new IProgParseError("演算子(".$top->value.")は、この場所には不適当です。". atLinePos($top));
		return array($err,$tokenList);

	} else if($top->type == IProgToken::SEPARATOR) {
		if(strcmp($top->value,"(")==0) { // キャストはまだやっていない
			$result = parseExpression($tokenList,$environment);
			if(is_null($result)) {
				array_shift($tokenList);
				$err = new IProgParseError("構文エラー". atLinePos($top));
				return array($err,$tokenList);
			}
			if($result[0]->isError())
				return $result; // エラーをそのまま返す。
			$tokenList = $result[1];
			if(count($tokenList) <= 0 || strcmp($tokenList[0]->value,")")!=0) {
				$err = new IProgParseError("構文エラー: 対応する閉括弧がありません。". atLinePos($top));
				return array($err,$tokenList);
			}
			$parse = new IProgParse(IProgParse::PAREN_EXPRESSION);
			array_push($parse->tokenList,$top);
			array_push($parse->tokenList,$result[0]);
			array_push($parse->tokenList,array_shift($tokenList));
			return array($parse,$tokenList);
		}
		return null;
	} else if($top->type == IProgToken::IDENTIFIER) {
		if(count($tokenList)==1) {
			$parse = new IProgParse(IProgParse::IDENTIFIER_EXPRESSION);
			array_push($parse->tokenList,$top);
			return array($parse,null);
		}
		// ここで new 演算子、sizeof演算子のチェックを行うべき
		array_shift($tokenList);
		$next = $tokenList[0];
		if($next->type == IProgToken::SEPARATOR) {
			switch($next->value) {
			case '(': // メソッド呼び出し
				array_shift($tokenList);
				if(count($tokenList) <= 0) { }// エラーだぜ
				if(strcmp($tokenList[0]->value,')')==0) { // 引数なし呼び出し
					$parse = new IProgParse(IProgParse::METHOD_EXPRESSION);
					array_push($parse->tokenList,$top);
					array_push($parse->tokenList,$next);
					array_push($parse->tokenList,array_shift($tokenList));
					return array($parse,$tokenList);
				}
				$res = parseExpression($tokenList,$environment);
				if(is_null($res)) {return null; }; // エラー出す
				if($res[0]->isError()) return $res;
				$tokenList = $res[1];
				if(count($tokenList) <= 0 ||
					strcmp($tokenList[0]->value,')')!=0) { }// エラーだぜ

				$parse = new IProgParse(IProgParse::METHOD_EXPRESSION);
				array_push($parse->tokenList,$top);
				array_push($parse->tokenList,$next);
				array_push($parse->tokenList,$res[0]);
				array_push($parse->tokenList,array_shift($tokenList));
				return array($parse,$tokenList);
			case '[': // 配列
				array_shift($tokenList);
				if(count($tokenList) <= 0) { }// エラーだぜ
				if(strcmp($tokenList[0]->value,']')==0) { // 添え字なし＝＞エラー
				}
				$res = parseExpression($tokenList,$environment);// List?
				if(is_null($res)) {return null;}; // エラー出す
				if($res[0]->isError()) return $res;
				$tokenList = $res[1];
				if(count($tokenList) <= 0 ||
					strcmp($tokenList[0]->value,']')!=0) { }// エラーだぜ

				$parse = new IProgParse(IProgParse::ARRAY_ITEM_EXPRESSION);
				array_push($parse->tokenList,$top);
				array_push($parse->tokenList,$next);
				array_push($parse->tokenList,$res[0]);
				array_push($parse->tokenList,array_shift($tokenList));
				return array($parse,$tokenList);
			default:
				break;
			}
			// 
			$parse = new IProgParse(IProgParse::IDENTIFIER_EXPRESSION);
			array_push($parse->tokenList,array_shift($tokenList));
			return array($parse,$tokenList);
		}
	}
	last:
	}
}

function parseExpressionList($tokenList,$environment=null) {
	if(is_null($tokenList)) return null;

	$exList = array();
	$comma  = null;
	while(! is_null($ex = parseExpression($tokenList,$environment))) {
		$comma  = null;
		if($ex[0]->isError()) return $ex;
		array_push($exList,$ex[0]);
		$tokenList = $ex[1];
		if(count($tokenList)<=0) break;
		if(strcmp($tokenList[0]->value,",")!=0) break; // コンマ以外は中断
		$comma = array_shift($tokenList);
		array_push($exList,$comma);
	}
	if(! is_null($comma)) {
		$err = new IProgParseError("余分なコンマがあります。". atLinePos($comma));
		return array($err,$tokenList);
	}
	if(count($exList) == 0) return null; // なし
	if(count($exList) == 1) return array($exList[0],$tokenList); // 1個だけ
	$parse = new IProgParse(IProgParse::EXPRESSION_LIST);
	$parse->tokenList = $exList;
	return array($parse,$tokenList);
}
*/




$tl = new TokenList("   a[3/2]+2;");//+1;"); //line();"); //0+50,0,-100,100);");
//$tl = new TokenList("a.line(1+1,2);"); //line();"); //0+50,0,-100,100);");
$tl->removeComment(true);

$w = parseExpression($tl->tokenList);

print "<pre>\n";
print_r($w);
print_r($tl);

print "</pre>\n";

foreach($tl->tokenList as $t) {
	printf("%s,%d<br>\n",$t->value,$t->type);
}


?>

