<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
	$key = "1f7e6858-ff54-4190-a994-b477056818fa";
	$targetJsonFile = './data.json';

	if(isset($_SERVER['HTTP_X_KEY'])){
		$key_sent = $_SERVER['HTTP_X_KEY'];
		if($key_sent == $key){
			$data = json_decode(file_get_contents('php://input'), true);
			//*
			$existingData = file_get_contents($targetJsonFile);
			$tempArray = json_decode($existingData);
			array_push($tempArray, $data['values']);
			$jsonData = json_encode($tempArray);
			file_put_contents($targetJsonFile, $jsonData);
			//*/
			$results = array('success'=>TRUE);
		}
		else{
			$results = array('success'=>FALSE, 'error'=>'invalid key');
		}
	}
	else{
		$results = array('success'=>FALSE, 'error'=>'missing key');
	}
}
else{
	$results = array('success'=>FALSE, 'error'=>'invalid method');
}

	// answer
	echo json_encode($results);
?>