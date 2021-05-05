<?php
include 'opruimen.php';
include 'speel_geluid.php';
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

function teken_script_kaart_klik($kamer_id, $ingelogde_speler){
	global $doorgeef_fase;
	echo "<script>
		function klikOp(kaart_id){";
	if(!$doorgeef_fase){
		echo "if(kaart_id == 8){//klaver 8 kan je niet op klikken
				return;
			}";
	}
	echo "
			var kaartElement = document.getElementById('kaart'+kaart_id);
			kaartElement.classList.toggle('geselecteerd');
			var xmlhttp = new XMLHttpRequest();
			xmlhttp.open('POST',
				'geklikte_kaart.php?kaart_id='+kaart_id+'&kamer_id=".$kamer_id."'
				, true);
			xmlhttp.send();
			xmlhttp.onreadystatechange = function(){
				if(this.readyState == 4 && this.status == 200){
					if(this.responseText == 'vernieuw'){						
						window.location.href = window.location.href;
					}
					if(this.responseText == 'speel_aan'){
						document.getElementById('speler".$ingelogde_speler."').classList.remove('uitgezet');
					}
					if(this.responseText == 'speel_uit'){
						document.getElementById('speler".$ingelogde_speler."').classList.add('uitgezet');
					}
				}
			}
		}
		</script>";
}	
			
function is_legaal($kaarten){
	global $stapel_diepte;
	global $stapel_breedte;
	global $stapel_waarde;
	global $stapel_jokers;
	if(!$kaarten){
		return False;
	}
	if(count($kaarten)!=$stapel_breedte && $stapel_breedte!=0){
		return False;
	}
	//kijk of het allemaal dezelfde kaarten en/of jokers zijn, bepaal de waarde
	$waarde = 'joker';
	foreach($kaarten as $kaart){
		if($kaart->waarde != 'joker'){
			if($kaart->waarde != $waarde && $waarde != 'joker'){
				return False;
			}
			$waarde = $kaart->waarde;
		}
	}
	if($stapel_diepte == 0){
		return True;
	}
	if(count($kaarten) != $stapel_breedte){
		echo "niet genoeg kaarten";
		return False;
	}
	//check de regels met boeren en jokers
	$is_er_een_joker = False;
	foreach($kaarten as $kaart){
		$is_er_een_joker = ($is_er_een_joker || ($kaart->waarde == 'joker'));
	}
	if(($stapel_jokers && $waarde == 'J') || ($stapel_waarde == 'J' && $is_er_een_joker)){
		return True;
	}
	switch($stapel_waarde){
		case 'lege tafel':
			return True;
			break;
		case 'joker':
			return ($waarde != 'joker');
			break;
		case '8':
			return !in_array($waarde, Array('joker', '8'));
			break;
		case '4':
			return !in_array($waarde, Array('joker', '8', '4'));
			break;
		case '6':
			return !in_array($waarde, Array('joker', '8', '4', '6'));
			break;
		case '7':
			return in_array($waarde, Array('3', '4', '5', '6'));
			break;
		case '3':
			return !in_array($waarde, Array('joker', '8', '4', '6', '7', '3'));
			break;
		case '9':
			return !in_array($waarde, Array('joker', '8', '4', '6', '7', '3', '9'));
			break;
		case '10':
			return False;
			break;
		case 'J':
			return in_array($waarde, Array('5', 'Q', 'K', 'A', '2'));
			break;
		case 'Q':
			return in_array($waarde, Array('5', 'K', 'A', '2'));
			break;
		case 'K':
			return in_array($waarde, Array('5', 'A', '2'));
			break;
		case 'A':
			return in_array($waarde, Array('5', '2'));
			break;
		case '2':
			return ($waarde == '5');
			break;
	}
}

