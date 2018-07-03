<HTML>
 <HEAD>
  <TITLE>Processing パーサのテストページ</TITLE>
 </HEAD>
 <BODY>
 <?php


// エラー出力する場合
ini_set( 'display_errors', 1 );

include_once "iProgToken.php";
include_once 'iProgParse.php';
include_once 'iProgEval.php';

function getTokenErrors($tl) {
	$message = "";
	foreach($tl->tokenList as $token) {
		if($token->isError()) {
			switch($token->type) {
			case IProgToken::UNKNOWN: $message .= "Unknown error". atLinePos($token) . "<br>\n"; break;
			case IProgToken::INVALID_CHARACTER:
				$message .= sprintf("Ivalid character '%s' ",$token->value) . atLinePos($token) . "<br>\n"; break;
			case IProgToken::UNTERMINATED_STRING:
				$message .= sprintf("Unterminated string %s ",$token->value) . atLinePos($token) . "<br>\n"; break;
			case IProgToken::UNTERMINATED_CHARACTER:
				$message .= sprintf("Unterminated character %s ",$token->value) . atLinePos($token) . "<br>\n"; break;
			case IProgToken::UNTERMINATED_COMMENT:
				$message .= sprintf("Unterminated comment '%s' ",$token->value) . atLinePos($token) . "<br>\n"; break;
			default:
			}
		}
	}
	return $message;
}

if(isset($_POST['text'])) {
   	$text = $_POST['text'];
	$view = htmlspecialchars($text);

	print <<<END
<hr>
次のテキストを構文解析します。
<pre>
$view
</pre>
<hr>
構文解析結果<br>
END;

	$tl = new TokenList($text);
	if(!$tl->tokenList) {
		printf("字句解析に失敗しました。たぶん入力が空か、ascii文字以外が使われています。<br>");
		goto next;
	}
	$message = $tl->getTokenErrors();

	if(strlen($message)>0) {
		print "$message<hr>\n";
		goto next;
	}
	$tl->removeComment();
	$result = parseStatement($tl->tokenList);

	$parse = $result[0];

	if(!$parse) {
		printf("構文エラーがありました。<br>");

		print("<pre>\n");
		print_r($tl);;
		print("</pre>\n");

		goto next;
	}

	if($parse->isError()) {
		printf("構文エラーがありました。<br>%s<br>", $parse->message);
		goto next;
	}

	print "<pre>\n";
	printResult($parse);
///	print_r($parse);
	print "</pre>\n";
	print "<hr>\n";
}

next: 1;
?>
構文解析したいjava/processingソースを以下に入力してください。ただし現在は
<ul>
<li> 以下の単文のみ対応しています。
　　<pre>
　　　式;
　　　; (空文)
　　</pre>
<li> 式は結構頑張ってますが、まだまだ不完全です。型のチェック、キャストはできていません。

</ul>
 <form action="iProgTest.php" method="post">
    入力: <textarea name=text></textarea><p>
    <input type="submit" value="実行" />
 </form>

<?php

if(isset($_POST['text2']) && $_POST['text2']) {
   	$text = $_POST['text2'];
	$view = htmlspecialchars($text);
   	$vari = $_POST['text4'];
	$view3 = htmlspecialchars($vari);
   	$coll = $_POST['text3'];
	$view2 = htmlspecialchars($coll);

	print <<<END
<hr>
次の式を評価します。
<pre>
変数: $view3
正解: $view2
入力: $view
</pre>
<hr>
結果<br>
END;

	$tl = new TokenList($text);

	$message = $tl->getTokenErrors();

	if(strlen($message)>0) {
		print $message;
		print "<hr>\n";
		goto next2;
	}
	$tl->removeComment();
	$result = parseExpression($tl->tokenList);

	$parse = $result[0];


	if(!$tl->tokenList) {
		printf("字句解析に失敗しました。たぶん入力が空か、ascii文字以外が使われています。<br>");
		goto next2;
	}

	if(!$parse) {
		printf("構文エラーがありました。<br>");

		print("<pre>\n");
		print_r($tl);;
		print("</pre>\n");


		goto next2;
	}

	if($parse->isError()) {
		printf("構文エラーがありました。<br>%s<br>", $parse->message);
		goto next2;
	}
	// ここに変数宣言
	if($vari) {
		$vl = new TokenList($vari);
//		print "<pre>\n"; print_r($vl); print "</pre>\n";
		$message = $vl->getTokenErrors();

		if(strlen($message)>0) {
			print $message;
			print "<hr>\n";
			goto next2;
		}
		$vl->removeComment();
		$v_result = parseVariableDeclaration($vl->tokenList);
		print "<pre>\n"; print_r($v_result); print "</pre>\n";

	}
	

	// kore tanjunnni test you
    $fl = getMethodResultByStrings($coll);

	if($parse->type == IProgParse::METHOD_EXPRESSION) {
		$val = iProgEvalGetMethodResult($parse);
	} else {
		$val = iProgEval($parse);
	}

	if(! isset($val)) {
		printf("評価できません。");
		goto next2;
	}

	print "<pre>\n";

	if($val instanceof MethodResult) {
		if(count($fl) <= 0) {
			print("メソッド評価時には正解も入力してください。");
			goto next2;
		}
		$eMess = "";
		while($fl) {
			$fn = array_shift($fl);
			if($fn->equal($val)) {
				print("一致しました。<br>\n");
				$eMess = null;
				break;
			} else {
				$eMess .= "$iProgError\n";
			}
		}
		if(isset($eMess)) {
			print("<pre>\n$eMess\n</pre>\n");
		}
	} else {
		switch($val->type) {
		case EResult::INTEGER:
			printf("整数値：%d\n",$val->value);
			break;
		case EResult::FLOAT:
			printf("実数値：%f\n",$val->value);
			break;

		case EResult::ERROR:
			printf("エラーが発生しました：%s\n",$val->value);
			break;

		}
	}
	//*/
	print "<hr>\n";
}

next2: 1;
?>
評価したい式を以下に入力してください。ただし現在は
<ul>
<li> 数式のみ対応しています。
</ul>
 <form action="iProgTest.php" method="post">
    変数: <textarea name=text4></textarea><p>
    正解: <textarea name=text3></textarea><p>
    入力: <textarea name=text2></textarea><p>
    <input type="submit" value="実行" />
 </form>

 </BODY>
</HTML>
