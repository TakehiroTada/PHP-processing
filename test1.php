<html>
<body>
<pre>
<?php
$a = array(10,20,array(1,2,3,4,5));
$b = $a;
array_shift($b[2]);
array_shift($b[2]);
print_r($a);
print_r($b);
?>
</pre>
</body>
</html>
