<?php
include 'algemene_functies_en_klasses_voor_een_spel.php';
$kaart_id = $_GET['kaart_id'];
$kamer_id = $_GET['kamer_id'];
$servername = "localhost";
$username = "root";
$dbname = "spellen";
$password = "";
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
//bepaal het spel_id
if($kamer_id != 0 && $kamer_id != 1 && $kamer_id != 2 && $kamer_id != 3){
	$kamer_id = -2;
}
$sql = "SELECT spel_id FROM actieve_spellen WHERE kamer_id=".$kamer_id;
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
	$spel_id = $row['spel_id'];
}
if(!isset($spel_id)){
	$spel_id = -1;
}

$spel = maak_spel_vanuit_database($spel_id);

//kijk welke speler het is
$sql = "SELECT speler_id FROM spellen_spelers_kaarten WHERE spel_id=".$spel_id." AND kaart_id=".$kaart_id;
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
	$speler = $row['speler_id'];
}
if(!isset($speler)){
	$speler = -1;
}

//we veranderen de geklikte status van de kaart in de database
$sql = "UPDATE aangeklikte_kaarten SET aangeklikt = 1-aangeklikt WHERE spel_id=".$spel_id." AND kaart_id=".$kaart_id;
$conn->query($sql);
//nu kijken we of de aangeklikte kaarten een legale combinatie vormen
$hand = $spel->bepaal_hand($speler);
$kaarten = $hand->bepaal_aangeklikte_kaarten();
bepaal_globale_variabelen($spel);
if($stapel_diepte == 0){
	//de persoon kan spelen of niet spelen
	if(is_legaal($kaarten)){
		echo "speel_aan";
	}
	else{
		echo "speel_uit";
	}
}
else{
	//de persoon speelt of speelt niet
	if(is_legaal($kaarten)){
		echo "vernieuw";
	}
}
?>