--TEST--
Check if brick is loaded
--SKIPIF--
<?php
if (!extension_loaded('brick')) {
	echo 'skip';
}
?>
--FILE--
<?php
echo 'The extension "brick" is available';
?>
--EXPECT--
The extension "brick" is available
