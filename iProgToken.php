<?php

// エラー出力する場合
//ini_set( 'display_errors', 1 );

$OneCharOperator =
		array('!','%','&','*','+','-','/','<','=','>','?','^','|','~');
$TwoCharOperator =
		array('!=','%=','&&','&=','*=','++','+=','--','-=','/=','<<','<=','==',
			'>>','>=','^=','|=','||','~=');
$ThreeCharOperator = array('<<=','>>=','>>>');

$TokenError = array("unknown error", "invalid character",
	"unterminated String","unterminated character literal","unterminated Comment");

$InFixOperator =
	array('%','&','*','+','-','/','<','=','>','?','^','|','~','!=','%=','&&','&=','*=',
		'+=','-=','/=','<<','<=','==','>>','>=','^=','|=','||','~=','<<=','>>=');

// 一部にlocale 設定が必要な関数が使ってあります。このクラスは
// C で設定されている事を前提に作っています。
class IProgToken {
    const UNKNOWN      = 0;
    const INVALID_CHARACTER    = -1;
    const UNTERMINATED_STRING  = -2;
    const UNTERMINATED_CHARACTER= -3;
    const UNTERMINATED_COMMENT = -4;
    const CHRACTER_FORMAT_ERROR= -5;

    const COMMENT       = 10;
    const LINE_COMMENT  = 11;
    const BLOCK_COMMENT = 12;

    const LITERAL          = 100;
    const INTEGER_LITERAL  = 101;
    const OCTAL_LITERAL    = 102;
    const HEX_LITERAL      = 103;
    const CHAR_LITERAL     = 109;
    const FLOAT_LITERAL    = 110;
    const COLOR_LITERAL    = 140;
    const STRING_LITERAL   = 150;
	
    const IDENTIFIER   = 200;
    const OPERATOR     = 300;
    const SEPARATOR    = 400;

    public $type = IProgToken::UNKNOWN;
    public $value;
    public $line;   // 行位置(Tokenの位置)
    public $cPos;   // 文字位置(Tokenの位置)

    function __construct($fst=null,$snd=null) {
	if(is_null($snd)) {
	} else { // 2引数は位置の設定
	    $this->line = $fst;
	    $this->cPos = $snd;
	}
    }

    function isError() { return $this->type <= 0; }

    function setValue($v) {
	$this->value = $v;
    }
    function setType($t) {
	$this->type = $t;
    }

    function getLine() { return $this->line; }
    function getCPos() { return $this->cPos; }

    function isComment() { 
	return ($this->type == IProgToken::COMMENT ||
		$this->type == IProgToken::LINE_COMMENT ||
		$this->type == IProgToken::BLOCK_COMMENT);
    }
    function isIntegerLiteral() { 
	return ($this->type == IProgToken::INTEGER_LITERAL ||
		$this->type == IProgToken::OCTAL_LITERAL ||
		$this->type == IProgToken::HEX_LITERAL ||
		$this->type == IProgToken::CHAR_LITERAL);
    }
    function isFloatLiteral() { 
	return ($this->type == IProgToken::FLOAT_LITERAL);
    }
    function isNumberLiteral() {
	return $this->isIntegerLiteral() || $this->isFloatLiteral();
    }
    function isNumber() { return isNumberLiteral() ; }
    function isStringLiteral() { 
	return ($this->type == IProgToken::STRING_LITERAL);
    }
    function isColorLiteral() { 
	return ($this->type == IProgToken::Color_LITERAL);
    }
    function isLiteral() { // 簡易的定義
	return (IProgToken::LITERAL <= $this->type && $this->type < IProgToken::IDENTIFIER);
    }
    function isInfixOperator() {
	global $InFixOperator;
	if($this->type != IProgToken::OPERATOR) return false;
	return(in_array($this->value,$InFixOperator));
    }

    function typeEqual($arg) {
	if($arg instanceof IProgToken) return ($this->type == $arg->type);
	return false;
    }

