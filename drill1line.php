<?php
include_once 'drillbase.php';

include_once "iProgToken.php";
include_once 'iProgParse.php';
include_once 'iProgEval.php';
include_once 'processing.php';

class MethodDrill extends DrillData {
    static $width=400;
    static $height=400;

    public $vList=null;
    public $qTrees=null;
    public $sTrees=null;
    public $aStr;
    public $aStrs;
    private $eqWork=null; // 一致を見る時の作業領域
    private $proc=null; //environment=null; // 変数の値保持用
    function answer($arg,$vFlag=false) { // vFlagは変数がセットされている時に使用する
//	print"answer start\n";
	if($this->vList) {
	    $this->proc = new Processing();
	    $this->proc->addValues(makeEnvironment($this->vList)); // 変数に値を入れる関数
	}
	if(! $this->qTrees || $this->vList) {
	    $this->qTrees = array();
	    $this->sTrees = array();
	    foreach( $this->aStrs as $str) { 
		$p1 = new TokenList($str);
		$t1 = parseStatement($p1->tokenList);
		array_push($this->qTrees,$t1[0]);
		$e1 = $this->toSimpleForm($t1[0]);
		array_push($this->sTrees,$e1);
	    }
	}
	$at = tryParseStatement($arg);
	if(! is_array($at)) return false;
	if(!($at[0] instanceof IProgParse)) return false;
	if($at[0]->type != IProgParse::SIMPLE_STATEMENT) return false; 

	// 簡易的に一致を見る
	$sa = $this -> toSimpleForm($at[0]);

	foreach($this->sTrees as $t1) {
	    $this->eqWork = $t1;
	    if($this->tEqual($sa)) {
		if($vFlag || ! $this->vList) return true;
		return $this->answer($arg,true);
	    }
	}
	return false;
    }

    function toSimpleForm($arg) {
	if(!isset($arg)) return null;
	if($arg -> type == IProgParse::SIMPLE_STATEMENT) {
		$arg = $arg->tokenList[0];
	}
	if($arg -> type == IProgParse::METHOD_EXPRESSION) {
		$tl = $arg->tokenList;
		$name = $tl[0] -> value; // method name;
		$al = $tl[2];
		if(isset($al) && $al->type == IProgParse::ARGUMENT_LIST) {
			$arg = $this->toSimpleArgs($al);
			return array($name,$arg);
		} else if(isset($al) && $al->type == IProgToken::SEPERATOR) {
			return array($name);
		}
		return null;
	}
	return null;
    }

    function toSimpleArgs($arg) {
	if($arg instanceof IProgParse && $arg->type == IProgParse::ARGUMENT_LIST) {
		$arg = $arg->tokenList;
	}
	if(! is_array($arg)) return null;
	
	$aList = array();
	foreach($arg as $p) {
		if($p instanceof IProgParse && $p->isExpression()) {
			$e = iProgEvalExpression($p,$this->proc); //environment);
			if(isset($e) && $e->isNumber()) {
				array_push($aList,$e->value);
			}
		}
	}
	return $aList;
    }

    function tEqual($arg) {
	if(strcmp($this->eqWork[0],$arg[0]) !== 0) return false; // method name
	if(count($this->eqWork[1]) != count($arg[1])) return false; // 引数の数
	for($i=0; $i < count($this->eqWork[1]); $i++) {
		if($this->eqWork[1][$i] != $arg[1][$i]) return false;
	}
	return true;
    }

}

function mkMethodHLine($has_vali=false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(array('a','b','c'),array('x','y','l'),array('x1','y1','l1'),
				randomArray(alphabet(),3)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$l1 = $v[2];
	$d->vList = $v;
    } else {
	$x1 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
	$y1 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
	$l1 = randomArray(array(10,15,20,30,50,100,150,200))[0];
    }
    $d -> qStr = "座標($x1,$y1)から右に長さ$l1 の水平線を描く";
    $d -> aStr = "line($x1,$y1,".($has_vali?"$x1+$l1":($x1+$l1)).",$y1);";
    $d -> aStrs = array($d->aStr,"line(".($has_vali?"$x1+$l1":($x1+$l1)).",$y1,$x1,$y1);");
    return $d;
}

function mkMethodVLine($has_vali=false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(array('a','b','c'),array('x','y','l'),array('x1','y1','l1'),
				randomArray(alphabet(),3)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$l1 = $v[2];
	$d->vList = $v;
    } else {
	$x1 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
	$y1 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
	$l1 = randomArray(array(10,15,20,30,50,100,150,200))[0];
    }
    $d -> qStr = "座標($x1,$y1)から下に長さ$l1 の垂直線を描く";
    $d -> aStr = "line($x1,$y1,$x1,".($has_vali?"$y1+$l1":($y1+$l1)).");";
    $d -> aStrs = array($d->aStr,"line($x1,".($has_vali?"$y1+$l1":($y1+$l1)).",$x1,$y1);");
    return $d;
}

