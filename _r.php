<?php
header('Content-Type: application/json');
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
