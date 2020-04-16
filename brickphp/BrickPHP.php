<?php
header('Content-type:text/html;charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('html_errors', 1);

define('BRICKPHP_VERSION', 'v1.2_20170504');

defined('APP_PATH')     or define('APP_PATH', '../application/');
defined('LIBRARY_PATH') or define('LIBRARY_PATH', '../brickphp/');

require(LIBRARY_PATH.'common/common.function.php');
require(LIBRARY_PATH.'controller/controller.class.php');
require(LIBRARY_PATH.'session/session.class.php');
require(LIBRARY_PATH.'mysqli/mysqli.class.php');
require(LIBRARY_PATH.'mysqli/mysqli_stmt.class.php');
require(LIBRARY_PATH.'route/route.class.php');
require(LIBRARY_PATH.'mo/mo.class.php');
require(LIBRARY_PATH.'mo/mo.function.php');

$OBJECT_START = new brick\route;
$OBJECT_START->index();