function is_doorgeef_legaal($hand_kaarten, $aangeklikte_kaarten, $doorgeef_stand){
	if($doorgeef_stand > 0){
		return (count($aangeklikte_kaarten) == $doorgeef_stand);
	}
	else{
		if(count($aangeklikte_kaarten) != -$doorgeef_stand){
			return False;
		}
		foreach($aangeklikte_kaarten as $kaart_hoog){
			foreach($hand_kaarten as $kaart_laag){
				if(!in_array($kaart_laag, $aangeklikte_kaarten) && vergelijk_laag_hoog($kaart_hoog, $kaart_laag) < 0){
					return False;
				}
			}
		}
		return True;
	}
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

function bepaal_waarde($kaart_id){
	$i = (int)$kaart_id/4;
	$alle_kaarten = Array('joker','joker','8','4','5','6','7','3','9','9','10','J','Q','K','A','2');
	return $alle_kaarten[$i];
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
		global $conn;
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
		//kijk of de kaart in de doorgeeftabel staat
		$sql = "SELECT kaart_id FROM doorgegeven_kaarten WHERE spel_id=".$this->spel_id." AND kaart_id=".$this->kaart_id;
		$result = $conn->query($sql);
		if(!$result){
			echo $conn->error."<br>".$sql."<br>";
		}
		if($result->num_rows && $zichtbaar){
			$klasse = "gouden_rand ";
		}
		else{
			$klasse = "";
		}
		if($klikbare_kaarten && $zichtbaar){
			$kaart_naam = "kaart".$this->kaart_id;
			if($this->geklikt){
				$klasse = $klasse."kaart geselecteerd";
			}
			else{
				$klasse = $klasse."kaart";
			}
			echo "<input class='".$klasse."' onclick=klikOp(".$this->kaart_id.") type='image' id='".$kaart_naam."' src='/afbeeldingen/".$kaart_tekst.".png' alt='".$kaart_tekst."'/>";
		}
		else{
			if($zichtbaar){
				$klasse = $klasse."kaart onklikbaar";
			}
			else{
				$klasse = $klasse."kaart onzichtbaar";
			}
			if($in_stapel){
				$klasse = $klasse."kaart in_stapel";
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
		global $aantal_spelers_in_spel;
		echo "<td class='hand'>";
		foreach($this->kaarten as $kaart){
			$kaart->draw($klikbare_kaarten, $ingelogd_als_speler || $aantal_spelers_in_spel <= 1, FALSE);
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
	if($kaart_1->waarde == $kaart_2->waarde){
		return 0;
	}
	return $kaart_1->kaart_id - $kaart_2->kaart_id;
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
		global $speler_aan_de_beurt, $stapel_diepte, $aantal_spelers_in_stapel, $gepaste_spelers, $aantal_spelers_in_spel, $standen, $stapel_ontploft, $aanwezige_spelers, $doorgeef_fase, $doorgeef_standen;
		$log_in_informatie = bepaal_log_in_informatie();
		$ingelogd = $log_in_informatie['ingelogd'];
		if($ingelogd){
			$ingelogde_speler = $log_in_informatie['ingelogde_speler'];
		}
		
		echo "<table><tr><td class='kaarten'><table class='kaarten'>";
		foreach($this->spelers as $speler){
			$naam = get_name_from_player($speler);
			$hand = $this->bepaal_hand($speler);
			$ingelogd_als_speler = ($ingelogd && $ingelogde_speler == $speler);
			$gepast = $gepaste_spelers[$speler];
			$stand = $standen[$speler];
			echo "<tr class='speler'><td class='naam_en_knop'>";
			if($doorgeef_fase){
				$klasse = "<h2>";
			}
			elseif($speler == $speler_aan_de_beurt){
				$klasse = '<h2 class="aan_de_beurt">';
			}
			elseif($stand){
				$klasse = "<h2 class='klaar'>";
			}
			elseif($gepast){
				$klasse = "<h2 class='gepast'>";
			}
			else{
				$klasse = "<h2>";
			}
			echo $klasse.$naam."</h2>";		
			//creeer de grote knop
			//eerst kijken we of de knop aan moet staan
			// gevallen waarin de knop aan moet staan:
			// 1.speler is aan de beurt, niet gepast of al uit en de stapel is niet leeg. Ofwel passend, ofwel om de stapel weg te halen.
			// 2.speler is aan de beurt, niet uit, stapel is leeg en de speler heeft een legale variatie aan kaarten aangeklikt.
			// 3.speler is aan de beurt, al gepast, maar er is niemand meer over.
			// 4.speler is aan de beurt en stapel is net ontploft
			// 5.speler is aan de beurt, doorgeeffase staat aan en speler moet doorgeven
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
			if($doorgeef_fase && $doorgeef_standen[$speler]){
				$disable = ' ';
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
				$disable = 'uitgezet groot';
			}
			if($speler == $speler_aan_de_beurt && $stapel_ontploft){
				$knop_tekst = "Stapel weg";
			}
			if(!isset($knop_tekst)){
				$knop_tekst = "hier klopt iets niet";
				echo $speler_aan_de_beurt."<br>".$aantal_overgebleven_spelers."<br>".$stapel_diepte;
			}
			if($doorgeef_fase){
				$doorgeef_stand = $doorgeef_standen[$speler];
				if($doorgeef_stand == 0){
					$knop_tekst = "Wacht op doorgeven";
				}
				else{
					$knop_tekst = "Geef door";
					//kijk of de speler al heeft doorgegeven
					global $conn;
					$sql = "SELECT speler_id FROM doorgegeven_kaarten WHERE spel_id=".$this->spel_id." AND speler_id=".$speler;
					$result = $conn->query($sql);
					if(!$result){
						echo $conn->error."<br>".$sql."<br>";
					}
					if($result->num_rows){
						$disable = 'uitgezet';
					}
				}
			}			
			
			$knop_naam = "grote_knop_van_".$speler;
			echo("<form method='post'><input id='speler".$speler."' class='speler_knop ".$disable."' type='submit' name='".$knop_naam."'; value='".$knop_tekst."'/></form></td>");
			//teken de hand
			$klikbare_kaarten = ($speler == $speler_aan_de_beurt && $aantal_spelers_in_stapel > 1 && $aantal_spelers_in_spel > 1 && !$stapel_ontploft) || ($doorgeef_fase && $doorgeef_stand);
			$hand->draw($klikbare_kaarten, $ingelogd_als_speler);
			echo "</tr>";
		}
		echo "</table></td>";
		//teken de stapel
		global $stapel_diepte;
		echo "<td class='boven rechts'><h2>Stapel</h2>";
		if($stapel_diepte>0){
			global $conn;
			for($diepte = 1; $diepte <= $stapel_diepte; $diepte++){
				echo "<br>";
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
			}
		}
		echo "</td></table>";
		//als er nog maar 1 persoon over is, is het spel voorbij, en moet je het spel kunnen beëindigen
		if($aantal_spelers_in_spel <= 1){			
			global $kamer_id;
			$nieuw_spel_knop_naam = "einde_spel".$this->spel_id;
			echo "<form method='post'><input class='delen' style='width:400px'; type='submit' name='".$nieuw_spel_knop_naam."'; value='Beëindig het spel'/></form>";
		}
		//kijk welke spelers er te wachten staan
		$aanwezige_spelers = bepaal_aanwezige_spelers();
		foreach($aanwezige_spelers as $speler){
			if(!in_array($speler, $this->spelers)){
				echo get_name_from_player($speler)." staat in de wachtrij<br>";
			}
		}
		//als je aan de beurt bent klinkt er een piep
		if($ingelogd && $ingelogde_speler == $speler_aan_de_beurt){
			speel_piep();
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
		//update($kamer_id); eigenlijk niet
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
		
		
		//kijk of de speler nu uit is
		$sql = "SELECT kaart_id FROM spellen_spelers_kaarten WHERE spel_id=".$this->spel_id." AND speler_id=".$speler;
		$result = $conn->query($sql);
		if(!$result){
			echo $conn->error."<br>".$sql."<br>";
		}
		$klaar = ($result->num_rows == 0);
		//kijk of er net ontploft is
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
				return;
			}
			
			//de persoon blijft aan de beurt en mag straks de stapel weghalen, maar dat staat ergens anders
		}
		
		//ook nog de beurt aanpassen
		else{
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
		$this->verwijder_doorgegeven_kaarten($speler);
	}
	function verwijder_geklikte_kaarten($speler){
		global $conn;
		$sql = "UPDATE aangeklikte_kaarten, spellen_spelers_kaarten SET aangeklikt=0 WHERE aangeklikte_kaarten.spel_id = spellen_spelers_kaarten.spel_id
				AND aangeklikte_kaarten.spel_id=".$this->spel_id." AND aangeklikte_kaarten.kaart_id=spellen_spelers_kaarten.kaart_id AND speler_id=".$speler;
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
	}
	
	function geef_door(){
		global $conn, $doorgeef_fase, $doorgeef_standen;
		if(!$doorgeef_fase){
			return False;
		}
		//geef de kaarten in de hand
		foreach($doorgeef_standen as $speler=>$doorgeef_stand){
			if($doorgeef_stand){
				$sql = "INSERT INTO spellen_spelers_kaarten
						SELECT spel_id, ".$speler.", kaart_id FROM doorgegeven_kaarten WHERE spel_id=".$this->spel_id." AND speler_id=(
							SELECT speler_id FROM doorgeef_informatie WHERE spel_id=".$this->spel_id." AND doorgeef_nummer=-".$doorgeef_stand."
						)
						";
				if(!$conn->query($sql)){
					echo $conn->error."<br>".$sql."<br>";
				}
			}
		}
		//haal het uit de doorgeefinformatie
		foreach($doorgeef_standen as $speler=>$doorgeef_stand){
			if($doorgeef_stand){
				$sql = "DELETE FROM doorgeef_informatie WHERE spel_id=".$this->spel_id." AND speler_id=".$speler;
				if(!$conn->query($sql)){
					echo $conn->error."<br>".$sql."<br>";
				}
			}
		}
		//zorg dat de persoon met de klaver 8 aan de beurt is
		$sql = "UPDATE spellen_beurt SET speler_id=(
					SELECT speler_id FROM spellen_spelers_kaarten WHERE spel_id=".$this->spel_id." AND kaart_id=8
				)
				WHERE spel_id=".$this->spel_id;
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
		//zorg dat de klaver 8 ook aangeklikt is, en verder niks
		$sql = "UPDATE aangeklikte_kaarten SET aangeklikt=0 WHERE spel_id=".$this->spel_id;
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
		$sql = "UPDATE aangeklikte_kaarten SET aangeklikt=1 WHERE spel_id=".$this->spel_id." AND kaart_id=8";
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
		global $kamer_id;
		update($kamer_id);
	}
	
	function verwijder_doorgegeven_kaarten($speler){
		global $conn;
		$sql = "DELETE FROM doorgegeven_kaarten WHERE spel_id=".$this->spel_id." AND kaart_id IN (
					SELECT kaart_id FROM spellen_spelers_kaarten WHERE spel_id=".$this->spel_id." AND speler_id=".$speler."
				)";
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
	
	//kijk of er doorgegeven moet worden
	$sql = "SELECT spel_id FROM spellen_archief WHERE kamer_id=".$kamer_id." AND TIMESTAMPDIFF(minute, eindtijd, current_TIMESTAMP)<30 ORDER BY eindtijd DESC;";
	$result = $conn->query($sql);
	if(!$result){
		echo $conn->error."<br>".$sql;
	}
	if($result->num_rows >= 1){
		//we moeten gaan doorgeven
		$row = $result->fetch_assoc();
		$vorig_spel = $row['spel_id'];
		//haal de spelers en standen uit de andere tabel
		$sql = "SELECT speler_id, stand FROM spellen_spelers WHERE spel_id=".$vorig_spel;
		$result = $conn->query($sql);
		if(!$result){
			echo $conn->error."<br>".$sql;
		}
		$vorige_standen = Array();
		while($row = $result->fetch_assoc()){
			$vorige_standen[$row['speler_id']] = $row['stand'];
		}
		asort($vorige_standen);//gesorteerd van eerst uit naar laatst uit
		//nu kijken we welke spelers in allebei de spellen zitten, in de goede volgorde
		$overeenkomst = Array();
		foreach($vorige_standen as $speler => $stand){
			if(in_array($speler, $spelers)){
				$overeenkomst[] = $speler;
			}
		}
		//vervolgens kijken we hoeveel doorgegeven moet worden
		$n = count($overeenkomst);
		$doorgeven = Array();
		switch($n){
			case 1:
				break;
			case 2:
				$doorgeven[$overeenkomst[0]] = 2;
				$doorgeven[$overeenkomst[1]] = -2;
				break;
			case 3:
				$doorgeven[$overeenkomst[0]] = 2;
				$doorgeven[$overeenkomst[2]] = -2;
				break;
			default:
				$doorgeven[$overeenkomst[0]] = 2;
				$doorgeven[$overeenkomst[1]] = 1;
				$doorgeven[$overeenkomst[$n-2]] = -1;
				$doorgeven[$overeenkomst[$n-1]] = -2;
				break;
		}
		//zet het in de database
		foreach($doorgeven as $speler => $nummer){
			$sql = "INSERT INTO doorgeef_informatie VALUES (".$spel_id.",".$speler.",".$nummer.")";
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql;
			}
		}
		//de klaver 8 is niet aangeklikt (en geen enkele kaart)
		$conn->query("UPDATE aangeklikte_kaarten SET aangeklikt = 0 WHERE spel_id=".$spel_id);
	}
		
	update($kamer_id);
	
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
	global $conn, $speler_aan_de_beurt, $stapel_diepte, $stapel_breedte, $stapel_waarde, $stapel_jokers, $aantal_spelers_in_spel, $aantal_spelers_in_stapel, $gepaste_spelers, $standen, $stapel_ontploft, $kamer_id, $doorgeef_fase, $doorgeef_standen;
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
	
	
	//kijk of het spel in de doorgeeffase zit
	$sql = "SELECT speler_id, doorgeef_nummer FROM doorgeef_informatie WHERE spel_id=".$spel_id;
	$result = $conn->query($sql);
	if(!$result){
		echo $conn->error."<br>".$sql."<br>";
	}
	$doorgeef_fase = ($result->num_rows > 0);
	$doorgeef_standen = Array();
	if($doorgeef_fase){
		while($row = $result->fetch_assoc()){
			$doorgeef_standen[$row['speler_id']] = $row['doorgeef_nummer'];
		}
	}
	foreach($spel->spelers as $speler){
		if(!isset($doorgeef_standen[$speler])){
			$doorgeef_standen[$speler] = 0;
		}
	}
}

?>