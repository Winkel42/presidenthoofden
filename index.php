<?php
include 'lees_en_zet_koekjes.php';
include 'updaten.php';
include 'vernieuwscript.php';
$servername = "localhost";
$username = "root";
$dbname = "spellen";
$password = "";

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$aantal_eigen_veranderingen = 0;


//vind de naam van een speler uit de database
function get_name_from_player($speler){
	global $conn;
	$sql = "SELECT speler_naam FROM spelers WHERE speler_id=".$speler;
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()){
		return $row["speler_naam"];
	}
}

function teken_pagina($ingelogd, $ingelogde_speler){
	vernieuw_functie();
	global $conn;
	if($ingelogd){
		echo "<h3>u bent ingelogd als ".get_name_from_player($ingelogde_speler)."</h3>";
		$knop_naam = "log_uit".$ingelogde_speler;
		echo"<form method='post'><input class='menu_knop' type='submit' name=".$knop_naam." value='log uit'></form><br>";
		//geef links naar de kamers, en geef aan welke spelers daar zijn
		$namen_kamers = Array("park", "13gang", "noordkantine", "tuin");
		echo "<form class='tabelformulier'>";
		foreach(Array(0,1,2,3) as $kamer_id){
			echo "<p class='tabelformulier'>";
			$spelers = Array();
			$sql = "SELECT speler_id FROM kamers_spelers WHERE kamer_id = ".$kamer_id;
			$result = $conn->query($sql);
			if(!$result){
				echo $conn->error."<br>".$sql."<br>";
			}
			while($row = $result->fetch_assoc()){
				$spelers[] = $row['speler_id'];
			}
			echo "<input type='submit' class='tabelformulier' formaction='/".$namen_kamers[$kamer_id].".php' value='".$namen_kamers[$kamer_id]."'>
				<span class='tabelvakje'>
				Aanwezige spelers: ";
			foreach($spelers as $speler){
				if($speler != $spelers[0]){
					echo ", ";
				}
				echo get_name_from_player($speler);
			}
			if(!$spelers){
				echo "niemand";
			}
			echo "</span></p><br>";
		}	
		echo "</form>";
	}
	else{
		echo"
		<h3>Welkom bij presidenthoofden.tk.</h3><br><br>
		<h3>Je bent niet ingelogd.</h3><br><br>
		<form class='tabelformulier' method='post'>
		<p class='tabelformulier'>
		<label class= 'tabelformulier' for='log_in_naam'>Naam:</label>
		<input class= 'tabelformulier' type='text' id='log_in_naam' name='log_in_naam'><br>
		</p>
		<p class='tabelformulier'>
		<label class= 'tabelformulier' for='log_in_wachtwoord'>Wachtwoord:</label>
		<input class= 'tabelformulier' type='password' id='log_in_wachtwoord' name='log_in_wachtwoord'><br>
		</p>
		<p class='tabelformulier'>
		<input class= 'tabelformulier' type='submit' name='log_in' value='log in'>
		</p>
		</form>
		<form class= 'tabelformulier' method='post'>
		<p class= 'tabelformulier'>
		<label class= 'tabelformulier' for='registreer_naam'>Naam:</label>
		<input class= 'tabelformulier' type='text' id='registreer_naam' name='registreer_naam'><br>
		</p>
		<p class= 'tabelformulier'>
		<label class= 'tabelformulier' for='registeer_wachtwoord'>Wachtwoord (kies een wachtwoord dat je niet ergens anders gebruikt!!):</label>
		<input class= 'tabelformulier' type='password' id='registreer_wachtwoord' name='registreer_wachtwoord'><br>
		</p>
		<p class= 'tabelformulier'>
		<input class= 'tabelformulier' type='submit' name='registreer' value='registreer'>
		</p>
		</form>";
	}
}




