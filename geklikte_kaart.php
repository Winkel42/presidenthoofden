<?php
include 'is_legaal.php';
$kaart_id = $_GET['kaart_id'];
$kamer_id = $_GET['kamer_id'];
$stapel_breedte= $_GET['stapel_breedte'];
$stapel_diepte = $_GET['stapel_diepte'];
$stapel_waarde = $_GET['stapel_waarde'];
$stapel_jokers = $_GET['stapel_jokers'];
$servername = "localhost";
$username = "root";
$dbname = "spellen";
$password = "";
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

//we veranderen de geklikte status van de kaart in de database
$sql = "UPDATE aangeklikte_kaarten A, actieve_spellen B SET aangeklikt = 1-aangeklikt WHERE A.spel_id=B.spel_id AND kamer_id=".$kamer_id." AND kaart_id=".$kaart_id;
$conn->query($sql);
//nu kijken we of de aangeklikte kaarten een legale combinatie vormen
$kaarten = Array();
$sql = "SELECT kaart_id FROM aangeklikte_kaarten A, actieve_spellen B WHERE A.spel_id=B.spel_id AND kamer_id=".$kamer_id." AND aangeklikt=1";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
	$kaarten[] = $row['kaart_id'];
}
if($stapel_diepte){
	//verander de speelknop
	if(is_legaal($kaarten)){
		echo "speel_aan";
	}
	else{
		echo "speel_uit";
	}
}
else{
	if(is_legaal($kaarten)){
		echo "vernieuw";
	}
}
	
?>