function mkMethodLine($has_vali=false) {
    $d = new MethodDrill();

    if($has_vali) {
	$v = randomArray(array(array('a','b','c','d'),array('x1','y1','x2','y2'),
				array('a1','a2','b1','b2'),array('px','py','qx','qy'),
				randomArray(alphabet(),4)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$x2 = $v[2];
	$y2 = $v[3];
	$d->vList = $v;
    } else {
	$x1 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
	$y1 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
	$x2 = randomArray(array(150,160,175,180,200,250,300))[0];
	$y2 = randomArray(array(150,160,175,180,200,250,300))[0];
    }
    $d -> qStr = "座標($x1,$y1)と($x2,$y2)を結ぶ直線を描く";
    $d -> aStr = "line($x1,$y1,$x2,$y2);";
    $d -> aStrs = array($d->aStr,"line($x2,$y2,$x1,$y1);");
    return $d;
}

function mkMethodTriangle($has_vali=false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(
		array('a','b','c','d','e','f'),array('x1','y1','x2','y2','x3','y3'),
		array('a1','a2','b1','b2','c1','c2'),array('px','py','qx','qy','rx','ry'),
		array('ax','ay','bx','by','cx','cy'),
				randomArray(alphabet(),6)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$x2 = $v[2];
	$y2 = $v[3];
	$x3 = $v[4];
	$y3 = $v[5];
	$d->vList = $v;
    } else {
	$x1 = randomArray(array(1,2,3,5,10,15,20,30,50,100,150,160,175,180,200,250,300))[0];
    	$y1 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
    	$x2 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
	$y2 = randomArray(array(150,160,175,180,200,250,300))[0];
	$x3 = randomArray(array(150,160,175,180,200,250,300))[0];
	$y3 = randomArray(array(150,160,175,180,200,250,300))[0];
    }

    $d -> qStr = "３つの頂点が($x1,$y1),($x2,$y2),($x3,$y3)の三角形を描く";
    $d -> aStr = "triangle($x1,$y1,$x2,$y2,$x3,$y3);";
    $d -> aStrs = array($d->aStr);
    $d -> aStrs[] = "triangle($x1,$y1,$x3,$y3,$x2,$y2);";
    $d -> aStrs[] = "triangle($x2,$y2,$x3,$y3,$x1,$y1);";
    $d -> aStrs[] = "triangle($x2,$y2,$x1,$y1,$x3,$y3);";
    $d -> aStrs[] = "triangle($x3,$y3,$x1,$y1,$x2,$y2);";
    $d -> aStrs[] = "triangle($x3,$y3,$x2,$y2,$x1,$y1);";
    return $d;
}

function mkMethodTriangle2($has_vali=false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(
		array('a','b','c','d'),array('x','y','w','h'),
		array('a1','a2','b1','b2'),array('px','py','w','h'),
				randomArray(alphabet(),4)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$w1 = $v[2];
	$h1 = $v[3];
	$x2 = "$x1-$w1/2";
	$y2 = "$y1+$h1";
	$x3 = "$x1+$w1/2";
	$y3 = "$y1+$h1";
	$d->vList = $v;
    } else {
	$x1 = randomArray(array(50,75,80,100,120,150,160,175,180,200))[0];
    	$y1 = randomArray(array(1,2,3,5,10,15,20,30,50))[0];
    	$w1 = randomArray(array(10,20,40,50,60,80,100))[0];
    	$h1 = randomArray(array(10,20,40,50,60,80,100))[0];
    	$x2 = $x1-($w1/2);
    	$y2 = $y1+$h1;
    	$x3 = $x1+($w1/2);
    	$y3 = $y1+$h1;
    }
    $d -> qStr = "頂点が($x1,$y1)で幅$w1,高さ$h1 の二等辺三角形を描く";
    $d -> aStr = "triangle($x1,$y1,$x2,$y2,$x3,$y3);";
    $d -> aStrs = array($d->aStr);
    $d -> aStrs[] = "triangle($x1,$y1,$x3,$y3,$x2,$y2);";
    $d -> aStrs[] = "triangle($x2,$y2,$x3,$y3,$x1,$y1);";
    $d -> aStrs[] = "triangle($x2,$y2,$x1,$y1,$x3,$y3);";
    $d -> aStrs[] = "triangle($x3,$y3,$x1,$y1,$x2,$y2);";
    $d -> aStrs[] = "triangle($x3,$y3,$x2,$y2,$x1,$y1);";
    return $d;
}

function mkMethodRect($has_vali = false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(
		array('a','b','c','d'),array('x','y','w','h'),
		array('a1','a2','b1','b2'),array('px','py','w','h'),
				randomArray(alphabet(),4)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$w1 = $v[2];
	$h1 = $v[3];
	$d -> vList = $v;
    } else {
	$x1 = randomArray(array(1,2,3,5,10,15,20,25,30,50,60,80,75,100))[0];
	$y1 = randomArray(array(1,2,3,5,10,15,20,25,30,50,60,80,75,100))[0];
	$w1 = randomArray(array(10,20,40,50,60,80,100))[0];
	$h1 = randomArray(array(10,20,40,50,60,80,100))[0];
    }

    $d -> qStr = "左上頂点が($x1,$y1)で幅$w1,高さ$h1 の長方形を描く";
    $d -> aStr = "rect($x1,$y1,$w1,$h1);";
    $d -> aStrs= array($d->aStr);
    return $d;
}

function mkMethodSquare($has_vali = false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(
		array('a','b','c'),array('x','y','l'),array('x1','y1','l1'),
		array('a1','a2','a3'),array('px','py','l'),
				randomArray(alphabet(),3)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$l1 = $v[2];
	$d -> vList = $v;
    } else {
	$x1 = randomArray(array(1,2,3,5,10,15,20,25,30,50,60,80,75,100))[0];
	$y1 = randomArray(array(1,2,3,5,10,15,20,25,30,50,60,80,75,100))[0];
	$l1 = randomArray(array(10,20,40,50,60,80,100))[0];
    }

    $d -> qStr = "左上頂点が($x1,$y1)で一辺の長さが$l1 の正方形を描く";
    $d -> aStr = "rect($x1,$y1,$l1,$l1);";
    $d -> aStrs= array($d->aStr);
    return $d;
}

function mkMethodQuad($has_vali=false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(
		array('a','b','c','d','e','f','g','h'),
		array('x1','y1','x2','y2','x3','y3','x4','y4'),
		array('a1','a2','a3','a4','a5','a6','a7','a8'),
		array('px','py','qx','qy','rx','ry','sx','sy'),
		array('ax','ay','bx','by','cx','cy','dx','dy'),
				randomArray(alphabet(),8)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$x2 = $v[2];
	$y2 = $v[3];
	$x3 = $v[4];
	$y3 = $v[5];
	$x4 = $v[6];
	$y4 = $v[7];
	$d -> vList = $v;
    } else {
	$x1 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
    	$y1 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];

    	$x2 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
    	$y2 = randomArray(array(150,160,175,180,200,250,300))[0];

    	$x3 = randomArray(array(150,160,175,180,200,250,300))[0];
    	$y3 = randomArray(array(150,160,175,180,200,250,300))[0];

	$x4 = randomArray(array(150,160,175,180,200,250,300))[0];
    	$y4 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];
    }

    $d -> qStr = "頂点が($x1,$y1),($x2,$y2),($x3,$y3),($x4,$y4)の四角形を描く";
    $d -> aStr = "quad($x1,$y1,$x2,$y2,$x3,$y3,$x4,$y4);";
    $d -> aStrs = array($d->aStr);
    $d -> aStrs[] = "quad($x2,$y2,$x3,$y3,$x4,$y4,$x1,$y1);";
    $d -> aStrs[] = "quad($x3,$y3,$x4,$y4,$x1,$y1,$x2,$y2);";
    $d -> aStrs[] = "quad($x4,$y4,$x1,$y1,$x2,$y2,$x3,$y3);";

    $d -> aStrs[] = "quad($x4,$y4,$x3,$y3,$x2,$y2,$x1,$y1);";
    $d -> aStrs[] = "quad($x3,$y3,$x2,$y2,$x1,$y1,$x4,$y4);";
    $d -> aStrs[] = "quad($x2,$y2,$x1,$y1,$x4,$y4,$x3,$y3);";
    $d -> aStrs[] = "quad($x1,$y1,$x4,$y4,$x3,$y3,$x2,$y2);";
    return $d;
}