function bekijk_log_in_en_registratie($ingelogd, $ingelogde_speler){
	global $conn;
	if($ingelogd){
		return controleer_log_uit();
	}
	else{
		$log_in_informatie = Array("ingelogd" => False);
		if(isset($_POST['registreer'])){
			//er probeert iemand zich te registereren
			if(!isset($_POST['registreer_naam'])){
				echo "geen naam ingevuld<br>";
				return $log_in_informatie;
			}
			$naam = filter_var($_POST['registreer_naam'], FILTER_SANITIZE_STRING);
			if(!preg_match("/^[a-zA-Z]*$/", $naam) || $naam == ''){
				echo "DEZE NAAM IS ILLEGAAL<br>";
				return $log_in_informatie;
			}
			//kijk of de naam al in de database zit
			$sql = "SELECT speler_naam FROM spelers WHERE speler_naam = '".$naam."'";
			$result = $conn->query($sql);
			if(!$result){
				echo $conn->error."<br>".$sql."<br>";
			}
			if($result->num_rows){
				echo "DEZE NAAM IS ILLEGAAL<br>";
				return $log_in_informatie;
			}
			//als we hier nog steeds zijn is de naam ok
			if(!isset($_POST['registreer_wachtwoord']) || $_POST['registreer_wachtwoord'] == ''){
				echo "Kies een wachtwoord<br>";
				return $log_in_informatie;
			}
			//bepaal een hash voor het wachtwoord
			$hash = password_hash($_POST['registreer_wachtwoord'], PASSWORD_DEFAULT);
			//vind het goede speler_id
			$sql = "SELECT max(speler_id) FROM spelers";
			$result = $conn->query($sql);
			if(!$result || $result->num_rows == 0){
				echo $conn->error."<br>".$sql."<br>";
			}
			while($row = $result->fetch_assoc()){
				$speler = 1 + $row['max(speler_id)'];
				if(!$speler){
					$speler = 1;
				}
			}
			$sql = "INSERT INTO spelers VALUES (?,?,?,0,10000)";
			$stmt = $conn->prepare($sql);
			if(!$stmt){
				echo $conn->error."<br>".$sql."<br>";
			}
			$stmt->bind_param("iss", $speler, $naam, $hash);
			if(!$stmt->execute()){
				echo $conn->error."<br>".$sql."<br>";
			}
			zet_koekjes($speler);
			$log_in_informatie = Array('ingelogd' => True, 'ingelogde_speler' => $speler);
			return $log_in_informatie;
		}
		if(isset($_POST['log_in'])){
			if(!isset($_POST['log_in_naam']) || !isset($_POST['log_in_wachtwoord'])){
				echo "vul naam en wachtwoord in";
				return $log_in_informatie;
			}
			$naam = filter_var($_POST['log_in_naam'], FILTER_SANITIZE_STRING);
			$sql = "SELECT speler_id, gehasht_wachtwoord FROM spelers WHERE speler_naam = ?";
			$stmt = $conn->prepare($sql);
			if(!$stmt){
				echo $conn->error."<br>".$sql."<br>";
			}
			$stmt->bind_param("s", $naam);
			$stmt->execute();
			$result = $stmt->get_result();
			if(!$result){
				echo $conn->error."<br>".$sql."<br>";
			}
			if(!($result->num_rows)){
				echo "naam of wachtwoord klopt niet<br>";
				return $log_in_informatie;
			}
			while($row = $result->fetch_assoc()){
				$hash = $row['gehasht_wachtwoord'];
				$speler = $row['speler_id'];
			}
			$ingelogd = password_verify($_POST['log_in_wachtwoord'], $hash);
			if($ingelogd){
				zet_koekjes($speler);
				$log_in_informatie = Array('ingelogd' => True, 'ingelogde_speler' => $speler);
				return $log_in_informatie;
			}
			else{
				echo "naam of wachtwoord klopt niet<br>";
				return $log_in_informatie;
			}
				
		}
	}
	return $log_in_informatie;	
}


function ververs(){
	global $conn;
	$log_in_informatie = bepaal_log_in_informatie();
	$ingelogd = $log_in_informatie['ingelogd'];
	if($ingelogd){
		$ingelogde_speler = $log_in_informatie['ingelogde_speler'];
	}
	else{
		$ingelogde_speler = Null;
	}
	$log_in_informatie = bekijk_log_in_en_registratie($ingelogd, $ingelogde_speler);
	$ingelogd = $log_in_informatie['ingelogd'];
	if($ingelogd){
		$ingelogde_speler = $log_in_informatie['ingelogde_speler'];
	}
	else{
		$ingelogde_speler = Null;
	}
	teken_pagina($ingelogd, $ingelogde_speler);
}

ververs();

?>
<style>
<?php include 'opmaak.css'; ?>
</style>