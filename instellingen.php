<?php
include 'maak_verbinding.php';
include 'lees_en_zet_koekjes.php';

function formulier_script($speler){
	global $conn;
	$sql = "SELECT autopas, laag_hoog, jokers, vijven FROM spelers WHERE speler_id=".$speler;
	$result = $conn->query($sql);
	if(!$result || $result->num_rows != 1){
		echo $conn->error."<br>".$sql."<br>";
	}
	$row = $result->fetch_assoc();
	$autopas = $row['autopas'];
	$laag_hoog = $row['laag_hoog'];
	$jokers = $row['jokers'];
	$vijven = $row['vijven'];
	
	echo "<script>
		window.onload = function(){
			var autopasKnop = document.getElementById('autopas');
			var laaghoogKnop = document.getElementById('laaghoog_'.concat('".$laag_hoog."'));
			var jokerKnop = document.getElementById('jokers_'.concat('".$jokers."'));
			var vijvenKnop = document.getElementById('vijven_'.concat('".$vijven."'));
			laaghoogKnop.checked = true;
			jokerKnop.checked = true;
			vijvenKnop.checked = true;";
	if($autopas){
		echo "autopasKnop.checked = true";
	}
	else{
		echo "autopasKnop.checked = false";
	}
	echo "};</script>";
}
	

function teken_formulier(){
	echo "<form method = 'post'>
			<input type='checkbox' id='autopas' name='autopas'>Autopas</input><br>
			Sorteer-voorkeur:<br>
			<input type='radio' id='laaghoog_0' name='laaghoog' value='0'>laag naar hoog</input>
			<input type='radio' id='laaghoog_1' name='laaghoog' value='1'>hoog naar laag</input><br>
			<input type='radio' id='jokers_0' name='jokers' value='0'>jokers laag</input>
			<input type='radio' id='jokers_1' name='jokers' value='1'>jokers hoog</input><br>
			<input type='radio' id='vijven_0' name='vijven' value='0'>5 op de normale plek</input>
			<input type='radio' id='vijven_1' name='vijven' value='1'>5 laag</input>
			<input type='radio' id='vijven_-1' name='vijven' value='-1'>5 hoog</input><br>
			<input type='submit' name='opslaan' class='menu_knop' value='Opslaan'/>
		</form>";
}


$log_in_informatie = bepaal_log_in_informatie();
$ingelogd = $log_in_informatie['ingelogd'];
if($ingelogd){
	$ingelogde_speler = $log_in_informatie['ingelogde_speler'];		
	formulier_script($ingelogde_speler);
	teken_formulier();
}
else{
	echo "Je bent niet ingelogd.<br>";
}

if($ingelogd && isset($_POST['opslaan'])){
	if(isset($_POST['autopas'])){
		$autopas = 1;
	}
	else{
		$autopas = 0;
	}
	$laag_hoog = $_POST['laaghoog'];
	$jokers = $_POST['jokers'];
	$vijven = $_POST['vijven'];
	$sql = "UPDATE spelers SET autopas = ".$autopas.", laag_hoog = ".$laag_hoog.", jokers = ".$jokers.", vijven = ".$vijven." WHERE speler_id=".$ingelogde_speler;
	if(!$conn->query($sql)){
		echo $conn->error."<br>".$sql."<br>";
	}
	echo "<script>window.location.href = window.location.href;</script>";
}


echo "<p><form><input type='submit' class='menu_knop' formaction='index.php'; value='Terug naar thuispagina'></form></p>";

?>
<style>
<?php include 'opmaak.css'; ?>
</style>