    function getNumber($environment = null) {
	if($this->isNumber()) {
	    switch($this->type) {
	    case IProgToken::INTEGER_LITERAL:
	    case IProgToken::OCTAL_LITERAL:
	    case IProgToken::HEX_LITERAL:  return intval($this->value,0);
	    case IProgToken::CHAR_LITERAL: return ord(substr($this->value,1,1)); // 簡易的
	    case IProgToken::FLOAT_LITERAL:
	    case IProgToken::DOUBLE_LITERAL: return floatval($this->value); // ダブル型はphpでは。。。
	    default: return null;
	    }
	}
	if($this->type == IProgToken::COLOR_LITERAL) {

	}
	if($this->type == IProgToken::IDENTIFIRE) {

	}
	return null;
    }
}

class IProgTokenFactory {

    private $strs;
    private $next;
    private $length;
    private $line;   // 行数
    private $cPos;   // 文字数
    function __construct($str) {
	$this->strs = str_split($str);
	$this->next = 0;
	$this->line = 0;
	$this->length = count($this->strs);
    }

    function nextToken() {
	global $OneCharOperator,$TwoCharOperator,$ThreeCharOperator;
	$char = $this->nextPChar();
	if(is_null($char)) return null; // データ終了
	$token = new IProgToken($this->line,$this->cPos);

	if(ctype_alpha($char) || ord($char) == ord('_')) { // 識別子
	    $v = $char;
	    $this->next ++; $this->cPos++;
	    while($this->next < $this->length && 
		(ctype_alnum($this->strs[$this->next]) || ord($this->strs[$this->next]) == ord('_'))) {
		$v .= $this->strs[$this->next];
		$this->next++; $this->cPos++;
	    }
	    $token->value = $v;
	    $token->type  = IProgToken::IDENTIFIER;
	    return $token;
	}

	if(ctype_digit($char)) { //数値
	    if(ord($char) == ord('0')) {
		$this->next++;
		$this->cPos++;
		$v = $char;
		$char = $this->strs[$this->next];
		if(ord($char) == ord('.')) { // 実数
		} else if(ord($char) == ord('e') || ord($char) == ord('E')) { // 実数
		} else if(ord($char) == ord('x') || ord($char) == ord('X')) { // 16進数
		} else if(ctype_digit($char)) { // 8進数
		} else { // 整数の0
		    $token->value = $v;
		    $token->type = IProgToken::INTEGER_LITERAL;
		    return $token;
		}
	    } else { // 1..9 始まりは整数か実数
		$v = $this->getDigit();
		if($this->next < $this->length &&
			ord($this->strs[$this->next]) == ord('.')) { // 実数値
		    $v .= '.';
		    $this->next++;
		    $this->cPos++;
		    $adp = $this->getDigit();
		    if(strlen($adp) > 0) {
			$v .= $adp;
		    }
		    if($this->next < $this->length && 
			(ord($this->strs[$this->next]) == ord('e') ||
			ord($this->strs[$this->next]) == ord('E'))) { // 実数値(指数表現)
			$v .= $this->getExponentPart();
		    }
		    $token->value = $v;
		    $token->type  = IProgToken::FLOAT_LITERAL;
		    return $token;
		} else if($this->next < $this->length && 
			(ord($this->strs[$this->next]) == ord('e') ||
			ord($this->strs[$this->next]) == ord('E'))) { // 実数値(指数表現)
				
		    $v .= $this->getExponentPart();
		    $token->value = $v;
		    $token->type  = IProgToken::FLOAT_LITERAL;
		    return $token;
					
		} else { // 整数値で確定
		    $token->value = $v;
		    $token->type  = IProgToken::INTEGER_LITERAL;
		    return $token;
		}
	    }
	}

	if(ord($char) == ord('.') && $this->remain(2) &&
	    ctype_digit($this->strs[$this->next + 1])) {// .で始まる数値
	    $this->next++;
	    $this->cPos++;
	    $v = '.' . $this->getDigit();
	    if($this->remain() && (ord($this->strs[$this->next]) == ord('e') ||
		ord($this->strs[$this->next]) == ord('E'))) { // 実数値(指数表現)
		$v .= $this->getExponentPart();
	    }
	    $token->value = $v;
	    $token->type  = IProgToken::FLOAT_LITERAL;
	    return $token;
	}
		

	if(ord($char) == 0x22) { // "文字列"
	    $v = $this->getString();
	    if(is_null($v)) {  // これは無いはず
		$token->type = IProgToken::INVALID_CHARACTER;			
		$token->value= "\"";
		return $token;
	    }
	    if(ord(substr($v,-1)) == 0x22) { // 終端文字を確認
		$token->type = IProgToken::STRING_LITERAL;			
		$token->value= $v;
		return $token;
	    } else {
		$token->type = IProgToken::UNTERMINATED_STRING;			
		$token->value= $v;
		return $token;
	    }
	}

	if(ord($char) == 0x27) { // '文字'
	    $v = $this->getQCharacter();
	    if(is_null($v)) {  // これは無いはず
		$token->type = IProgToken::INVALID_CHARACTER;			
		$token->value= "\'";
		return $token;
	    }
	    if(ord(substr($v,-1)) == 0x27) { // 終端文字を確認
		$token->type = IProgToken::STRING_LITERAL;			// need check
		$token->value= $v;
		return $token;
	    } else {
		$token->type = IProgToken::UNTERMINATED_CHARACTER;			
		$token->value= $v;
		return $token;
	    }
	}

	if($this->remain(2) && ord($char) == ord('/')) {  // コメント
	    if(ord($this->strs[$this->next+1]) == ord('/')) { // 1行コメント
		$token->value = $this->getLineComment();
		$token->type  = IProgToken::LINE_COMMENT;
		return $token;
	    }
	    if(ord($this->strs[$this->next+1]) == ord('*')) { // ブロックコメント
		$token->value = $this->getBlockComment();
		if(strcmp(substr($token->value,-2),'*/') == 0) 
		     $token->type  = IProgToken::BLOCK_COMMENT;
		else $token->type  = IProgToken::UNTERMINATED_COMMENT;
		return $token;
	    }
	}

	// その他もろもろ
	switch(ord($char)) {
	// 区切り文字
	case 0x28: // (
	case 0x29: // )
	case 0x2c: // ,
	case 0x2e: // .
	case 0x3a: // :
	case 0x3b: // ;
	case 0x5b: // [
	case 0x5d: // ]
	case 0x7b: // {
	case 0x7d: // }
	    $this->next++; $this->cPos++;
	    $token->value = $char;
	    $token->type  = IProgToken::SEPARATOR;
	    return $token;

	// 演算子（3文字演算子）
	case 0x3c: // <
	case 0x3e: // >
	    if($this->next+2<$this->length) {
		$v = $char . $this->strs[$this->next+1] . $this->strs[$this->next+2];
		if(in_array($v,$ThreeCharOperator)) {
		    $token->value = $v;
		    $token->type  = IProgToken::OPERATOR;
		    $this->next += 3;
		    $this->cPos += 3;
		    return $token;
		}
	    } // 次に抜ける

	// 演算子（２文字演算子）
	case 0x21: // !
	case 0x25: // %
	case 0x26: // &
	case 0x2a: // *
	case 0x2b: // +
	case 0x2d: // -
	case 0x2f: // /
	case 0x3d: // =
	case 0x5e: // ^
	case 0x7c: // |
	case 0x7e: // ~
	    if($this->next+1<$this->length) {
		$v = $char . $this->strs[$this->next+1];
		if(in_array($v,$TwoCharOperator)) {
		    $token->value = $v;
		    $token->type  = IProgToken::OPERATOR;
		    $this->next += 2;
		    $this->cPos += 2;
		    return $token;
		}
	    } // 次に抜ける

	// 演算子（1文字演算子）
	case 0x3f: // ?
	    if(in_array($char,$OneCharOperator)) {
		$token->value = $char;
		$token->type  = IProgToken::OPERATOR;
		$this->next ++;
		$this->cPos ++;
		return $token;
	    }
	    $token->value = $char;
	    $token->type  = IProgToken::INVALID_CHARACTOR;
	    $this->next ++;
	    $this->cPos ++;
	    return $token;

	case 0x23: // # // １６進数で色コード
	    $this->next ++;
	    $this->cPos ++;
	    $v = $this->getHexNumber();
	    if(!is_null($v)) {
		$token->value = $char . $v;
		$token->type  = IProgToken::COLOR_LITERAL;
		return $token;
	    }
	    $token->value = $char;
	    $token->type  = IProgToken::INVALID_CHARACTER;
	    return $token;

	case 0x40: // @
	case 0x5c: // \
	case 0x60: // `
	case 0x24: // $
	default:
	    $this->next++; $this->cPos++;
	    $token->value = $char;
	    $token->type  = IProgToken::INVALID_CHARACTER;
	    return $token;
	}
	return null;
    }

