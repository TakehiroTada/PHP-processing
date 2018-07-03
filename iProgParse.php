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
	const CASTING_EXPRESSION = 1070;
	const EXPRESSION_LIST = 1500;
	const ARGUMENT_LIST = 1510;

	const IPT_MODIFIER = 1600;
	const IPT_TYPE = 1601;
	const IPT_ARRAY_INDICATOR = 1602;
	const VARIABLE_DECLARATOR = 1610;
	const VARIABLE_DECLARATION = 1611;
	const TYPE_SPECIFIER = 1620;
	const TYPE_DECLARATION = 1621;

	const METHOD_DECLARATION = 1700;
	const PARAMETER          = 1701;
	const PARAMETER_LIST     = 1702;

	const STATEMENT = 2000;
	const EMPTY_STATEMENT = 2001;
	const SIMPLE_STATEMENT = 2010;
	const IF_STATEMENT = 2020;
	const WHILE_STATEMENT = 2030;
	const DO_STATEMENT = 2040;
	const SWITCH_STATEMENT = 2050;
	const FOR_STATEMENT  = 2060;
	const TRY_STATEMENT  = 2070;
	const STATEMENT_BLOCK = 2100;

	const DOC_COMMENT = 2200;

	const CLASS_NAME = 2300;
	const INTERFACE_NAME = 2310;
	const PACKEAGE_NAME = 2320;

	public $type;
	public $tokenList;

	function __construct($t = 0) {
		$this->type = $t;
		$this->tokenList = array();
	}

	function isError() { return $this->type <= 0; }

	function isExpression() { return 1000 <= $this->type && $this->type < 1500 ; }

	function typeEqual($arg) {
		if($arg instanceof IProgParse) return ($this->type == $arg->type);
		return false;
	}
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

