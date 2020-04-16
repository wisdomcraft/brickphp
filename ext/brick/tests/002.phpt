--TEST--
brick_test1() Basic test
--SKIPIF--
<?php
if (!extension_loaded('brick')) {
	echo 'skip';
}
?>
--FILE--
<?php
$ret = brick_test1();

var_dump($ret);
?>
--EXPECT--
The extension brick is loaded and working!
NULL