function mkMethodDiamond($has_vali=false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(
		array('a','b','c','d'),array('x','y','w','h'),
		array('a1','a2','b1','b2'),array('px','py','w','h'),array('p1','p2','q','r'),
				randomArray(alphabet(),4)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$w1 = $v[2];
	$h1 = $v[3];
	$x2 = "$x1-$w1/2";
	$y2 = "$y1+$h1/2";
	$x3 = $x1;
	$y3 = "$y1+$h1";
	$x4 = "$x1+$w1/2";
	$y4 = "$y1+$h1/2";
	$d->vList = $v;
    } else {
	$x1 = randomArray(array(50,60,70,75,80,90,100))[0];
    	$y1 = randomArray(array(1,2,3,5,10,15,20,30,50,100))[0];

	$w1 = randomArray(range(10,100,10))[0];
    	$h1 = randomArray(range(10,100,10))[0];

	$x2 = $x1 - $w1/2;
    	$y2 = $y1 + $h1/2;

	$x3 = $x1;
    	$y3 = $y1 + $h1;

    	$x4 = $x1 + $w1/2;
    	$y4 = $y2;
    }

    $d -> qStr = "頂点が($x1,$y1)で幅$w1, 高さ$h1 のひし形を描く";
    $d -> aStr = "quad($x1,$y1,$x2,$y2,$x3,$y3,$x4,$y4);";
    $d -> aStrs = array($d->aStr);
    $d -> aStrs[] = "quad($x2,$y2,$x3,$y3,$x4,$y4,$x1,$y1);";
    $d -> aStrs[] = "quad($x3,$y3,$x4,$y4,$x1,$y1,$x2,$y2);";
    $d -> aStrs[] = "quad($x4,$y4,$x1,$y1,$x2,$y2,$x3,$y3);";

    $d -> aStrs[] = "quad($x4,$y4,$x3,$y3,$x2,$y2,$x1,$y1);";
    $d -> aStrs[] = "quad($x3,$y3,$x2,$y2,$x1,$y1,$x4,$y4);";
    $d -> aStrs[] = "quad($x2,$y2,$x1,$y1,$x4,$y4,$x3,$y3);";
    $d -> aStrs[] = "quad($x1,$y1,$x4,$y4,$x3,$y3,$x2,$y2);";
    return $d;
}