    function getDigit() { // [0-9]+ を得る
	if($this->next >= $this->length) return null;
	$c = $this->strs[$this->next];
	$v = '';
	while(ctype_digit($c)) {
	    $v .= $c;
	    $this->next++;
	    $this->cPos++;
	    if($this->next < $this->length) $c = $this->strs[$this->next];
	    else break;
	}
	if(strlen($v) > 0) return $v;
	return null;
    }

    function getHexNumber() { // [0-9]+ を得る
	if($this->next >= $this->length) return null;
	$c = $this->strs[$this->next];
	$v = '';
	while(ctype_xdigit($c)) {
	    $v .= $c;
	    $this->next++;
	    $this->cPos++;
	    if($this->next < $this->length) $c = $this->strs[$this->next];
	    else break;
	}
	if(strlen($v) > 0) return $v;
	return null;
    }

    function getString() { // ".*" を得る(改行が混じった時の処理は？)
	if($this->next >= $this->length) return null;
	if(ord($this->strs[$this->next]) != 0x22) return null; // 先頭文字はダブルクォート
	$v = $this->strs[$this->next++];

	if($this->next < $this->length) $c = $this->strs[$this->next];
	while($this->next < $this->length && ord($c) != 0x22) {
	    $v .= $c;
	    if(ord($c) == 0x5c) {				// エスケープ文字
		$this->next++;
		$this->cPos++;
		if($this->next < $this->length) {
		    $c = $this->strs[$this->next];
		    if(ord($c) == 0x0a || ord($c) == 0x0d) { // 改行あり
			return $v;
		    } else $v .= $c;
		} else return $v;
	    } else if(ord($c) == 0x0a || ord($c) == 0x0d) { // 改行あり
		return $v;
	    }
	    $this->next++;
	    $this->cPos++;
	    if($this->next < $this->length) $c = $this->strs[$this->next];
	    else break;
	}
	if($this->next >= $this->length) return $v;
	if(ord($c) == 0x22) {
	    $v .= $c;
	    $this->next++;
	    $this->cPos++;
	    return $v;
	}
	return null; //　ここには来ない
    }

