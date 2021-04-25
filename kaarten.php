<?php

include 'lees_en_zet_koekjes.php';
include 'updaten.php';
include 'vernieuwscript.php';
include 'is_legaal.php';
//we maken verbinding met de database
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

function teken_algemene_dingen_voor_spel(){
	global $kamer_id, $stapel_breedte, $stapel_diepte, $stapel_waarde, $stapel_jokers;
	vernieuw_functie($kamer_id);
	echo "<script>
		function klikOp(kaart_id){
			if(kaart_id == 8){//klaver 8 kan je niet op klikken
				return;
			}
			var kaartElement = document.getElementById('kaart'+kaart_id);
			kaartElement.classList.toggle('geselecteerd');
			var xmlhttp = new XMLHttpRequest();
			xmlhttp.open('POST',
				'geklikte_kaart.php?kaart_id='+kaart_id+'&kamer_id=".$kamer_id."&stapel_breedte=".$stapel_breedte."&stapel_diepte=".$stapel_diepte."&stapel_waarde=".$stapel_waarde."&stapel_jokers=".$stapel_jokers."'
				, true);
			xmlhttp.send();
			xmlhttp.onreadystatechange = function(){
				if(this.readyState == 4 && this.status == 200){
					if(this.responseText == 'vernieuw'){						
						window.location.href = window.location.href;
					}
					if(this.responseText == 'speel_aan'){
						window.location.href = window.location.href;
					}
				}
			}
		}
		</script>";
}

function teken_algemene_dingen_na_spel(){
	global $conn, $kamer_id;
	//we gaan een knop tekenen om een kamer in te gaan of te verlaten
	$log_in_informatie = bepaal_log_in_informatie();
	$ingelogd = $log_in_informatie['ingelogd'];
	if($ingelogd){
		$ingelogde_speler = $log_in_informatie['ingelogde_speler'];
		//we moeten kijken of de speler in de kamer zit
		$sql = "SELECT * FROM kamers_spelers WHERE speler_id = ".$ingelogde_speler." AND kamer_id = ".$kamer_id;
		$result = $conn->query($sql);
		if(!$result){
			echo $conn->error."<br>".$sql."<br>";
		}
		$in_spel = ($result->num_rows >= 1);
	}
	if($ingelogd && !$in_spel){
		$knop_naam = "Schuif_aan";
		$knop_tekst = "Schuif aan";
		$disable = '';
	}
	if($ingelogd && $in_spel){
		$knop_naam = "Ga_weg";
		$knop_tekst = "Ga weg (na dit potje)";
		$disable = '';
	}
	if(!$ingelogd){
		$knop_naam = "";
		$knop_tekst = "Niet ingelogd";
		$disable = 'uitgezet';
	}
	
	
	
	echo "<p>";
	if($ingelogd){
		echo "<h3>U bent ingelogd als ".get_name_from_player($ingelogde_speler).".</h3>";
		$knop_naam_log_uit = "log_uit".$ingelogde_speler;
		echo"<form method='post'><input class='menu_knop' type='submit' name=".$knop_naam_log_uit." value='Log uit'>";
	}
	else{
		echo "<form method='post'>";
	}
	echo "<input type='submit'; class='menu_knop ".$disable."' name='".$knop_naam."'; value='".$knop_tekst."'>";
	echo "<input type='submit' class='menu_knop' formaction='index.php'; value='Terug naar thuispagina'></form>";
	echo "</p>";
}





function is_het_een_ontploffing($kaarten){
	$aantal_jokers = 0;
	foreach($kaarten as $kaart){
		$waarde = $kaart->waarde;
		if($waarde == '10'){
			return True;
		}
		if($waarde =='joker'){
			$aantal_jokers++;
		}
	}
	if(count($kaarten)-$aantal_jokers >= 4){
		return True;
	}
	return False;
}


function bepaal_kleur($kaart_id){
	//ze staan op volgorde van laag naar hoog
	//de klaver 8 is nummer 8
	if($kaart_id<8){
		return 'nvt';
	}
	if($kaart_id>=32 && $kaart_id<=34){
		return 'klaver';
	}
	if($kaart_id==35){
		return 'ruiten';
	}
	switch($kaart_id%4){
		case 0:
			return 'klaver';
			break;
		case 1:
			return 'schoppen';
			break;
		case 2:
			return 'harten';
			break;
		case 3:
			return 'ruiten';
			break;
	}
}



class Kaart {
	public $spel_id;
	public $kaart_id;
	public $kleur;
	public $waarde;
	private $geklikt;
	function __construct($spel_id, $kaart_id){
		global $conn;
		$this->spel_id = $spel_id;
		$this->kaart_id = $kaart_id;
		$this->kleur = bepaal_kleur($kaart_id);
		$this->waarde = bepaal_waarde($kaart_id);
		$this->geklikt = 0;
		//kijk of de kaart al aangeklikt was
		$sql = "SELECT aangeklikt FROM aangeklikte_kaarten WHERE spel_id=".$spel_id." AND kaart_id=".$kaart_id." AND aangeklikt=1";
		$result = $conn->query($sql);
		if(!$result){
			echo $conn->error."<br>".$sql."<br>";
		}
		if($result && $result->num_rows>0){
			$this->geklikt = 1;
		}
	}
	function draw($klikbare_kaarten, $zichtbaar, $in_stapel){
		if($zichtbaar){
			if($this->waarde == "joker"){
				$kaart_tekst = "joker";
			}
			else{
				$kaart_tekst = $this->kleur.$this->waarde;
			}
		}
		else{
			$kaart_tekst = "achterkant";
		}
		if($klikbare_kaarten && $zichtbaar){
			$kaart_naam = "kaart".$this->kaart_id;
			if($this->geklikt){
				$klasse = "kaart geselecteerd";
			}
			else{
				$klasse = "kaart";
			}
			echo "<input class='".$klasse."' onclick=klikOp(".$this->kaart_id.") type='image' id='".$kaart_naam."' src='/afbeeldingen/".$kaart_tekst.".png' alt='".$kaart_tekst."'/>";
		}
		else{
			if($zichtbaar){
				$klasse = "kaart onklikbaar";
			}
			else{
				$klasse = "kaart onzichtbaar";
			}
			if($in_stapel){
				$klasse = "kaart in_stapel";
			}
			echo "<img class='".$klasse."' src='/afbeeldingen/".$kaart_tekst.".png' alt='".$kaart_tekst."'/>";
		}
	}
}


