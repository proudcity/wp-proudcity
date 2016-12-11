<?php

	$data = [
		"build" => getenv('CBUILDNUM'),
		"branch" => getenv('CBRANCH'),
	];
	header("Content-type: application/json; charset=utf-8");
	echo json_encode($data, JSON_PRETTY_PRINT);

?>