    function getQCharacter() { // ".*" を得る(改行が混じった時の処理は？)
	if($this->next >= $this->length) return null;
	if(ord($this->strs[$this->next]) != 0x27) return null; // 先頭文字はダブルクォート
	$v = $this->strs[$this->next++];

	if($this->next < $this->length) $c = $this->strs[$this->next];
	while($this->next < $this->length && ord($c) != 0x27) {
	    $v .= $c;
	    if(ord($c) == 0x5c) {				// エスケープ文字
		$this->next++;
		$this->cPos++;
		if($this->next < $this->length) {
		    $c = $this->strs[$this->next];
		    if(ord($c) == 0x0a || ord($c) == 0x0d) { // 改行あり
			return $v;
		    } else $v .= $c;
		} else return $v;
	    } else if(ord($c) == 0x0a || ord($c) == 0x0d) { // 改行あり
		return $v;
	    }
	    $this->next++;
	    $this->cPos++;
	    if($this->next < $this->length) $c = $this->strs[$this->next];
	    else break;
	}
	if($this->next >= $this->length) return $v;
	if(ord($c) == 0x27) {
	    $v .= $c;
	    $this->next++;
	    $this->cPos++;
	    return $v;
	}
	return null; //　ここには来ない
    }

    function nextPChar() { // 次の非スペース文字を得る。
	if($this->next >= $this->length) return null;
	$c = $this->strs[$this->next];
	while(($this->next < $this->length) && ctype_space($c)) {
	    $this->next++;
	    if(ord($c) == 0x0a || ord($c) == 0x0d) { // 改行文字
		$this->line++;
		$this->cPos = 0;
	    } else $this->cPos++;
	    if($this->next < $this->length) $c = $this->strs[$this->next];
	}
	if($this->next >= $this->length) return null;
	return $c;
    }