//een hand is wat één speler in één spel vastheeft
class Hand {
	public $kaarten;
	public $speler;
	public $spel;
	function __construct($speler, $spel){
		$this->speler = $speler;
		$this->spel = $spel;
		$this->kaarten = Array();
		$this->haal_hand_uit_database();
		$this->sorteer();
	}
	function draw($klikbare_kaarten, $ingelogd_als_speler){//weergeef alle kaarten in de hand
		echo "<td class='hand'>";
		foreach($this->kaarten as $kaart){
			$kaart->draw($klikbare_kaarten, $ingelogd_als_speler, FALSE);
		}
		echo "</td>";
			
	}
	
	function haal_hand_uit_database(){
		global $conn;
		$sql = "SELECT kaart_id FROM spellen_spelers_kaarten WHERE spel_id='".($this->spel)->spel_id."' AND speler_id='".$this->speler."';";
		$result = $conn->query($sql);
		$this->kaarten = Array();
		if($result){
			while($row = $result->fetch_assoc()){
				$this->kaarten[] = new Kaart(($this->spel)->spel_id,$row["kaart_id"]);
			}
		}
		else{			
			echo $conn->error."<br>".$sql."<br>";
		}
	}
	
	function bepaal_aangeklikte_kaarten(){
		global $conn;
		$aangeklikte_kaarten = Array();
		foreach($this->kaarten as $kaart){
			//kijk of deze kaart is aangeklikt
			$sql = "SELECT aangeklikt FROM aangeklikte_kaarten WHERE spel_id = ".($this->spel)->spel_id." AND kaart_id = ".$kaart->kaart_id." AND aangeklikt=1";
			$result = $conn->query($sql);
			if(!$result){	
				echo $conn->error."<br>".$sql."<br>";
			}
			if($result->num_rows > 0){
				//dan is de kaart aangeklikt
				$aangeklikte_kaarten[] = $kaart;
			}
		}
		return $aangeklikte_kaarten;
	}
	function aantal_aangeklikte_kaarten(){
		return count($this->bepaal_aangeklikte_kaarten());
	}
	function sorteer(){
		//sorteer de kaarten van de speler in $kaarten
		$sorteer_voorkeur = "laag_hoog";
		usort($this->kaarten, "vergelijk_".$sorteer_voorkeur);
	}
}

function vergelijk_laag_hoog($kaart_1, $kaart_2){
	return $kaart_1->kaart_id - $kaart_2->kaart_id;
}


//vind de naam van een speler uit de database
function get_name_from_player($speler){
	global $conn;
	$sql = "SELECT speler_naam FROM spelers WHERE speler_id=".$speler;
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()){
		return $row["speler_naam"];
	}
}



class Spel {
	public $spel_id;
	public $spelers;
	public $handen;//elke speler heeft een hand
	function __construct($spel_id, $spelers){
		$this->spelers = $spelers;
		$this->spel_id = $spel_id;
		$this->handen = array();
		foreach($spelers as $speler){
			$this->handen[] = new Hand($speler, $this);
		}
	}
	function bepaal_hand($speler){//vind de hand die hoort bij een bepaalde speler in dit spel
		$i = array_search($speler, $this->spelers);
		return $this->handen[$i];
	}
	function get_spelers(){
		return $this->spelers;
	}
	
