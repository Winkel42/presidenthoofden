<?php

function zet_koekjes($speler){
	global $conn;
	$houdbaarheid = 86400*30; //de koekjes zijn 30 dagen houdbaar
	setcookie("ingelogde_speler", $speler, time()+$houdbaarheid, "/"); //we zetten een koekje voor de naam
	//we kijken of er een token in de database zit
	$sql = "SELECT token FROM spelers WHERE speler_id = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("i", $speler);
	$stmt->execute();
	$result = $stmt->get_result();
	if(!$result){
		echo $conn->error."<br>".$sql."<br>";
	}
	while($row = $result->fetch_assoc()){
		$token = $row['token'];
	}
	if($token == 0){
		$token = bin2hex(random_bytes(100));
		$sql = "UPDATE spelers SET token='".$token."' WHERE speler_id = ?";
		$stmt = $conn->prepare($sql);
		if(!$stmt){
			echo $conn->error."<br>".$sql."<br>";
		}
		$stmt->bind_param("i", $speler);
		if(!$stmt->execute()){
			echo $conn->error."<br>".$sql."<br>";
		}
	}
	setcookie("token", $token, time()+$houdbaarheid,"/"); //we zetten een cookie voor het authenticatietoken
	$_POST = Array();
}

function bepaal_log_in_informatie(){
	global $conn;
	$ingelogd = False;
	if(isset($_COOKIE["ingelogde_speler"]) && isset($_COOKIE["token"])){
		$speler = $_COOKIE["ingelogde_speler"];
		$token = $_COOKIE["token"];
		$sql = "SELECT token FROM spelers WHERE speler_id = ?";
		$stmt = $conn->prepare($sql);
		if(!$stmt){
			echo $conn->error."<br>".$sql."<br>";
		}
		$stmt->bind_param("i", $speler);
		$stmt->execute();
		$result = $stmt->get_result();
		if(!$result){
			echo $conn->error."<br>".$sql."<br>";
		}
		while($row = $result->fetch_assoc()){
			$goede_token = $row['token'];
		}
		if(isset($goede_token) && $token == $goede_token && $token != 0){
			$ingelogd = True;
		}
	}
	if($ingelogd){
		$log_in_informatie = Array("ingelogd" => True, "ingelogde_speler" => $speler);
	}
	else{
		$log_in_informatie = Array("ingelogd" => False);
	}
	return $log_in_informatie;
}



function controleer_log_uit(){
	global $conn;
	$log_in_informatie = bepaal_log_in_informatie();
	$ingelogd = $log_in_informatie['ingelogd'];
	if($ingelogd){
		$ingelogde_speler = $log_in_informatie['ingelogde_speler'];
		$knop_naam = "log_uit".$ingelogde_speler;
		if(isset($_POST[$knop_naam])){
			//we gaan de token gewoon weer op 0 zetten
			$sql = "UPDATE spelers SET token=0 WHERE speler_id=".$ingelogde_speler;
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			//we moeten ook de koekjes weghalen
			setcookie("gebruikersnaam", "", \time()-30);
			setcookie("token", "", \time()-30);
			$_POST = Array();
			$log_in_informatie = Array("ingelogd" => False);
		}
	}
	return $log_in_informatie;
}
?>