function mkMethodCircle($has_vali=false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(
		array('a','b','c'),array('x','y','d'),array('x1','y1','d1'),
		array('a1','a2','a3'),array('px','py','phi'),array('p','q','r'),
				randomArray(alphabet(),3)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$d1 = $v[2];
	$d -> vList = $v;
    } else {
	$x1 = randomArray(range(50,100,5))[0];
    	$y1 = randomArray(range(50,100,5))[0];

	$r1  = randomArray(range(5,50,5))[0];
    	$d1 = $r1 * 2;
    }
    $d -> qStr = "中心が($x1,$y1)で、直径$d1 の円を描く";
    $d -> aStr = "ellipse($x1,$y1,$d1,$d1);";
    $d -> aStrs = array($d->aStr);
    return $d;
}

function mkMethodCircleR($has_vali=false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(
		array('a','b','c'),array('x','y','r'),array('x1','y1','r1'),
		array('a1','a2','a3'),array('px','py','rad'),array('p','q','r'),
				randomArray(alphabet(),3)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$r1 = $v[2];
	$d1 = "$r1*2";
	$d -> vList = $v;
    } else {
	$x1 = randomArray(range(50,100,5))[0];
    	$y1 = randomArray(range(50,100,5))[0];

	$r1  = randomArray(range(5,50,5))[0];
    	$d1 = $r1 * 2;
    }
    $d -> qStr = "中心が($x1,$y1)で、半径$r1 の円を描く";
    $d -> aStr = "ellipse($x1,$y1,$d1,$d1);";
    $d -> aStrs = array($d->aStr);
    return $d;
}

function mkMethodEllipse($has_vali=false) {
    $d = new MethodDrill();
    if($has_vali) {
	$v = randomArray(array(
		array('a','b','c','d'),array('x','y','w','h'),array('x1','y1','d1','d2'),
		array('a1','a2','a3','a4'),array('px','py','w','h'),array('p','q','d1','d2'),
				randomArray(alphabet(),4)))[0];
	$x1 = $v[0];
	$y1 = $v[1];
	$d1 = $v[2];
	$d2 = $v[3];
	$d -> vList = $v;
    } else {
	$x1 = randomArray(range(50,100,5))[0];
    	$y1 = randomArray(range(50,100,5))[0];

	$r1  = randomArray(range(5,50,5))[0];
    	$d1 = $r1 * 2;

    	$r2  = randomArray(range(5,50,5))[0];
    	$d2 = $r2 * 2;
    }
    $d -> qStr = "中心が($x1,$y1)で、横方向直径が$d1, 縦方向直径が$d2 のだ円を描く";
    $d -> aStr = "ellipse($x1,$y1,$d1,$d2);";
    $d -> aStrs = array($d->aStr);
    return $d;
}

// 簡易的変数値入れ
function makeEnvironment($vList) {
    $env = array();
    $aList = randomArray(range(4,200,4),count($vList));
    for($i=0; $i<count($vList); $i++) {
	$env[$vList[$i]] = $aList[$i];
    }
    return $env;
}



?>