	function get_handen(){
		return $this->handen;
	}
	function teken(){
		teken_algemene_dingen_voor_spel();
		global $speler_aan_de_beurt, $stapel_diepte, $aantal_spelers_in_stapel, $gepaste_spelers, $aantal_spelers_in_spel, $standen, $stapel_ontploft, $aanwezige_spelers;
		$log_in_informatie = bepaal_log_in_informatie();
		$ingelogd = $log_in_informatie['ingelogd'];
		if($ingelogd){
			$ingelogde_speler = $log_in_informatie['ingelogde_speler'];
		}
		
		echo "<table class='kaarten'>";
		foreach($this->spelers as $speler){
			$naam = get_name_from_player($speler);
			$hand = $this->bepaal_hand($speler);
			$ingelogd_als_speler = ($ingelogd && $ingelogde_speler == $speler);
			echo "<tr class='speler'><td class='naam_en_knop'>";
			if($speler == $speler_aan_de_beurt){
				$klasse = '<h2 class="aan_de_beurt">';
			}
			else{
				$klasse = "<h2>";
			}
			echo $klasse.$naam."</h2>";		
			//creeer de grote knop
			$stand = $standen[$speler];
			$gepast = $gepaste_spelers[$speler];
			//eerst kijken we of de knop aan moet staan
			// gevallen waarin de knop aan moet staan:
			// 1.speler is aan de beurt, niet gepast of al uit en de stapel is niet leeg. Ofwel passend, ofwel om de stapel weg te halen.
			// 2.speler is aan de beurt, niet uit, stapel is leeg en de speler heeft een legale variatie aan kaarten aangeklikt.
			// 3.speler is aan de beurt, al gepast, maar er is niemand meer over.
			// 4.speler is aan de beurt en stapel is net ontploft
			// in alle gevallen kan het alleen als je de ingelogde speler bent.
			$disable = 'uitgezet';
			if($speler == $speler_aan_de_beurt && !$gepast && $stand == 0){
				$disable = '';
				$hand = $this->bepaal_hand($speler);
				$aangeklikt = $hand->bepaal_aangeklikte_kaarten();
				if($stapel_diepte == 0 && !is_legaal($aangeklikt)){
					$disable = 'uitgezet'; //in dit geval mag je niet op Speel klikken
				}
			}			
			if($stand > 0){
				$disable = 'uitgezet';
			}
			if($speler == $speler_aan_de_beurt && $stapel_ontploft){
				$disable = ' ';
			}
			if($speler == $speler_aan_de_beurt && $gepast && $aantal_spelers_in_stapel == 0 && $stand == 0){
				$disable = ''; //dit is om de stapel weg te doen nadat degene voor je uit is gegaan
			}
			if($aantal_spelers_in_spel == 1){
				$disable = 'uitgezet';
			}
			if(!$ingelogd_als_speler){
				$disable = 'uitgezet';
			}
			//nu bepalen we de tekst die op de knop moet staan
			if($speler == $speler_aan_de_beurt && $stapel_diepte == 0){
				$knop_tekst = "Speel";
			}
			if($speler == $speler_aan_de_beurt && $aantal_spelers_in_stapel <= 1){
				$knop_tekst = "Stapel weg";
			}			
			if($gepast && ($speler != $speler_aan_de_beurt || $aantal_spelers_in_stapel >=1) && $stand == 0){
				$knop_tekst = "Gepast";
			}
			if(($speler != $speler_aan_de_beurt && !$gepast && $stand == 0) || ($speler == $speler_aan_de_beurt && $aantal_spelers_in_stapel>1 && $stapel_diepte > 0)){
				$knop_tekst = "Pas";
			}
			if($stand > 0){
				$knop_tekst = $stand;
			}
			if($speler == $speler_aan_de_beurt && $stapel_ontploft){
				$knop_tekst = "Stapel weg";
			}
			if(!isset($knop_tekst)){
				$knop_tekst = "hier klopt iets niet";
				echo $speler_aan_de_beurt."<br>".$aantal_overgebleven_spelers."<br>".$stapel_diepte;
			}
			$knop_naam = "grote_knop_van_".$speler;
			echo("<form method='post'><input class='speler_knop ".$disable."' type='submit' name='".$knop_naam."'; value='".$knop_tekst."'/></form></td>");
			//teken de hand
			$klikbare_kaarten = ($speler == $speler_aan_de_beurt && $aantal_spelers_in_stapel > 1 && $aantal_spelers_in_spel > 1 && !$stapel_ontploft);
			$hand->draw($klikbare_kaarten, $ingelogd_als_speler);
			echo "</tr>";
		}
		//teken de stapel
		global $stapel_diepte;
		echo "<tr><td><h2>Stapel</h2></td>";
		if($stapel_diepte>0){
			global $conn;
			for($diepte = 1; $diepte <= $stapel_diepte; $diepte++){
				if($diepte > 1){
					echo "<tr class='stapel'><td></td><td>";
				}
				else{
					echo "<td>";
				}
				$sql = "SELECT kaart_id FROM spel_stapel WHERE spel_id = ".$this->spel_id." AND diepte=".$diepte;
				$result = $conn->query($sql);
				if($result && $result->num_rows>0){
					$vorige_diepte = 0;
					while($row = $result->fetch_assoc()){
						$kaart_id = $row['kaart_id'];
						$vorige_diepte = $diepte;
						$kaart = new Kaart($this->spel_id, $kaart_id);
						$kaart->draw(FALSE, TRUE, TRUE);
					}
				}
				else{
					echo $conn->error."<br>".$sql."<br>";
				}
				echo "</td></tr>";
			}
		}
		echo "</table>";
		//als er nog maar 1 persoon over is, is het spel voorbij, en moet je het spel kunnen beëindigen
		if($aantal_spelers_in_spel <= 1){			
			global $kamer_id;
			$nieuw_spel_knop_naam = "einde_spel".$kamer_id;
			echo "<form method='post'><input class='delen' style='width:400px'; type='submit' name='".$nieuw_spel_knop_naam."'; value='Beëindig het spel'/></form>";
		}
		//kijk welke spelers er te wachten staan
		$aanwezige_spelers = bepaal_aanwezige_spelers();
		foreach($aanwezige_spelers as $speler){
			if(!in_array($speler, $this->spelers)){
				echo $speler." staat in de wachtrij<br>";
			}
		}
		//teken nog andere dingen
		teken_algemene_dingen_na_spel();
		
	}
	
