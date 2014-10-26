<?php

header('Content-Type: application/json');

function exception_handler($exception) {
	http_response_code(400);
	echo json_encode(array('error'=>'(╥﹏╥)  '.$exception->getMessage()));
}
set_exception_handler('exception_handler');
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

if(!isset($_GET['token'])||$_GET['token']!=='##token##') throw new \RuntimeException('Permission denied');
$batch = json_decode(file_get_contents('php://input'), true);
$here = getcwd();
$index = array();
foreach($batch['files'] as $f){
	if(file_exists($here.$f)){		
		$index[$f]= array(
			"time" => filemtime($here.$f),
			"crc" => md5(file_get_contents($here.$f)),
			"size" => filesize($here.$f) 
		);
	}		
}
echo(json_encode(array('index'=>$index)));
