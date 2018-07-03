<?php
  // Processing.php GDを使ったプロセッシングのエミュレータ
include_once 'iProgEval.php';

class Processing {
    public $canvas=null;
    public $environment;

    static private $const = array(
	'PI' => 3.14159265358979323846,
	'TWO_PI' => 6.28318530717958647693,
	'HALF_PI' => 1.57079632679489661923);

    static function makeEnvironment($v,$ty = 0) {
	$env = array();
	foreach($v as $key => $value) {
	    $env[$key] = new EResult($ty,$value);
	}
	return $env;
    }
    function __construct() {
	$this->environment['constant'] =
	    self::makeEnvironment(self::$const,EResult::DOUBLE);
    }
    function getResult($key) {
	if(is_string($key)) {
	    if(isset($this->environment['constant'][$key])) return $this->environment['constant'][$key];
	    if(isset($this->environment['valiable'][$key])) return $this->environment['valiable'][$key];
	}
	return null;
    }
    function getValue($key) {
	$a = $this->getResult($key);
	if($a instanceof EResult) return $a->value;
	return null;
    }
    function addValues($v) {
	foreach($v as $key => $value) {
	    if($value instanceof EResult) $val = $value;
	    else if(is_int($value)) $val = new EResult(EResult::INTEGER,$value);
	    else if(is_float($value)) $val = new EResult(EResult::FLOAT,$value);
	    else if(is_string($value)) $val = new EResult(EResult::STRING,$value);
	    else if(is_bool($value)) $val = new EResult(EResult::BOOLEAN,$value);
	    else $val = $value;
	    $this->environment['valiable'][$key] = $val;
	}
    }
}

class ProcessingCanvas {
    public $width;
    public $height;
    public $canvas;
    private $bkColor,$strokeColor,$fillColor;
    private $filled=true,$stroked=true;
    private $ttfont="/usr/share/fonts/alias/TrueType/Mincho-Medium.ttf";
    function __construct($w=100,$h=100) {
	$this->size($w,$h);
	$this->bkColor = imagecolorallocate($this->canvas,0xd3,0xd3,0xd3);
	$this->strokeColor = imagecolorallocate($this->canvas,0x00,0x00,0x00);
	$this->fillColor = imagecolorallocate($this->canvas,0xff,0xff,0xff);
	$this->background0();
    }

   function __destruct() {
       imagedestroy($this->canvas);
   }

    function size($w,$h) {
	$this->canvas = imagecreatetruecolor($w,$h);
	$this->width = $w;
	$this->height = $h;
	$this->background0();
    }
    function background0() {
	imagefill($this->canvas,0,0,$this->bkColor);
    }
    function background($r,$g,$b) {
	$this->bkColor = imagecolorallocate($this->canvas,$r,$g,$b);
    }
    function point($x,$y) {
	if($this->stroked) imageline($this->canvas,$x,$y,$x,$y,$this->strokeColor);
    }
    function line($x1,$y1,$x2,$y2) {
	if($this->stroked) imageline($this->canvas,$x1,$y1,$x2,$y2,$this->strokeColor);
    }
    function rect($x,$y,$w,$h) {
	if($this->filled) imagefilledrectangle($this->canvas,$x,$y,$w,$h,$this->fillColor);
	if($this->stroked) imagerectangle($this->canvas,$x,$y,$w,$h,$this->strokeColor);
    }
    function triangle($x1,$y1,$x2,$y2,$x3,$y3) {
	$r = array($x1,$y1,$x2,$y2,$x3,$y3);
	if($this->filled) imagefilledpolygon($this->canvas,$r,3,$this->fillColor);
	if($this->stroked) imagepolygon($this->canvas,$r,3,$this->strokeColor);
    }
    function quad($x1,$y1,$x2,$y2,$x3,$y3,$x4,$y4) {
	$r = array($x1,$y1,$x2,$y2,$x3,$y3,$x4,$y4);
	if($this->filled) imagefilledpolygon($this->canvas,$r,4,$this->fillColor);
	if($this->stroked) imagepolygon($this->canvas,$r,4,$this->strokeColor);
    }
    function ellipse($x,$y,$w,$h) {
	if($this->filled) imagefilledellipse($this->canvas,$x,$y,$w,$h,$this->fillColor);
	if($this->stroked) imageellipse($this->canvas,$x,$y,$w,$h,$this->strokeColor);
    }
    function arc($x,$y,$w,$h,$s,$e) {
	if($this->filled) imagefilledarc($this->canvas,$x,$y,$w,$h,
					rad2deg($s),rad2deg($e),$this->fillColor,IMG_ARC_PIE);
	if($this->stroked) imagearc($this->canvas,$x,$y,$w,$h,
					rad2deg($s),rad2deg($e),$this->strokeColor);
    }
    function text($text,$x,$y) {
//	if($this->filled) imagestring($this->canvas,$this->font,$x,$y-imagefontheight($this->font),
//				$text,$this->fillColor);
	if($this->filled) imagettftext($this->canvas,10,0,$x,$y,
				$this->fillColor,$this->ttfont,$text);
    }

    function fill($r,$g,$b) {
	$this->filled = true;
	$this->fillColor = imagecolorallocate($this->canvas,$r,$g,$b);
    }
    function stroke($r,$g,$b) {
	$this->stroked = true;
	$this->strokeColor = imagecolorallocate($this->canvas,$r,$g,$b);
    }
    function noFill() {
	$this->filled = false;
    }
    function noStroke() {
	$this->stroked = false;
    }


    function equals($targ) {
	if($targ instanceof ProcessingCanvas) $targ = $targ->canvas;
	if(is_null($targ)) return false;
	if(imagesx($this->canvas) != imagesx($targ) ||
		imagesy($this->canvas) != imagesy($targ)) return false;
	for($x = 0; $x < imagesx($this->canvas); $x++) {
	    for($y = 0; $y < imagesy($this->canvas); $y++) {
		if(imagecolorat($this->canvas) != imagecolorat($targ)) return false;
	    }
	}
	return true;
    }

    function out($file = null, $fmt="png") {
	switch($fmt) {
	case "png":  imagepng($this->canvas,$file); break;
	case "jpeg": imagejpeg($this->canvas,$file); break;
	default: break;
	}
    }
}

/*
$e = new Processing();
?>
<pre>
<?php print_r($e);
print "PI="; print $e->getValue('PI'); print "\n"; ?>
</pre>

<?php
// phpinfo(); exit(0);
/*
header('Content-Type: image/png');

$r = new Processing(300,300);

$r->arc(150,150,250,250,0,3.1416);

$r -> line(10,10,180,280);
$r->noFill();
$r->stroke(255,0,255);
// $r->size(300,300);
$r->triangle(100,10,10,150,191.3,150);
$r->quad(50,10,190,150,150,30,100,130);
$r->ellipse(200,200,100,80);
$r->point(200,200);
$r->fill(255,0,0);
$r->text("醤油林檎薔薇だよね",50,250);
$r -> out();
*/
?>