	function nieuwe_stapel(){
		global $conn;
		//leeg de stapel
		$sql = "DELETE FROM spel_stapel";
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
		//niemand is meer gepast behalve de mensen die uit zijn
		$sql = "UPDATE spellen_spelers SET gepast = 0 WHERE stand=0 AND spel_id=".$this->spel_id;
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
		global $kamer_id;
		$_POST = Array();
		update($kamer_id);
	}
	function volgende_aan_de_beurt(){
		global $conn;
		$sql = "UPDATE spellen_beurt SET speler_id=(
					SELECT speler_id FROM spellen_spelers WHERE spel_id=".$this->spel_id." AND volgorde=((
						SELECT volgorde FROM spellen_spelers WHERE spel_id=".$this->spel_id." AND speler_id=(
							SELECT speler_id FROM spellen_beurt WHERE spel_id=".$this->spel_id."
						)
					)+1)%".count($this->spelers)."
				)
				WHERE spel_id=".$this->spel_id;
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
		global $kamer_id;
		update($kamer_id);
	}
	function speel($speler, $kaarten){
		global $stapel_diepte, $conn, $aantal_overgebleven_spelers, $kamer_id;
		$stapel_diepte++;
		$_POST = Array();
		foreach($kaarten as $kaart){
			//verwijder de kaarten uit de hand
			$sql = "DELETE FROM spellen_spelers_kaarten WHERE spel_id=".$this->spel_id." AND speler_id=".$speler." AND kaart_id=".$kaart->kaart_id;
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			//verwijder ze ook als aangeklikt
			$sql = "DELETE FROM aangeklikte_kaarten WHERE spel_id=".$this->spel_id." AND kaart_id=".$kaart->kaart_id;
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			//stop ze in de stapel
			$sql = "INSERT INTO spel_stapel (spel_id, kaart_id, diepte) VALUES (".$this->spel_id.",".$kaart->kaart_id.",".$stapel_diepte.")";					
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
		}
		
		
		//kijk of de speler nu uit is blabl
		$sql = "SELECT kaart_id FROM spellen_spelers_kaarten WHERE spel_id=".$this->spel_id." AND speler_id=".$speler;
		$result = $conn->query($sql);
		if(!$result){
			echo $conn->error."<br>".$sql."<br>";
		}
		$klaar = ($result->num_rows == 0);
		//kijk of er net ontploft is blablabla
		$ontploft = is_het_een_ontploffing($kaarten);
		
		if($ontploft){
			//kijk of er vier klaver negens bij zijn
			$aantal_klaver_negens = 0;
			foreach($kaarten as $kaart){
				if($kaart->waarde == '9' && $kaart->kleur == 'klaver'){
					$aantal_klaver_negens++;
				}
			}
			if($aantal_klaver_negens == 4){
				//de persoon heeft direct gewonnen
				$sql = "UPDATE spellen_spelers SET stand = stand + 1 WHERE spel_id=".$this->spel_id." AND stand!=0 AND stand<=(
							SELECT MIN(stand) FROM spellen_spelers WHERE spel_id=".$this->spel_id." AND stand+1 NOT IN (
								SELECT stand FROM spellen_spelers WHERE spel_id = ".$this->spel_id."
							)
						)";
				if(!$conn->query($sql)){
					echo $conn->error."<br>".$sql."<br>";
				}		
				$sql = "UPDATE spellen_spelers SET stand = 1 WHERE spel_id=".$this->spel_id." AND speler_id=".$speler;
				if(!$conn->query($sql)){
					echo $conn->error."<br>".$sql."<br>";
				}
				update($kamer_id);
				update(-1);
				return;
			}
			
			//de persoon blijft aan de beurt en mag straks de stapel weghalen, maar dat staat ergens anders
		}
		
		//ook nog de beurtvolgorde aanpassen
		if(!$ontploft){
			$this->volgende_aan_de_beurt();
		}
		
		
		if($klaar && !$ontploft){
			//zet de stand in de database
			//we halen de speler pas later uit de beurtvolgorde, want misschien heeft diegene nog een stapel
			$sql = "UPDATE spellen_spelers SET stand=
			(SELECT MIN(stand) FROM spellen_spelers WHERE spel_id=".$this->spel_id." AND stand+1 NOT IN(SELECT stand FROM spellen_spelers WHERE spel_id=".$this->spel_id."))
			+1 WHERE spel_id=".$this->spel_id." AND speler_id=".$speler;
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
		}
		update($kamer_id);
		update(-1);//zodat je op de thuispagina de voortgang kan zien
	}
	function verwijder_geklikte_kaarten($speler){
		global $conn;
		$sql = "UPDATE aangeklikte_kaarten, spellen_spelers_kaarten SET aangeklikt=0 WHERE aangeklikte_kaarten.spel_id = spellen_spelers_kaarten.spel_id
				AND aangeklikte_kaarten.spel_id=".$this->spel_id." AND aangeklikte_kaarten.kaart_id=spellen_spelers_kaarten.kaart_id AND speler_id=".$speler;
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
	}
}
	