    function nextChar() { // 次の文字を得る。
	if($this->next >= $this->length) return null;
	$c = $this->peek();
	if(ord($c) == 0x0a || ord($c) == 0x0d) { // 改行文字
	    $this->line++;
	    $this->cPos = 0;
	} else $this->cPos++;
	$this->next++;
	return  $c;
    }

    function peek() { // 次の文字を得る。
	if($this->next >= $this->length) return null;
	return  $this->strs[$this->next];
    }

    function getExponentPart() {
	if(! $this->remain(2)) return null; // 最低2文字必要
	$c = $this->strs[$this->next];
	if(ord($c) != ord('e') && ord($c) != ord('E')) return null;
	$v = $c;
	$c = $this->strs[$this->next + 1];
	if(ctype_digit($c)) { // e2
	    $this->next++;
	    $this->cPos++;
	    $v .= $this->getDigit();
	    return $v;
	} else if(ord($c) == ord('+') || ord($c) == ord('-')) {
	    if(! $this->remain(3)) return null; // e{+|-}\d 最低3文字必要
	    if(ctype_digit( $this->strs[$this->next + 2] )) { // 成立
		$v .= $c;
		$this->next+=2;
		$this->cPos+=2;
		$v .= $this->getDigit();
		return $v;
	    }
	    return null; // 不成立
	} else return null;
    }

    function remain($n=1) {
	return $this->length >= $this->next + $n;
    }

    function getLineComment() {// 行コメント、ただし最初の２文字は確認済み
	$v = '';
	while($this->remain()) {
	    $c = $this->nextChar();
	    $v .= $c;
	    if(ord($c) == ord("\n") || ord($c) == ord("\r")) break;
	}
	return $v;  
    }

    function getBlockComment() {//blockコメント、ただし最初の２文字は確認済み
	$v = $this->nextChar();
	$v .= $this->nextChar();
	while($this->remain(2)) {
	    $c = $this->nextChar();
	    $v .= $c;
	    if(ord($c) == ord("*") || ord($this->peek()) == ord("/")) break;
	}
	$v .= $this->nextChar();
	return $v;  
    }
}

class TokenList {
    public $tokenList;
    public $next;

    function __construct($in =null) {
	if(! is_null($in)) {
	    $this->tokenList = array();

	    $tf = new IProgTokenFactory($in);

	    while($t = $tf->nextToken()) {
		$this->tokenList[] = $t; // 最後に加える
	    }
	}
    }

