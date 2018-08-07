<?php

//////////////////////////////////////////////////////////////////////////////////////
register_shutdown_function(function() {
	global $_TMP_sqlite;
	global $_TMP_sqlite_id;
	$_TMP_output = ob_get_clean();
	echo $_TMP_output;
	$_TMP_sqlite
		->prepare('UPDATE `log` SET `resh`=:resh,`res`=:res,`err`=:err WHERE `tid`=:tid')
		->execute([
			'tid' => $_TMP_sqlite_id
			,'res' => $_TMP_output
			,'err' =>  json_encode(error_get_last(),JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES)
			,'resh' => json_encode(headers_list(),JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES)
		])
	;
});
session_start();

if (!defined('TMP_SQLOGFILE')) define('TMP_SQLOGFILE', $_SERVER['DOCUMENT_ROOT'].'/../private/logs/access.log.db');
if (!file_exists(dirname(TMP_SQLOGFILE)) && !is_dir(dirname(TMP_SQLOGFILE))) mkdir(dirname(TMP_SQLOGFILE));
$_TMP_sqlite = new PDO('sqlite:'.TMP_SQLOGFILE);
$_TMP_sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
	$_TMP_sqlite->exec('SELECT * FROM `log` LIMIT 1;');
} catch(Exception $e) {
	$_TMP_sqlite->exec('CREATE TABLE `log` (
	`tid`	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT
	,`date`	TEXT
	,`at`	REAL
	,`ip`	TEXT
	,`xip`	TEXT
	,`host`	TEXT
	,`path`	TEXT
	,`query`	TEXT
	,`reqh`	TEXT
	,`req`	TEXT
	,`resh`	TEXT
	,`res`	TEXT
	,`err`	TEXT
	,`sessid`	TEXT
	,`ua`	TEXT
);
');
}
$_TMP_input = file_get_contents("php://input");
if ($_TMP_input) {
	$_TMP_input_d = json_decode($_TMP_input,true);
	if ($_TMP_input_d!==null) $_TMP_input = json_encode($_TMP_input_d,JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
} elseif($_POST) {
	$_TMP_input = json_encode($_POST,JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
}
$_TMP_sqlite
	->prepare('INSERT INTO `log`(`date`,`at`,`ip`,`xip`,`ua`,`host`,`path`,`query`,`sessid`,`reqh`,`req`) VALUES (:date,:at,:ip,:xip,:ua,:host,:path,:query,:sessid,:reqh,:req)')
	->execute([
		'at' => $_SERVER['REQUEST_TIME_FLOAT']
		,'date'=>date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME_FLOAT'])
		,'ip' => $_SERVER['REMOTE_ADDR']
		,'xip' => ((isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != $_SERVER['REMOTE_ADDR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '' )
		,'ua' => ((isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : null )
		,'host' => (($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'])
		,'path' => strtok($_SERVER["REQUEST_URI"],'?')
		,'query' => $_SERVER['QUERY_STRING']
		,'sessid' => session_id()
		,'req' => $_TMP_input
		,'reqh' => json_encode(getallheaders(),JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES)
	])
;
$_TMP_sqlite_id = $_TMP_sqlite->lastInsertId();
ob_start();
//////////////////////////////////////////////////////////////////////////////////////