function begin_spel(){
	global $kamer_id;
	global $conn;
	global $speler_aan_de_beurt;
	//kijk welke spelers er mee willen doen
	$spelers = Array();
	$sql = "SELECT speler_id FROM kamers_spelers WHERE kamer_id=".$kamer_id;
	$result = $conn->query($sql);
	if(!$result){
		echo $conn->error."<br>".$sql."<br>";
	}
	if($result->num_rows<=1){
		echo "te weinig spelers";
		$_POST = Array();
		return;
	}
	if($result->num_rows>=1){
		while($row = $result->fetch_assoc()){
			$spelers[] = $row['speler_id'];
		}
	}
	//zet die spelers niet meer op klaar voor een nieuw spel
	$sql = "UPDATE kamers_spelers SET klaar_voor_nieuw_potje=0 WHERE kamer_id=".$kamer_id;
	if(!$conn->query($sql)){
		echo $conn->error."<br>".$sql."<br>"; 
	}
	//creeer het goede id voor dit spel
	$sql = "SELECT MAX(spel_id) FROM spellen_spelers;";
	$result = $conn->query($sql);
	if(!$result){
		echo $conn->error."<br>".$sql."<br>"; 
	}
	if($result->num_rows == 0){
		$spel_id = 1;
	}
	else{
		while($row = $result->fetch_assoc()){
			$spel_id = $row["MAX(spel_id)"]+1;
		}
	}
	//creeer het spel in de database
	$sql = "INSERT INTO actieve_spellen  VALUES(".$kamer_id.",".$spel_id.",CURRENT_TIMESTAMP);";
	if(!$conn->query($sql)){
		echo $conn->error."<br>".$sql."<br>";
	}	
	//creer de kaarten in de andere database
	foreach(range(0,63) as $kaart_id){
		if($kaart_id != 8){
			$sql = "INSERT INTO aangeklikte_kaarten VALUES(".$spel_id.",".$kaart_id.",0)";
		}
		else{
			$sql = "INSERT INTO aangeklikte_kaarten VALUES(".$spel_id.",8,1)";
		}
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql;
		}
	}	
	//nu gaan we de kaarten uitdelen
	$kaart_ids = range(0, 63);
	shuffle($kaart_ids);
	$index_speler = rand(0,count($spelers)-1);
	for($i=0;$i<64;$i++){
		$kaart_id = $kaart_ids[$i];
		$kaart = New Kaart($spel_id, $kaart_id);
		//kijk of het de klaver acht is
		if($kaart->kleur == "klaver" && $kaart->waarde == "8"){
			$index_begin_speler = $index_speler;
			$speler_aan_de_beurt = $index_speler;
		}
		$speler = $spelers[$index_speler];
		$sql = "INSERT INTO spellen_spelers_kaarten VALUES(".$spel_id.",".$speler.",".$kaart_id.")";
		if($conn->query($sql) == FALSE){
			echo 'database updaten mislukt';
			echo $conn->error."<br>".$sql."<br>";
		}
		$index_speler = ($index_speler+1)%count($spelers);
	}
	//zet de spelers in de database
	for($j=0;$j<count($spelers);$j++){
		$sql = "INSERT INTO spellen_spelers VALUES(".$spel_id.",".$spelers[$j].",".$j.",0,0);";
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql;
		}
	}
	//en de beginspeler is nu aan de beurt
	$sql = "INSERT INTO spellen_beurt VALUES(".$spel_id.",".$spelers[$index_begin_speler].")";
	if(!$conn->query($sql)){
		echo $conn->error."<br>".$sql;
	}
	update($kamer_id);
	update(-1);
}
	

function maak_spel_vanuit_database($spel_id){
	global $conn;
	//bepaal wie er meedoen
	$speler_ids = Array();
	$sql = "SELECT speler_id FROM spellen_spelers WHERE spel_id='".$spel_id."';";
	$result = $conn->query($sql);
	if($result){
		while($row = $result->fetch_assoc()){
			$speler_ids[] = $row['speler_id'];
		}
	}

	sort($speler_ids);
	//en dan het spel zelf
	$spel = new Spel($spel_id, $speler_ids);
	return $spel;
}