function parseExpression($tokenList,$environment=null,$prece=100) {
	global $InfixOperatorList,$PrefixOperatorList,$PostfixOperatorList;
	if(is_null($tokenList)) return null;
	$parse = null;

	
	//print "parseException(top=".$tokenList[0]->value.",prece=$prece);<br>\n";
	while(count($tokenList)>0) {
	//printf("あ<br>");

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
				$result = parseExpression($tokenList,$environment,$opa->getPrece());
				if(is_null($result)) { // 無いはず

				}
				if($result[0]->isError()) return $result;

				$parse     = new IProgParse(IProgParse::NUMERIC_EXPRESSION);
				$parse->tokenList = array($top,$op,$result[0]);
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
		
			if(is_null($parse) && strcmp($top->value,'null')==0) { //null
				array_shift($tokenList);
				$parse = new IProgParse(IProgParse::EXPRESSION);
				$parse->tokenList = array($top);
				return array($parse,$tokenList);
			}
			
			if(is_null($parse) && strcmp($top->value,'super')==0) { //null
				array_shift($tokenList);
				$parse = new IProgParse(IProgParse::EXPRESSION);
				$parse->tokenList = array($top);
				return array($parse,$tokenList);
			}

			if(is_null($parse) && strcmp($top->value,'this')==0) { //null
				array_shift($tokenList);
				$parse = new IProgParse(IProgParse::EXPRESSION);
				$parse->tokenList = array($top);
				return array($parse,$tokenList);
			}

			$parse = array_shift($tokenList);
			//var_dump($tokenList);
			continue;
		}
	
									

		if(is_null($parse) && strcmp($top->value,'(')==0) { // ( expression ): キャスト演算子の処理は入っていない
			array_shift($tokenList);

			if( parseType($tokenList,$environment=null) != null){ //parseCastingExpression
		
				$result =  parseType($tokenList,$environment=null);
				$tokenList = $result[1];

				if(is_null($tokenList) || strcmp($tokenList[0]->value,')')!=0) {
					$err = new IProgParseError("対応する閉じ括弧が見つかりません。". atLinePos($top));
					return array($err,$tokenList);
				}

				$sec = array_shift($tokenList);
				if(parseExpression($tokenList,$environment) == null) {
					//expエラー
				}

				$result2 = parseExpression($tokenList,$environment); //castのexp部分の判定
				$tokenList = $result2[1];


				$parse = new IProgParse(IProgParse::CASTING_EXPRESSION);
				$parse->tokenList = array($top,$result[0],$sec,$result2[0]);
				//return array($parse,$tokenList);
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
			$parse = new IProgParse(IProgParse::PAREN_EXPRESSION);
			$parse->tokenList = array($top,$result[0],array_shift($tokenList));
			continue;
		}

		if(! is_null($parse) && strcmp($top->value,'(')==0) { // expression ( expression ): 関数の呼び出し
			array_shift($tokenList);
			if(is_null($tokenList)) { // エラー
			}
			$result = parseArgList($tokenList,$environment);

			if(is_null($result) && strcmp($tokenList[0]->value,')')==0) { // 引数なし関数
				$p2 = $parse;
				$parse = new IProgParse(IProgParse::METHOD_EXPRESSION);
				$parse->tokenList = array($p2,$top,array_shift($tokenList));
				continue;
			}
//			$result = parseArgList($tokenList,$environment);
//			if($result == null) { // エラー
//				$err = new IProgParseError("括弧の中が存在しないか、この場所には不適当です。". atLinePos($top));
//				return array($err,$tokenList);
//			}
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
			if(is_null($tokenList) || count($tokenList) == 0 || strcmp($tokenList[0]->value,']')!=0) {
				$err = new IProgParseError("対応する閉じ括弧が見つかりません。". atLinePos($top));
				return array($err,$tokenList);
			}
			$p2 = $parse;
			$parse = new IProgParse(IProgParse::ARRAY_ITEM_EXPRESSION);
			$parse->tokenList = array($p2,$top,$result[0],array_shift($tokenList));
			continue;
		}




		/* if(! is_null($parse) && strcmp($top->value,'.')==0) { // exp.exp
		var_dump($tokenList);
			$top = array_shift($tokenList);
			if(is_null($tokenList)) { // エラー
			}
			$result = parseExpression($tokenList,$environment);
			if($result == null) { // エラー
				$err = new IProgParseError("exp.expのエラーの処理". atLinePos($top));
				return array($err,$tokenList);
			}
			if($result[0]->isError()) return $result; // エラーをそのまま返す。
			$tokenList = $result[1];

			$parse = new IProgParse(IProgParse::ARRAY_ITEM_EXPRESSION);
			//$parse->tokenList = array($p2,$top,$result[0],array_shift($tokenList));
			continue;
		}
		*/
		
		/*
		if(! is_null($parse) && strcmp($top->value,',')==0) { // exp,exp
			$top = array_shift($tokenList);
			if(is_null($tokenList)) { // エラー
			}
			$result = parseExpression($tokenList,$environment);
			if($result == null) { // エラー
				$err = new IProgParseError("exp.expのエラーの処理". atLinePos($top));
				return array($err,$tokenList);
			}
			if($result[0]->isError()) return $result; // エラーをそのまま返す。
			$tokenList = $result[1];

			$parse = new IProgParse(IProgParse::ARRAY_ITEM_EXPRESSION);
			//$parse->tokenList = array($p2,$top,$result[0],array_shift($tokenList));
			continue;
		}
		*/



		break;
/*		if(! is_null($parse)) break;
		$err = new IProgParseError("この場所には不適当です。". atLinePos($top));
		return array($err,$tokenList); */
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
	//
	if(empty($tokenList)) return null;

	switch($tokenList[0]->value) {
	case ';': // empty statement
		$top = array_shift($tokenList);
		$parse = new IProgParse(IProgParse::EMPTY_STATEMENT);
		$parse->tokenList = array($top);
		return array($parse,$tokenList);
	case '{': // statement block
		return parseStatementBlock($tokenList,$environment);
	case 'if': // if statement
		return parseIfStatement($tokenList,$environment);
	case 'for': // for statement
		return parseForStatement($tokenList,$environment);
	case 'do': // if statement
		return parseDoStatement($tokenList,$environment);
	case 'try': // try statement
		return parseTryStatement($tokenList,$environment);
	case 'switch': // switch statement
		return parseSwitchStatement($tokenList,$environment); 
	case 'while': // while statement
		return parseWhileStatement($tokenList,$environment); 

	/*case '/**': // doc comment
		return parseDocComment($tokenList,$environment);
		*/
		
	}


	if(! is_null($result = parseVariableDeclaration($tokenList,$environment))) return $result;

	if(! is_null($result = parseExpression($tokenList,$environment))) {
		$tokenList = $result[1];
		if($result[0]->isError()) return $result;
		if(is_null($tokenList) || count($tokenList) == 0) {
			$err = new IProgParseError("式の終りのセミコロンがありません。");
			return array($err,$tokenList);
		}
		$top = array_shift($tokenList);
		if($top->value != ';') {
			$err = new IProgParseError("この前にセミコロンが必要です。". atLinePos($top));
			return array($err,$tokenList);
		}
		$parse = new IProgParse(IProgParse::SIMPLE_STATEMENT);
		$parse->tokenList = array( $result[0],$top);
		return array($parse,$tokenList);
	}


	return null;
}
// 複文
function parseStatementBlock($tokenList,$environment=null) {
	//
	if(is_null($tokenList)) return null;
	if($tokenList[0]->value != '{') return null;
	$top = array_shift($tokenList); //tokenlistの先頭を取り出して代入
	$blocks = array(); //配列作成
	while(! is_null($tokenList) && $tokenList[0]->value != '}') {
		$r1 = parseStatement($tokenList,$environment);
		if(is_null($r1)) {
			$err = new IProgParseError("構文エラーです");
			return array($err,$tokenList);
		} else if($r1[0]->isError()) return $r1;
		$blocks[] = $r1[0];
		$tokenList = $r1[1];
	}
	
	if(is_null($tokenList)) {
		$err = new IProgParseError("'{'に対応する'}'がありません。");
		return array($err,array($top));
	}

	$parse = new IProgParse(IProgParse::STATEMENT_BLOCK);
	array_unshift($blocks,$top); //topをblocksの先頭に
	array_push($blocks,array_shift($tokenList)); //blocksに tokenlistの先頭を抜いたものを代入（先頭の入れ替え？）
	$parse->tokenList = $blocks;

	//var_dump($parse);
	//var_dump($tokenList);
	//var_dump($blocks);

	return array($parse,$tokenList);
}
// if文
function parseIfStatement($tokenList,$environment=null){
	if(is_null($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成

	/* ifと'('の判定（①と②) */
	if($tokenList[0]->value != 'if') {
		return null; //先頭がifでなかったらエラー
		} else {
		array_push($blocks,array_shift($tokenList)); 
		}
		if($tokenList[0]->value != '('){	
		$err = new IProgParseError("ifの次は'('でないといけません。");
		return array($err,$tokenList);
		} else {
		 array_push($blocks,array_shift($tokenList));
		}

	/* perseExppression② */
	$result =  parseExpression($tokenList,$environment=null); 

	if(is_null($result)){
		$err = new IProgParseError("演算子は、この場所には不適当です。");//エラー
		return array($err,$tokenList);
	}
	if($result[0]->isError()) return $result;
	$blocks[2] = $result[0];
	$tokenList = $result[1];


	/* ')'の部分③ */
	if($tokenList[0]->value != ')'){
		$err = new IProgParseError("ifの条件式は')'で閉じなければなりません");
		return array($err,$tokenList);
	}
	array_push($blocks,array_shift($tokenList));
		 
	/* perseStatementBlock④ */
	$result = parseStatement($tokenList,$environment=null);
	if(is_null($result)){
		return null;
	}
	if($result[0]->isError()) return $result;
	$blocks[4] = $result[0];
	$tokenList = $result[1];

	/* else⑤ parseStatement⑥ */
	if(empty($tokenList)){
		$parse = new IProgParse(IProgParse::IF_STATEMENT);
		$parse->tokenList = $blocks;
	
		return array($parse,$tokenList);
	}
		//var_dump($tokenList);
	if($tokenList[0]->value == 'else'){
		array_push($blocks,array_shift($tokenList));
		//var_dump($tokenList);

		$result = parseStatement($tokenList,$environment=null);

		if(is_null($result)){
			//エラー
		}
		if($result[0]->isError()) return $result;
		$blocks[6] = $result[0];
		$tokenList = $result[1];
	} 
	
	/* output */
	$parse = new IProgParse(IProgParse::IF_STATEMENT);
	$parse->tokenList = $blocks;
	
	return array($parse,$tokenList);
}

function parseForStatement($tokenList,$environment=null){
	if(is_null($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成

	/* forと'('の判定 */
	if($tokenList[0]->value != 'for') {
		return null; //先頭がforでなかったらエラー
	} else {
		array_push($blocks,array_shift($tokenList)); //'for'をtokenListから除外してtopへ格納
	}

	if($tokenList[0]->value != '('){	
		$err = new IProgParseError("forの次は'('でないといけません。");
		return array($err,$tokenList);
	} else {
		array_push($blocks,array_shift($tokenList));
	}

	if(parseVariableDeclaration($tokenList,$environment=null) == null) {
		if(parseExpression($tokenList,$environment=null) != null){
			$result = parseExpression($tokenList,$environment=null);
			array_push($blocks,$result[0]);
			$tokenList = $result[1];//result_Variable[1]を$tokenListに
		} 

		if($tokenList[0]->value != ';'){
			$err = new IProgParseError("セミコロンがありません。");
			return array($err,$tokenList);
		} else {
			array_push($blocks,array_shift($tokenList));//セミコロン削除
		}

	} else {
		$result = parseVariableDeclaration($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];//result_Variable[1]を$tokenListに
	}

	if(parseExpression($tokenList,$environment=null) != null){
		$result = parseExpression($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];//result_Variable[1]を$tokenListに
	} else {
		//エラー
	}
	if($tokenList[0]->value != ';'){
		$err = new IProgParseError("セミコロンがありません。");
		return array($err,$tokenList);
	} else {
		array_push($blocks,array_shift($tokenList));//セミコロン削除
	}

	if(parseExpression($tokenList,$environment=null) != null){
		$result = parseExpression($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];//result_Variable[1]を$tokenListに
		}
	if($tokenList[0]->value != ')'){
		$err = new IProgParseError("for文は')'で閉じなければなりません。");
		return array($err,$tokenList);
		} else {
		 array_push($blocks,array_shift($tokenList));//セミコロン削除
		}
	if(parseStatement($tokenList,$environment=null) != null){
		$result = parseStatement($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];//result_Variable[1]を$tokenList
	} else {
		//エラー
	}

	/* output */
	$parse = new IProgParse(IProgParse::FOR_STATEMENT);
	$parse->tokenList = $blocks;
	return array($parse,$tokenList);
}

function parseWhileStatement($tokenList,$environment=null){
	if(is_null($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成

	/* whileと'('の判定 */
	if($tokenList[0]->value != 'while') {
		return null; //先頭がwhileでなかったらエラー
		} else {
		$blocks[0] = array_shift($tokenList); //'for'をtokenListから除外してtopへ格納
		}
		if($tokenList[0]->value != '('){	
		$err = new IProgParseError("whileの次は'('でないといけません。");
		return array($err,$tokenList);
		} else {
		 $blocks[1] = array_shift($tokenList);
		}

		if(parseExpression($tokenList,$environment=null) != null){
			$result = parseExpression($tokenList,$environment=null);
			array_push($blocks,$result[0]);
			$tokenList = $result[1];//result_Variable[1]を$tokenListに
		} else {
			//エラー
			$err = new IProgParseError("条件式が誤っています");
			return array($err,$tokenList);
		}
		if($tokenList[0]->value != ')'){
			$err = new IProgParseError("while文は')'で閉じなければなりません。");
			return array($err,$tokenList);
		} else {
			array_push($blocks,array_shift($tokenList));//')'削除
		}
		if(parseStatement($tokenList,$environment=null) != null){
			$result = parseStatement($tokenList,$environment=null);
			array_push($blocks,$result[0]);
			$tokenList = $result[1];//result_Variable[1]を$tokenList
		} else {
		//エラー
		}

		/* output */
		$parse = new IProgParse(IProgParse::WHILE_STATEMENT);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);

}

function parseDoStatement($tokenList,$environment=null){
	if(is_null($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成

	/* doの判定 */
	if($tokenList[0]->value != 'do') {
		return null; //先頭がdoでなかったらエラー
		} else {
		$blocks[0] = array_shift($tokenList); //'for'をtokenListから除外してtopへ格納
		}
	if(parseStatement($tokenList,$environment=null) != null){
		$result = parseStatement($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];//result_Variable[1]を$tokenList
	} else {
		//エラー
	}	
	
	if($tokenList[0]->value != 'while') {
		return null; //whileでなかったらエラー
		} else {
		array_push($blocks,  array_shift($tokenList));
		}
		if($tokenList[0]->value != '('){	
		$err = new IProgParseError("whileの次は'('でないといけません。");
		return array($err,$tokenList);
		} else {
		 array_push($blocks,  array_shift($tokenList));
		}

	if(parseExpression($tokenList,$environment=null) != null){
		$result = parseExpression($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];//result_Variable[1]を$tokenListに
	} else {
		//エラー
		$err = new IProgParseError("条件式が誤っています");
		return array($err,$tokenList);
	}
	if($tokenList[0]->value != ')'){
		$err = new IProgParseError("')'がありません。");
		return array($err,$tokenList);
	} else {
		array_push($blocks,array_shift($tokenList));//')'削除
	}

	if($tokenList[0]->value != ';'){
		$err = new IProgParseError("セミコロンがありません。");
		return array($err,$tokenList);
	} else {
		array_push($blocks,array_shift($tokenList));//セミコロン削除
	}

	/* output */
	$parse = new IProgParse(IProgParse::DO_STATEMENT);
	$parse->tokenList = $blocks;
	return array($parse,$tokenList);
}

function parseSwitchStatement($tokenList,$environment=null){
	if(is_null($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成

	/* whileと'('の判定 */
	if($tokenList[0]->value != 'switch') {
		return null; //先頭がswitchでなかったらエラー
		} else {
		$blocks[0] = array_shift($tokenList); //'switch'をtokenListから除外してtopへ格納
		}
	if($tokenList[0]->value != '('){	
		$err = new IProgParseError("switchの次は'('でないといけません。");
		return array($err,$tokenList);
	} else {
		$blocks[1] = array_shift($tokenList);
	}

	if(parseExpression($tokenList,$environment=null) != null){
		$result = parseExpression($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];
		
	} else {
		//エラー
		$err = new IProgParseError("条件式が誤っています");
		return array($err,$tokenList);
	}
	if($tokenList[0]->value != ')'){
		$err = new IProgParseError("switch文は')'で閉じなければなりません。");
		return array($err,$tokenList);
	} else {
		array_push($blocks,array_shift($tokenList));//')'削除
	}

	if($tokenList[0]->value != '{'){
		$err = new IProgParseError("'{'がありません");
		return array($err,$tokenList);
	} else {
		array_push($blocks,array_shift($tokenList));//')'削除
	}
	
	while( ($tokenList[0]->value == 'case') || (parseStatement($tokenList,$environment=null) != null) || ($tokenList[0]->value == 'default') ){

		if($tokenList[0]->value == 'case'){
			array_push($blocks,array_shift($tokenList));
			if(parseExpression($tokenList,$environment=null) != null){
				$result = parseExpression($tokenList,$environment=null);
				array_push($blocks,$result[0]);
				$tokenList = $result[1];//result_Variable[1]を$tokenListに
			} else {
				//エラー
				$err = new IProgParseError("条件式が誤っています");
				return array($err,$tokenList);
			}
		

			if($tokenList[0]->value != ':'){
				$err = new IProgParseError("':'がありません");
				return array($err,$tokenList);
			} else {
				array_push($blocks,array_shift($tokenList));//')'削除
			}
		

		} else if($tokenList[0]->value == 'default'){
			array_push($blocks,array_shift($tokenList));
			if($tokenList[0]->value != ':'){
	
				$err = new IProgParseError("':'がありません2");
				return array($err,$tokenList);
			} else {
				array_push($blocks,array_shift($tokenList));//')'削除
			}

		} else if(parseStatement($tokenList,$environment=null) != null){
			$result = parseStatement($tokenList,$environment=null);
			array_push($blocks,$result[0]);
			$tokenList = $result[1];//result_Variable[1]を$tokenList

		} else {
			//エラー
		}
		
	}
	
	if($tokenList[0]->value != '}'){
		$err = new IProgParseError("'}'がありません");
		return array($err,$tokenList);
	} else {
		array_push($blocks,array_shift($tokenList));//')'削除
	}


	/* output */
	$parse = new IProgParse(IProgParse::SWITCH_STATEMENT);
	$parse->tokenList = $blocks;
	return array($parse,$tokenList);

}

function parseTryStatement($tokenList,$environment=null){

	if(empty($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成
	if($tokenList[0]->value != 'try') {
		return null; //先頭がtryでなかったらエラー
	} else {
		array_push($blocks,array_shift($tokenList)); //'for'をtokenListから除外してtopへ格納
	}

	if(parseStatement($tokenList,$environment=null) != null){
		$result = parseStatement($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];//result_Variable[1]を$tokenList
	} else {
		//エラー
	}

	if(empty($tokenList)) return null; //tokenListがなくなったらエラー
	while($tokenList[0]->value != 'catch'){
		array_push($blocks,array_shift($tokenList)); 

		if($tokenList[0]->value != '('){
			$err = new IProgParseError("'('がありません。");
			return array($err,$tokenList);
		} else {
		 array_push($blocks,array_shift($tokenList));//セミコロン削除
		}
		if(parseParameter($tokenList,$environment=null) != null){
			$result = parseParameter($tokenList,$environment=null);
			array_push($blocks,$result[0]);
			$tokenList = $result[1];
		} else {
			//エラー
		}

		if($tokenList[0]->value != ')'){
			$err = new IProgParseError("')'がありません。");
			return array($err,$tokenList);
		} else {
		 array_push($blocks,array_shift($tokenList));//セミコロン削除
		}

		if(parseStatement($tokenList,$environment=null) != null){
			$result = parseStatement($tokenList,$environment=null);
			array_push($blocks,$result[0]);
			$tokenList = $result[1];//result_Variable[1]を$tokenList
		} else {
		//エラー
		}

	}

	if($tokenList[0]->value == 'finally'){
		 array_push($blocks,array_shift($tokenList));
		 if(parseStatement($tokenList,$environment=null) != null){
			$result = parseStatement($tokenList,$environment=null);
			array_push($blocks,$result[0]);
			$tokenList = $result[1];//result_Variable[1]を$tokenList
		} else {
		//エラー
		}
	}
	/* output */
	$parse = new IProgParse(IProgParse::TRY_STATEMENT);
	$parse->tokenList = $blocks;
	return array($parse,$tokenList);
}

// 変数宣言
function parseVariableDeclaration($tokenList,$environment=null) {
	global $Type_specifierList;
	$mList = array();
	if($ml = parseModifier($tokenList,$environment)) { // Modifier
		$mList[] = $ml[0];
		$tokenList = $ml[1];
	}
	if(! $tokenList) return null;
	$t2 = parseType($tokenList,$environment);
	if(!$t2) return null;
	$type = $t2[0];
	$tokenList = $t2[1];

	$vdList = array();
	while(true) {
		$vd = parseVariableDeclarator($tokenList,$environment);
		if(!$vd) return null; // need return error
		$tokenList = $vd[1];
		$vdList[] = $vd[0];
		if(count($tokenList) > 0 && $tokenList[0]->value == ',') {
			$vdList[] = array_shift($tokenList);
			continue;
		}
		break;
    }
	if(count($vdList) >= 1 && count($tokenList) > 0 && $tokenList[0]->value == ';') { // success
		$vd = new IProgParse(IProgParse::VARIABLE_DECLARATION);
		$vd->tokenList = $mList;
		$vd->tokenList[] = $type;
		while(count($vdList)>0) $vd->tokenList[] = array_shift($vdList);
		$vd->tokenList[] = array_shift($tokenList);
		return array($vd,$tokenList);
	}

	return null;
}

function parseVariableDeclarator($tokenList,$environment=null) {
	if(count($tokenList) <= 0) return null;
	$top = $tokenList[0];
	if($top->type != IProgToken::IDENTIFIER) return null;
	array_shift($tokenList);
	$sList = null;
	$r2 = parseArrayBracket($tokenList,$environment);
	if($r2) {
		$sList = $r2[0];
		$tokenList = $r2[1];
	}
	$vi = null;
	if(count($tokenList) > 0 && $tokenList[0]->value == '=') {
		$eq = array_shift($tokenList);
		$vi = parseVariableInitializer($tokenList,$environment);
		if(! $vi) {
			return array(new IProgParseError("初期値が見つかりません。". atLinePos($eq)),$tokenList);
		} 
		$tokenList = $vi[1];
		$vi = $vi[0];
	}
	$vd = new IProgParse(IProgParse::VARIABLE_DECLARATOR);
	$vd->tokenList[] = $top;
	if($sList) $vd->tokenList[] = $sList;
    if($vi) {
		$vd->tokenList[] = $eq;
		$vd->tokenList[] = $vi;
	}
	return array($vd,$tokenList);
}

function parseVariableInitializer($tokenList,$environment=null) {
	if(count($tokenList) <= 0) return null;
	if($tokenList[0]->value == '{') { // may be array initializer
		//koko
		return null;
	}
	if($et = parseExpression($tokenList,$environment,15)) return $et;
	return null;
} 

function parseArrayBracket($tokenList,$environment) {
	if(count($tokenList) > 2 && $tokenList[0]->value == '[' &&
		$tokenList[1]->value == ']') {
		$ai = new IProgParse(IProgParse::IPT_ARRAY_INDICATOR);
		$ai->tokenList[] = array_shift($tokenList); 
		$ai->tokenList[] = array_shift($tokenList); 
		if($an = parseArrayBracket($tokenList,$environment)) {
			$ai->tokenList[] = $an[0];
			return array($ai,$an[1]);
		}
		return array($ai,$tokenList);
	}
	return null;
}

function parseModifier($tokenList,$environment=null) {
	global $ModifierList;
//	$ml = array();
	if($tokenList && isset($ModifierList[$tokenList[0]->value])) {
		$modi = new IProgParse(IProgParse::IPT_MODIFIER);
		$modi->tokenList[] = array_shift($tokenList);
		if($ml = parseModifier($tokenList,$environment)) {
			$modi->tokenList[] = $ml[0];
			$tokenList = $ml[1];
		}
		return array($modi,$tokenList);
	}
	return null;
}

function parseType($tokenList,$environment=null) {
	global $Type_specifierList; //specifierのリスト
	if(count($tokenList) < 1) return null;
	$top = $tokenList[0];
	if(isset($Type_specifierList[$top->value])) { // Type_specifire
		array_shift($tokenList);
		$sList = array();
		$r2 = parseArrayBracket($tokenList,$environment);
		if($r2) {
			$sList[] = $r2[0];
			$tokenList = $r2[1];
		}
		array_unshift($sList,$top);
		$ty = new IProgParse(IProgParse::IPT_TYPE);
		$ty->tokenList = $sList;
		return array($ty,$tokenList);
	}
	return null;
	
	/*if(empty($tokenList)) return null;
	$blocks = array();
	if(parseTypeSpecifier($tokenList,$environment=null) != null) {
		$result = parseTypeSpecifier($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];//

		$result = parseArrayBracket($tokenList,$environment);
		if($result) {
			array_push($blocks,$result[0]);
			$tokenList = $result[1];//
		}
		//array_unshift($sList,$top);
		$ty = new IProgParse(IProgParse::IPT_TYPE);
		$ty->tokenList = $blocks;
		return array($ty,$tokenList);
	} else {
		return null; //エラー
	}
	*/
}

function parseTypeDeclaration($tokenList,$environment=null) {
	if(empty($tokenList)) return null;
	$blocks = array();

	if( parseDocComment($tokenList,$environment=null) != null ){

	}

	if( parseClassDeclaration($tokenList,$environment=null) != null ){

	} else if(parseInterfaceDeclaration($tokenList,$environment=null) != null ){

	}

	if($tokenList[0]->value != 'if') {
		return null; //先頭がifでなかったらエラー
	} else {
		array_push($blocks,array_shift($tokenList)); //'if'をtokenListから除外してtopへ格納
	}


	/* output */
	$parse = new IProgParse(IProgParse::TYPE_DECLARATION);
	$parse->tokenList = $blocks;
	return array($parse,$tokenList);
	
}

/*function parseTypeSpecifier($tokenList,$environment=null) {
	if(empty($tokenList)) return null;
	$blocks = array();
	
	switch($tokenList[0]->value) {
	case 'boolean': //boolean
		array_push($blocks,array_shift($tokenList));
		$parse = new IProgParse(IProgParse::TYPE_SPECIFIER);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	case 'byte':	//byte
		array_push($blocks,array_shift($tokenList));
		$parse = new IProgParse(IProgParse::TYPE_SPECIFIER);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	case 'char':	//char
		array_push($blocks,array_shift($tokenList));
		$parse = new IProgParse(IProgParse::TYPE_SPECIFIER);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	case 'short':	//short
		array_push($blocks,array_shift($tokenList));
		$parse = new IProgParse(IProgParse::TYPE_SPECIFIER);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	case 'int':		//int
		array_push($blocks,array_shift($tokenList));
		$parse = new IProgParse(IProgParse::TYPE_SPECIFIER);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	case 'float':	//float
		array_push($blocks,array_shift($tokenList));
		$parse = new IProgParse(IProgParse::TYPE_SPECIFIER);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	case 'long':	//long
		array_push($blocks,array_shift($tokenList));
		$parse = new IProgParse(IProgParse::TYPE_SPECIFIER);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	case 'double':	//double
		array_push($blocks,array_shift($tokenList));
		$parse = new IProgParse(IProgParse::TYPE_SPECIFIER);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	}
	if (parseClassName($tokenList,$environment=null) != null){
		$parse = new IProgParse(IProgParse::TYPE_SPECIFIER);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	}

	if (parseInterfaceName($tokenList,$environment=null) != null){
		$parse = new IProgParse(IProgParse::TYPE_SPECIFIER);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	}

}*/

function parseParameterList($tokenList,$environment=null) {
	$pl = array();
	while($pp = parseParameter($tokenList,$environment)) {
		$pl[] = $pp[0];
		$tokenList = $pp[1];
		if(count($tokenList) < 1 || $tokenList[0].value != ',') break;
		$pl[] = array_shift($tokenList);
	}
	if(count($pl) <= 0) return null;
	$ppl = new IProgParse(IProgParse::PARAMETER_LIST);
	$ppl->tokenList = $pl;
	return array($ppl,$tokenList);
}

function parseParameter($tokenList,$environment=null) {
	if(count($tokenList) < 2) return null;
	if(!($ty = parseType($tokenList,$environment))) return null;
	$top = $ty[0];
	$tokenList = $ty[1];
	if(count($tokenList) < 1) return null;
 	$id = $tokenList[0];
	if($id->type != IProgToken::IDENTIFIRE) return null;
	array_shift($tokenList);
	$pp = new IProgParse(IProgParse::PARAMETER);
	$pp->tokenList = array($top,$id);
	if($ab = parseArrayBracket($tokenList,$environment)) {
		$pp->tokenList[] = $ab[0];
		$tokenList = $ab[1];
	}
	return array($pp,$tokenList);
}
	
function parseArgList($tokenList,$environment=null) {
	$al = array();
	while($pp = parseExpression($tokenList,$environment,16)) {
		$al[] = $pp[0];
		$tokenList = $pp[1];
		if(count($tokenList) < 1 || $tokenList[0]->value != ',') break;
		$al[] = array_shift($tokenList);
	}
	if(count($al) <= 0) return null;
	$ppl = new IProgParse(IProgParse::ARGUMENT_LIST);
	$ppl->tokenList = $al;
	return array($ppl,$tokenList);
}

function parseClassName($tokenList,$environment=null){
	if(empty($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成
	if( (parseIdentifier($tokenList,$environment=null)) != null){
		//trueなら終了
		array_push($blocks,array_shift($tokenList)); 
		$parse = new IProgParse(IProgParse::CLASS_NAME);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	} else if( parsePackage_name($tokenList,$environment=null) != null){
		array_push($blocks,array_shift($tokenList)); 
		if($tokenList[0]->value != '.'){
			return null;
		}
		if( (parseIdentifier($tokenList,$environment=null)) != null){
			//trueなら終了
			array_push($blocks,array_shift($tokenList)); 
			$parse = new IProgParse(IProgParse::CLASS_NAME);
			$parse->tokenList = $blocks;
			return array($parse,$tokenList);
		}
	} else {
		return null;
	}
}

function parseInterfaceName($tokenList,$environment=null){
	if(empty($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成
	if( (parseIdentifier($tokenList,$environment=null)) != null){
		//trueなら終了
		array_push($blocks,array_shift($tokenList)); 
		$parse = new IProgParse(IProgParse::INTERFACE_NAME);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	} else if( parsePackage_name($tokenList,$environment=null) != null){
		array_push($blocks,array_shift($tokenList)); 
		if($tokenList[0]->value != '.'){
			return null;
		}
		if( (parseIdentifier($tokenList,$environment=null)) != null){
			//trueなら終了
			array_push($blocks,array_shift($tokenList)); 
			$parse = new IProgParse(IProgParse::INTERFACE_NAME);
			$parse->tokenList = $blocks;
			return array($parse,$tokenList);
		}
	} else {
		return null;
	}
}

function parseDocComment($tokenList,$environment=null){
	if(empty($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成
	if($tokenList[0]->value != '/**'){
		return null; //error
	}
	array_push($blocks,array_shift($tokenList)); // '/**'の削除
	while($tokenList[0] == emtpy){
		if($tokenList[0]->value =='*/'){
			array_push($blocks,array_shift($tokenList));
			break;
		}
		array_push($blocks,array_shift($tokenList));
	}

	/* output */
	$parse = new IProgParse(IProgParse::DOC_COMMENT);
	$parse->tokenList = $blocks;
	return array($parse,$tokenList);

}

function parseClassDeclaration($tokenList,$environment=null){
}

function parseInterfaceDeclaration($tokenList,$environment=null){
}

function parseIdentifier($tokenList,$environment=null){
/*
	if(tokenList[0]->type != 'IDENTIFIER'){
		return null;
	}
*/
}

function parsePackage_name($tokenList,$environment=null){
/*
	if(empty($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成
	if( (parseIdentifier($tokenList,$environment=null)) != null){
		//trueなら終了
		array_push($blocks,array_shift($tokenList)); 
		$parse = new IProgParse(IProgParse::PACKEAGE_NAME);
		$parse->tokenList = $blocks;
		return array($parse,$tokenList);
	} else if( parsePackage_name($tokenList,$environment=null) != null){
		array_push($blocks,array_shift($tokenList)); 
		if($tokenList[0]->value != '.'){
			return null;
		}
		if( (parseIdentifier($tokenList,$environment=null)) != null){
			//trueなら終了
			array_push($blocks,array_shift($tokenList)); 
			$parse = new IProgParse(IProgParse::PACKEAGE_NAME);
			$parse->tokenList = $blocks;
			return array($parse,$tokenList);
		}
	} else {
		return null;
	}
	*/
}

function parseMethod_Declaration($tokenList,$environment=null){
	var_dump($tokenList);
	if(empty($tokenList)) return null; //tokenListがなくなったらエラー
	$blocks = array(); //配列作成

	if( parseModifier($tokenList,$environment=null) != null ) {//modifierの判定
		$result = parseModifier($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];
	}

	if( parseType($tokenList,$environment=null) != null){//typeの判定
		$result = parseType($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];
	} else {
		return null; //エラー
	}

	if( parseExpression($tokenList,$environment=null) != null){//identifierの判定（Expressionの中に組み込んである)
		$result = parseExpression($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];
	} else {
		return null; //エラー
	}

	if($tokenList[0]->value != '('){//'('の判定
		array_push($blocks,array_shift($tokenList));	
		
	} else {
		$err = new IProgParseError("'('がありません。");
		return array($err,$tokenList);	 
	}

	if( parseParameterList($tokenList,$environment=null) != null){//parameter_listの判定
		$result = parseType($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];
	}

	if($tokenList[0]->value != '('){//'('の判定
		array_push($blocks,array_shift($tokenList));	
		
	} else {
		$err = new IProgParseError("'('がありません。");
		return array($err,$tokenList);	 
	}

	if($tokenList[0]->value != '['){//'['の判定
		array_push($blocks,array_shift($tokenList));	

		if($tokenList[0]->value != '['){//']'の判定
			array_push($blocks,array_shift($tokenList));
		} else {
			$err = new IProgParseError("']'がありません。");
			return array($err,$tokenList);
		}
	}

	if(parseStatementBlock($tokenList,$environment=null) != null){//statementblcokの判定
		$result = parseStatementBlock($tokenList,$environment=null);
		array_push($blocks,$result[0]);
		$tokenList = $result[1];
	} else if($tokenList[0]->value != ';'){	//';'の判定
		array_push($blocks,array_shift($tokenList));
	} else {
		return null; //エラー
	}
}
	

// 出力用
function spaces($n) {
	while($n>0) { print " "; $n--; }
}

function  printResult($tree,$level=0) {
	switch ($tree->type) {
	case IProgParse::EXPRESSION: print spaces($level*3)."EXPRESSION\n"; break;
	case IProgParse::LITERAL_EXPRESSION: print spaces($level*3)."LITERAL_EXPRESSION\n"; break;
	case IProgParse::NUMERIC_EXPRESSION: print spaces($level*3)."NUMERIC_EXPRESSION\n"; break;
	case IProgParse::BOOLEAN_EXPRESSION: print spaces($level*3)."BOOLEAN_EXPRESSION\n"; break;
	case IProgParse::METHOD_EXPRESSION: print spaces($level*3)."METHOD_EXPRESSION\n"; break;
	case IProgParse::PAREN_EXPRESSION: print spaces($level*3)."PAREN_EXPRESSION\n"; break;
	case IProgParse::VALIABLE_EXPRESSION: print spaces($level*3)."BALIABLE_EXPRESSION\n"; break;
	case IProgParse::CASTING_EXPRESSION: print spaces($level*3)."CASTING_EXPRESSION\n"; break;
	case IProgParse::ARGUMENT_LIST: print spaces($level*3)."ARGLIST\n"; break;
	case IProgParse::EXPRESSION_LIST: print spaces($level*3)."EXPRESSION_LIST\n"; break;
	case IProgParse::ARRAY_ITEM_EXPRESSION: print spaces($level*3)."ARRAY_ITEM_EXPRESSION\n"; break;
	case IProgParse::IPT_MODIFIER: print spaces($level*3)."MODIFIRE\n"; break;
	case IProgParse::IPT_TYPE: print spaces($level*3)."TYPE\n"; break;
	case IProgParse::IPT_ARRAY_INDICATOR: print spaces($level*3)."ARRAY INDICATOR\n"; break;
	case IProgParse::VARIABLE_DECLARATOR: print spaces($level*3)."VARIABLE_DECLARATOR\n"; break;
	case IProgParse::VARIABLE_DECLARATION: print spaces($level*3)."VARIABLE_DECLARATION\n"; break;
	case IProgParse::TYPE_SPECIFIER: print spaces($level*3)."TYPE_SPECIFIER\n"; break;
	case IProgParse::TYPE_DECLARATION: print spaces($level*3)."TYPE_DECLARATION\n"; break;

	case IProgParse::DOC_COMMENT: print spaces($level*3)."DOC_COMMENT\n"; break;

	case IProgParse::STATEMENT: print spaces($level*3)."STATEMENT\n"; break;
	case IProgParse::EMPTY_STATEMENT: print spaces($level*3)."EMPTY_STATEMENT\n"; break;
	case IProgParse::SIMPLE_STATEMENT: print spaces($level*3)."SIMPLE_STATEMENT\n"; break;
	case IProgParse::STATEMENT_BLOCK: print spaces($level*3)."SIMPLE_STATEMENT\n"; break;

	case IProgParse::IF_STATEMENT: print spaces($level*3)."IF_STATEMENT\n"; break;
	case IProgParse::FOR_STATEMENT: print spaces($level*3)."FOR_STATEMENT\n"; break;
	case IProgParse::WHILE_STATEMENT: print spaces($level*3)."WHILE_STATEMENT\n"; break;
	case IProgParse::DO_STATEMENT: print spaces($level*3)."DO_STATEMENT\n"; break;
	case IProgParse::SWITCH_STATEMENT: print spaces($level*3)."SWITCH_STATEMENT\n"; break;
	case IProgParse::TRY_STATEMENT: print spaces($level*3)."TRY_STATEMENT\n"; break;

	case IProgParse::CLASS_NAME: print spaces($level*3)."CLASS_NAME\n"; break;
	case IProgParse::INTERFACE_NAME: print spaces($level*3)."INTERFACE_NAME\n"; break;
	case IProgParse::PACKEAGE_NAME: print spaces($level*3)."PACKEAGE_NAME\n"; break;

	default: print spaces($level*3)."Unknown type(".$tree->type.")\n";
	}
	foreach($tree->tokenList as $token) {
		if(get_class($token) == 'IProgToken') print spaces($level*3+3).$token->value."\n";
		else printResult($token,$level+1);
	}

}

function  GD_Draw($tree,$level=0) {
var_dump($tree);
	switch ($tree->type) {
		case IProgParse::METHOD_EXPRESSION: 
			//print spaces($level*3)."METHOD_EXPRESSION\n";
			printf("true");
			break;

		default: 
			//printf("false");
			//print spaces($level*3)."Unknown type(".$tree->type.")\n";
	}
	foreach($tree->tokenList as $token) {
		if(get_class($token) == 'IProgToken') print spaces($level*3+3).$token->value."\n";
		else GD_Draw($token,$level+1);
	}

}
/*
$tl = new TokenList(";");
//$tl = new TokenList("1+2+3+4;");
//$tl = new TokenList("a = b = c = 1+2+3+4;");
//$tl = new TokenList("a == b && c != 1+2+3+4;");

//$tl = new TokenList(" list(a++,--b,!a);");//+1;"); //line();"); //0+50,0,-100,100);");
//$tl = new TokenList(" a[3/2]++;");//+1;"); //line();"); //0+50,0,-100,100);");
//$tl = new TokenList(" line(1+1,2,100,-90)"); //line();"); //0+50,0,-100,100);");
$tl->removeComment(true);

$w = parseStatement($tl->tokenList);

print "<pre>\n";
print_r($w);
print_r($tl);

print "</pre>\n";

foreach($tl->tokenList as $t) {
	printf("%s,%d<br>\n",$t->value,$t->type);
}

*/

?>