    function removeComment($flag=false) {
	global $TokenError;
	$result = array();
	if(is_null($this->tokenList)) return;

	foreach($this->tokenList as $token) {
	    if($token->type >= 100) { // 簡易的判断
		array_push($result,$token);
	    } else if($flag) {
		printf("ERROR:%s  %s in line %d, column %d.<br>\n",
			$token->value, $TokenError[- $token->type],
			$token->line + 1, $token->cPos + 1);
	    }
	}
	if(count($result) > 0) $this->tokenList = $result;
	return ;
    }

    function next() {
	if(count($this->tokenList)>0) 
	    return array_shift($this->tokenList);
	else return null;
    }

    function peek() {
	if(count($this->tokenList)>0) 
	    return $this->tokenList[0];
	else return null;
    }

    function getTokenErrors() {
	$message = "";
	foreach($this->tokenList as $token) {
	    if($token->isError()) {
		switch($token->type) {
		case IProgToken::UNKNOWN:
		    $message .= "Unknown error". atLinePos($token) . "<br>\n";
		    break;
		case IProgToken::INVALID_CHARACTER:
		    $message .= sprintf("ここにこの文字'%s'はおけません。",$token->value) .
					atLinePos($token) . "<br>\n";
		    break;
		case IProgToken::UNTERMINATED_STRING:
		    $message .= sprintf("文字列が閉じていません。 %s ",$token->value) .
					atLinePos($token) . "<br>\n";
		    break;
		case IProgToken::UNTERMINATED_CHARACTER:
		    $message .= sprintf("文字列が閉じていません。 %s ",$token->value) .
					atLinePos($token) . "<br>\n";
		    break;
		case IProgToken::UNTERMINATED_COMMENT:
		    $message .= sprintf("コメントの終端がありません '%s' ",$token->value) .
					atLinePos($token) . "<br>\n";
		    break;
		default:
		}
	    }
	}
	return $message;
    }

	function getChar($tokenList){
		array_column($tokenList, 'value');
		
		return $re;
	}

}


class IProgOperator {
    const NUMERIC = 0; // 演算結果のタイプ：数値
    const BOOLEAN = 1; // 演算結果：論理
    public $operator; // 演算子
    public $prece; // 優先順位
    public $assoc; // 結合規則
    public $type;

    function __construct($op,$pre,$ass,$ty=IProgOperator::NUMERIC) {
	$this->operator = $op;
	$this->prece = $pre;
	$this->assoc = $ass;
	$this->type = $ty;
    }

    function getPrece() {
	if(strcmp($this->assoc,"left")==0) return $this->prece;
	else return $this->prece + 1;
    }
}

$PrefixOperatorList = array();
$InfixOperatorList = array();
$PostfixOperatorList = array();

$PrefixOperatorList['-'] = new IProgOperator('-',2,'right');
$PrefixOperatorList['--'] = new IProgOperator('--',2,'right');
$PrefixOperatorList['++'] = new IProgOperator('++',2,'right');
$PrefixOperatorList['~'] = new IProgOperator('~',2,'right');
$PrefixOperatorList['!'] = new IProgOperator('!',2,'right');
$PrefixOperatorList['new'] = new IProgOperator('new',3,'right');

$InfixOperatorList['.'] = new IProgOperator('.',1,'left');
$InfixOperatorList['*'] = new IProgOperator('*',4,'left');
$InfixOperatorList['/'] = new IProgOperator('/',4,'left');
$InfixOperatorList['%'] = new IProgOperator('%',4,'left');
$InfixOperatorList['+'] = new IProgOperator('+',5,'left');
$InfixOperatorList['-'] = new IProgOperator('-',5,'left');
$InfixOperatorList['<<'] = new IProgOperator('<<',6,'left');
$InfixOperatorList['>>'] = new IProgOperator('>>',6,'left');
$InfixOperatorList['>>'] = new IProgOperator('>>',6,'left');
$InfixOperatorList['<'] = new IProgOperator('<',7,'left');
$InfixOperatorList['<='] = new IProgOperator('<=',7,'left');
$InfixOperatorList['>'] = new IProgOperator('>',7,'left');
$InfixOperatorList['>='] = new IProgOperator('>=',7,'left');
$InfixOperatorList['instanceof'] = new IProgOperator('instanceof',7,'left');
$InfixOperatorList['=='] = new IProgOperator('==',8,'left');
$InfixOperatorList['!='] = new IProgOperator('!=',8,'left');
$InfixOperatorList['&'] = new IProgOperator('&',9,'left');
$InfixOperatorList['^'] = new IProgOperator('^',10,'left');
$InfixOperatorList['|'] = new IProgOperator('|',11,'left');
$InfixOperatorList['&&'] = new IProgOperator('&&',12,'left');
$InfixOperatorList['||'] = new IProgOperator('||',13,'left');