function bepaal_globale_variabelen($spel){
	$spel_id = $spel->spel_id;
	global $conn, $speler_aan_de_beurt, $stapel_diepte, $stapel_breedte, $stapel_waarde, $stapel_jokers, $aantal_spelers_in_spel, $aantal_spelers_in_stapel, $gepaste_spelers, $standen, $stapel_ontploft, $kamer_id;
	//wie is er aan de beurt
	$sql = "SELECT speler_id FROM spellen_beurt WHERE spel_id=".$spel_id;
	$result = $conn->query($sql);
	if($result and $result->num_rows>0){
		while($row = $result->fetch_assoc()){
			$speler_aan_de_beurt = $row['speler_id'];
		}
	}
	else{
		echo "er is niemand aan de beurt <br>".$conn->error."<br>".$sql;
	}
	//hoe diep is de stapel
	$sql = "SELECT MAX(diepte) FROM spel_stapel WHERE spel_id = ".$spel_id;
	$result = $conn->query($sql);
	if($result && $result->num_rows>0){
		while($row = $result->fetch_assoc()){
			$stapel_diepte = $row['MAX(diepte)'];
		}
		if(!$stapel_diepte){
			$stapel_diepte = 0;
		}
	}
	else{
		echo $conn->error."<br>".$sql;
	}
	//welke waarde ligt er bovenop de stapel
	$relevante_diepte = $stapel_diepte;
	do{
		if($relevante_diepte ==0){
			$stapel_waarde = "lege tafel";
			break;
		}
		$sql = "SELECT kaart_id FROM spel_stapel WHERE spel_id = ".$spel_id." AND diepte = ".$relevante_diepte;
		$result = $conn->query($sql);
		if($result && $result->num_rows>0){
			$stapel_waarde = "joker";
			while($row = $result->fetch_assoc()){
				$kaart_id = $row['kaart_id'];
				$kaart_waarde = bepaal_waarde($kaart_id);
				if($kaart_waarde!="joker"){
					$stapel_waarde = $kaart_waarde;
				}
			}
		}
		else{
			echo $conn->error."<br>".$sql;
		}
		$relevante_diepte -= 1;
	}while($stapel_waarde=="5");
	
	//hoeveel kaarten liggen er op de stapel
	if($stapel_diepte>0){
		$sql = "SELECT kaart_id FROM spel_stapel WHERE spel_id = ".$spel_id." AND diepte = ".$stapel_diepte;
		$result = $conn->query($sql);
		if($result && $result->num_rows>0){
			$stapel_breedte = $result->num_rows;
		}
		else{
			echo $conn->error."<br>".$sql;
		}
	}
	else{
		$stapel_breedte = 0;
	}
	
	//ligt er een joker bovenop
	if($stapel_diepte>0){
		$stapel_jokers = FALSE;
		$sql = "SELECT kaart_id FROM spel_stapel WHERE spel_id = ".$spel_id." AND diepte = ".$stapel_diepte;
		$result = $conn->query($sql);
		if($result && $result->num_rows>0){
			while($row = $result->fetch_assoc()){
				$kaart_id = $row['kaart_id'];
				$kaart_waarde = bepaal_waarde($kaart_id);
				if($kaart_waarde=="joker"){
					$stapel_jokers = TRUE;
				}
			}
		}
		else{
			echo $conn->error."<br>".$sql."<br>";
		} 
	}
	
	//ook het aantal overgebleven spelers is belangrijk in wat volgt
	$aantal_spelers_in_stapel = count($spel->spelers);
	$aantal_spelers_in_spel = count($spel->spelers);
	$sql = "SELECT speler_id, gepast FROM spellen_spelers WHERE spel_id=".$spel_id;
	$result = $conn->query($sql);
	if($result){
		while($row = $result->fetch_assoc()){
			$gepaste_spelers[$row['speler_id']] = $row['gepast'];
			if($row['gepast']){
				$aantal_spelers_in_stapel -= 1;
			}
		}
	}
	else{
		echo $conn->error."<br>".$sql."<br>";
	}
	$sql = "SELECT speler_id, stand FROM spellen_spelers WHERE spel_id=".$spel_id;
	$result = $conn->query($sql);
	if($result){
		while($row = $result->fetch_assoc()){
			$standen[$row['speler_id']] = $row['stand'];
			if($row['stand']){  
				$aantal_spelers_in_spel -=1; //spelers die uit zijn, zijn nog aanwezig in de stapel, totdat de stapel weg is, daarna staan ze permanent op gepast
			}
		}
	}
	else{
		echo $conn->error."<br>".$sql."<br>";
	}
	
	//kijk of de bovenkant van de stapel tot een ontploffing leidt
	$stapel_ontploft = ($stapel_waarde == '10');
	$sql = "SELECT kaart_id FROM spel_stapel WHERE spel_id =".$spel_id." AND diepte=".$stapel_diepte;
	$result = $conn->query($sql);
	if(!$result){
		echo $conn->error."<br>".$sql."<br>";
	}
	$aantal_jokers = 0;
	while($row = $result->fetch_assoc()){
		$waarde = bepaal_waarde($row['kaart_id']);
		if($waarde =='joker'){
			$aantal_jokers++;
		}
	}
	if($stapel_breedte-$aantal_jokers >= 4){
		$stapel_ontploft = True;
	}
		
}

function teken_zonder_spel(){
	teken_algemene_dingen_voor_spel();
	global $kamer_id, $conn;
	$log_in_informatie = bepaal_log_in_informatie();
	$ingelogd = $log_in_informatie['ingelogd'];
	if($ingelogd){
		$ingelogde_speler = $log_in_informatie['ingelogde_speler'];
		//kijk of de speler al is aangeschoven, en of die al klaar is
		$sql = "SELECT klaar_voor_nieuw_potje FROM kamers_spelers WHERE kamer_id=".$kamer_id." AND speler_id=".$ingelogde_speler;
		$result = $conn->query($sql);
		if(!$result){
			echo $conn->error."<br>".$sql."<br>";
		}
		$aangeschoven = False;
		while($row = $result->fetch_assoc()){
			$aangeschoven = True;
			$klaar = ($row['klaar_voor_nieuw_potje'] == 1);
		}
		if($aangeschoven){
			if($klaar){
				$knop_naam = "niet_klaar".$kamer_id;
				$klasse = 'delen groen';
			}
			else{
				$knop_naam = "klaar".$kamer_id;
				$klasse = 'delen';
			}
			echo "<form method='post'><input class='".$klasse."' style='width:400px'; type='submit' name='".$knop_naam."'; value='Klaar voor een nieuw potje'/></form>";
		}
	}
	echo "aanwezige spelers: ";
	$aanwezige_spelers = bepaal_aanwezige_spelers();
	foreach($aanwezige_spelers as $speler){
		if($speler != $aanwezige_spelers[0]){
			echo ", ";
		}
		//kijk of de speler al klaar is voor het nieuwe spel
		$sql = "SELECT speler_id FROM kamers_spelers WHERE kamer_id=".$kamer_id." AND speler_id=".$speler." AND klaar_voor_nieuw_potje = 1";
		$result = $conn->query($sql);
		if(!$result){
			echo $conn->error."<br>".$sql."<br>";
		}
		if($result->num_rows){
			$klasse= "class='groen'";
		}
		else{
			$klasse= "";
		}
		echo "<h4 ".$klasse." >".get_name_from_player($speler)."</h4>";
	}
	if(!$aanwezige_spelers){
		echo " er is niemand";
	}
	echo ".";
	teken_algemene_dingen_na_spel();
}

