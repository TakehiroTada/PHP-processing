<?php
include_once 'drillbase.php';
include_once 'drill1figure.php';
include_once 'iProgEval.php';
include_once 'processing.php';

if(!isset($seed)) {
    $seed = make_seed();
}

srand($seed);


?>
<pre>
<?php
//  $a = new PermutationFactory(3);
  $a =  makeFigureQuestion("i1710","size(200,200);",
		array("line(100,10,100,190);","ellipse(100,100,100,100);",
			"triangle(100,30,50,130,150,130);")); //range(50,100,5);
  foreach($a as $b) {
	print $b->getMethods()."\n";
  }
//  print_r($a);

//  print_r($a->getAll());
?>
</pre>