$InfixOperatorList['*='] = new IProgOperator('*=',15,'right');
$InfixOperatorList['/='] = new IProgOperator('/=',15,'right');
$InfixOperatorList['%='] = new IProgOperator('%=',15,'right');
$InfixOperatorList['+='] = new IProgOperator('+=',15,'right');
$InfixOperatorList['-='] = new IProgOperator('-=',15,'right');
$InfixOperatorList['='] = new IProgOperator('=',15,'right');
$InfixOperatorList[','] = new IProgOperator(',',16,'left');

$PostfixOperatorList['++'] = new IProgOperator('++',1,'left');
$PostfixOperatorList['--'] = new IProgOperator('--',1,'left');

function atLinePos($t) {
    if(isset($t) && $t instanceof IProgToken) {
	return sprintf(" token(%s) at line %d, column %d.", $t->value,$t->line+1, $t->cPos+1);
    }
    return null;
}
// key word の種別

class IProgKeyword {
	public $keyword;
	function __construct($w) { $this->keyword = $w; }
}




$ModifierList = array(
	'public' => new IProgKeyword('public'),	
	'private' => new IProgKeyword('private'),	
	'protected' => new IProgKeyword('protected'),	
	'static' => new IProgKeyword('static'),	
	'final' => new IProgKeyword('final'),	
	'native' => new IProgKeyword('native'),	
	'synchronized' => new IProgKeyword('synchronized'),	
	'abstract' => new IProgKeyword('abstract'),	
	'threadsafe' => new IProgKeyword('threadsafe'),	
	'transient' => new IProgKeyword('transient'));	


$Type_specifierList = array(
	'void' => new IProgKeyword('void'),
	'boolean' => new IProgKeyword('boolean'),	
	'byte' => new IProgKeyword('byte'),	
	'char' => new IProgKeyword('char'),	
	'short' => new IProgKeyword('short'),	
	'int' => new IProgKeyword('int'),	
	'float' => new IProgKeyword('float'),
	'long' => new IProgKeyword('long'),
	'double' => new IProgKeyword('double'),	
	'String' => new IProgKeyword('String'),	// Processing default class
	'color' => new IProgKeyword('color'),	// Processing default class
	'PImage' => new IProgKeyword('PImage'),	// Processing default class
	'PFont' => new IProgKeyword('PFont'),	// Processing default class
	'PShape' => new IProgKeyword('PShape'),	// Processing default class
	'ArrayList' => new IProgKeyword('ArrayList'),	// Processing default class
	'Object' => new IProgKeyword('Object'),	// Processing default class
	// Too may processing default classes, omitted.
	'XML' => new IProgKeyword('XML'));	// Processing default class

1;
/*
$in = "line(.0, .1, .1e3 , .1e-123)@;\"aaaaa\" #abcdEF;";

$tf = new IProgTokenFactory($in);

while($t = $tf->nextToken()) {
	if($t->type <= 0) {
		printf("ERROR:%s  %s in line %d, column %d.<br>\n",
			$t->value, $TokenError[- $t->type], $t->line + 1, $t->cPos + 1);
	} else printf("%s,%d<br>\n",$t->value,$t->type);
}
*/
?> 