function bepaal_aanwezige_spelers(){
	global $conn, $kamer_id;
	$aanwezige_spelers = Array();
	$sql = "SELECT speler_id FROM kamers_spelers WHERE kamer_id=".$kamer_id;
	$result = $conn->query($sql);
	if(!$result){
		echo $conn->error."<br>".$sql."<br>";
	}
	while($row = $result->fetch_assoc()){
		$aanwezige_spelers[] = $row['speler_id'];
	}
	return $aanwezige_spelers;
}



function ververs(){
	controleer_log_uit();
	global $conn;
	global $kamer_id;
	//eerst vinden we het juiste spel_id
	$sql = "SELECT spel_id FROM actieve_spellen WHERE kamer_id='".$kamer_id."';";
	$result = $conn->query($sql);
	if(!$result){
		echo $conn->error."<br>".$sql."<br>";
	}
	if($result->num_rows){
		while($row = $result->fetch_assoc()){
			$spel_id = $row['spel_id'];
		}
		$er_is_een_spel = True;
	}
	else{
		$er_is_een_spel = False;
	}
	$log_in_informatie = bepaal_log_in_informatie();
	$ingelogd = $log_in_informatie['ingelogd'];
	if($ingelogd){
		$ingelogde_speler = $log_in_informatie['ingelogde_speler'];
	}
	if($er_is_een_spel){
		$spel = maak_spel_vanuit_database($spel_id);
				
		//bepaal een aantal globale variabelen die de stand van het spel aangeven
		global $conn, $speler_aan_de_beurt, $stapel_diepte, $stapel_breedte, $stapel_waarde, $stapel_jokers, $aantal_spelers_in_spel, $aantal_spelers_in_stapel, $gepaste_spelers, $standen;
		bepaal_globale_variabelen($spel);	
		
		//test:
		//echo "de diepte is ".$stapel_diepte.", de breedte is ".$stapel_breedte.", de waarde is ".$stapel_waarde." en er zijn jokers: ".($stapel_jokers?"ja":"nee")."<br> er zijn ".$aantal_spelers_in_spel." spelers over<br>";
		
		//iedereen die niet aan de beurt is heeft geen kaarten meer aangeklikt
		foreach($spel->spelers as $speler){
			if($speler != $speler_aan_de_beurt){
				$spel->verwijder_geklikte_kaarten($speler);
			}
		}	
		
		//nu moeten we nog kijken, of er iets moet gebeuren, omdat iemand op een knop gedrukt heeft
		$knop_naam = "grote_knop_van_".$speler_aan_de_beurt;
		if(isset($_POST[$knop_naam])){
			switch($_POST[$knop_naam]){
				case "Speel":
					//de speler heeft gespeeld
					//we gaan er hier van uit dat het legaal was
					$hand = $spel->bepaal_hand($speler_aan_de_beurt);
					$kaarten = $hand->bepaal_aangeklikte_kaarten();
					$spel->speel($speler_aan_de_beurt, $kaarten);
					break;
				case "Pas":
					//de speler heef gepast
					//als er maar één persoon over is, mag die beginnen, maar dat wordt ergens anders geregeld
					//beurt aanpassen in database
					$spel->volgende_aan_de_beurt();
					//verwijder alle aangeklikte kaarten van die speler
					$sql = "UPDATE aangeklikte_kaarten, spellen_spelers_kaarten SET aangeklikt = 0
					WHERE aangeklikte_kaarten.spel_id=spellen_spelers_kaarten.spel_id AND aangeklikte_kaarten.kaart_id=spellen_spelers_kaarten.kaart_id
					AND aangeklikte_kaarten.spel_id=".$spel_id." AND speler_id=".$speler_aan_de_beurt;
					if(!$conn->query($sql)){
						echo $conn->error."<br>".$sql."<br>";
					}
					update($kamer_id);
					//in de database zetten dat de speler gepast heeft
					$sql = "UPDATE spellen_spelers SET gepast = 1 WHERE spel_id=".$spel_id." AND speler_id=".$speler_aan_de_beurt;
					if(!$conn->query($sql)){
						echo $conn->error."<br>".$sql."<br>";
					}
					update($kamer_id);
					break;
				case "Stapel weg":				
					//als de persoon geen kaarten meer heeft, dan is de stapel net ontploft en wordt de persoon dus laatste
					$hand = $spel->bepaal_hand($speler_aan_de_beurt);
					if(!count($hand->kaarten)){
						//de persoon is direct laatste
						//mocht er al iemand laatste zijn dan is die nu niet meer laatste (zeldzaam geval)
						$sql = "UPDATE spellen_spelers SET stand = stand - 1 WHERE spel_id=".$spel_id." AND stand >(
									SELECT MIN(stand) FROM spellen_spelers WHERE spel_id=".$spel_id." AND stand+1 NOT IN (
										SELECT stand FROM spellen_spelers WHERE spel_id = ".$spel_id."
									)
								)";
						if(!$conn->query($sql)){
							echo $conn->error."<br>".$sql."<br>";
						}
						update($kamer_id);
						$sql = "UPDATE spellen_spelers SET stand = ".count($spel->spelers)." WHERE spel_id=".$spel_id." AND speler_id=".$speler_aan_de_beurt;
						if(!$conn->query($sql)){
							echo $conn->error."<br>".$sql."<br>";
						}
						update($kamer_id);
						//dan moet ook alvast de volgende aan beurt
						$spel->volgende_aan_de_beurt();
					}			
					//de speler heeft de stapel weggelegd
					$spel->nieuwe_stapel($speler_aan_de_beurt);
					break;
			}
			$_POST = Array();
			return ververs();
		}
			
		//we kijken, of iemand kaarten aangeklikt heeft, die gespeeld kunnen worden
		$hand = $spel->bepaal_hand($speler_aan_de_beurt);
		$kaarten = $hand->bepaal_aangeklikte_kaarten();
		if(is_legaal($kaarten) && $stapel_diepte > 0){
			//in dat geval worden de kaarten inderdaad gespeeld
			//haal die kaarten uit de hand van de speler (in de database) en stop ze in de stapel
			$spel->speel($speler_aan_de_beurt, $kaarten);
			//en dan alles nog een keer doen
			return ververs();
		}	
		
		//we kijken of degene die aan de beurt is al gepast heeft of klaar is, dan moet de volgende aan beurt, tenzij natuurlijk die persoon als laatste over is
		if($standen[$speler_aan_de_beurt] && $aantal_spelers_in_spel){
			//deze persoon heeft vanaf nu gepast, als dat nog niet zo was
			$sql = "UPDATE spellen_spelers SET gepast=1 WHERE spel_id=".$spel_id." AND speler_id=".$speler_aan_de_beurt;
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			$spel->volgende_aan_de_beurt();
			return ververs();
		}
		if($gepaste_spelers[$speler_aan_de_beurt] && $aantal_spelers_in_stapel && $aantal_spelers_in_spel){
			$spel->volgende_aan_de_beurt();
			return ververs();
		}
		
		$eind_spel_knop_naam = "einde_spel".$kamer_id;
		if(isset($_POST[$eind_spel_knop_naam])){
			//eerst zetten we er nog even de stand van de laatste persoon bij
			$sql = "UPDATE spellen_spelers SET stand=(
						SELECT MIN(stand) FROM spellen_spelers WHERE spel_id=".$spel_id." AND stand+1 NOT IN(
							SELECT stand FROM spellen_spelers WHERE spel_id=".$spel_id."
						)
					)
					+1 WHERE spel_id=".$spel_id." AND speler_id=".$speler_aan_de_beurt;
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			//vergeet niet om het huidige spel uit de actieve spellen te verwijderen
			$sql = "DELETE FROM actieve_spellen WHERE kamer_id=".$kamer_id;
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			$_POST = Array();
			update($kamer_id);
			update(-1);
			return ververs();
		}
	}
	//hierna komen nog algemene dingen die ook moeten gebeuren als er geen spel beschikbaar is	
	
	//kijk of er een nieuw spel wordt begonnen
	$klaar_knop_naam = "klaar".$kamer_id;
	if(isset($_POST[$klaar_knop_naam]) && $ingelogd){
		$_POST = Array();
		//voeg toe dat die speler klaar is
		$sql = "UPDATE kamers_spelers SET klaar_voor_nieuw_potje=1 WHERE kamer_id=".$kamer_id." AND speler_id=".$ingelogde_speler;
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
		//kijk of iedereen klaar is
		$sql = "SELECT kamer_id FROM kamers_spelers WHERE klaar_voor_nieuw_potje=0 AND kamer_id=".$kamer_id;
		$result = $conn->query($sql);
		if(!$result){
			echo $conn->error."<br>".$sql."<br>";
		}
		if(!($result->num_rows)){
			//dan zijn we klaar voor een nieuw spel, mits er genoeg spelers zijn
			$sql = "SELECT kamer_id FROM kamers_spelers WHERE kamer_id=".$kamer_id;
			$result = $conn->query($sql);
			if(!$result){
				echo $conn->error."<br>".$sql."<br>";
			}
			if($result->num_rows >= 2){
				begin_spel();
			}
		}
		update($kamer_id);
		return ververs();
	}
	$niet_klaar_knop_naam = "niet_klaar".$kamer_id;
	if(isset($_POST[$niet_klaar_knop_naam]) && $ingelogd){
		$_POST = Array();
		//voeg toe dat die speler niet klaar is
		$sql = "UPDATE kamers_spelers SET klaar_voor_nieuw_potje=0 WHERE kamer_id=".$kamer_id." AND speler_id=".$ingelogde_speler;
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
		update($kamer_id);
		return ververs();
	}
	
	//kijk of er iemand aanschuift
	if(isset($_POST['Schuif_aan'])){
		if($ingelogd){
			//voeg de ingelogde speler toe aan de kamer, haal hem er eerst uit voor de zekerheid
			$sql = "DELETE FROM kamers_spelers WHERE kamer_id=".$kamer_id." AND speler_id=".$ingelogde_speler;
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			$sql = "INSERT INTO kamers_spelers VALUES (".$kamer_id.",".$ingelogde_speler.", 0)";
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			update($kamer_id);
			update(-1);
		}
		$_POST = Array();
		return ververs();
	}
	//kijk of er iemand de tafel verlaat
	if(isset($_POST["Ga_weg"])){
		if($ingelogd){
			//haal de ingelogde speler uit de kamer
			$sql = "DELETE FROM kamers_spelers WHERE kamer_id=".$kamer_id." AND speler_id=".$ingelogde_speler."";
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			update($kamer_id);
			update(-1);
		}		
		$_POST = Array();
		return ververs();
	}
	
	
	if($er_is_een_spel){
		$spel->teken();
	}
	else{
		teken_zonder_spel();
	}
}




ververs();

?>
<style>
<?php include 'opmaak.css'; ?>
</style>