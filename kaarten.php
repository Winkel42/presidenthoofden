<?php
$kamer_id=1;

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
			return !in_array($waarde, Array('joker', '8'));
			break;
		case '6':
			return !in_array($waarde, Array('joker', '8', '4'));
			break;
		case '7':
			return in_array($waarde, Array('3', '4', '5', '6'));
			break;
		case '3':
			return !in_array($waarde, Array('joker', '8', '4', '6', '7'));
			break;
		case '9':
			return !in_array($waarde, Array('joker', '8', '4', '6', '7', '3'));
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
		//kijk of de kaart net aangeklikt werd		
		$knop_naam = "knop".$spel_id."a".$kaart_id;
		if(isset($_POST[$knop_naam])){
			$this->geklikt = 1 - $this->geklikt;
			$sql = "UPDATE aangeklikte_kaarten SET aangeklikt = ".$this->geklikt."  WHERE spel_id=".$spel_id." AND kaart_id=".$kaart_id.";";
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
		}
		//als het de klaver 8 is, is de kaart altijd aangeklikt
		if($this->kleur == 'klaver' && $this->waarde == '8'){
			$sql = "UPDATE aangeklikte_kaarten SET aangeklikt=1 WHERE spel_id=".$spel_id." AND kaart_id=".$kaart_id;
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			$this->geklikt = 1;
		}
	}
	function draw($klikbare_kaarten){
		if($this->waarde == "joker"){
			$kaart_tekst = "joker";
		}
		else{
			$kaart_tekst = $this->kleur." ".$this->waarde;
		}
		
		if($klikbare_kaarten){
			$knop_naam = "knop".$this->spel_id."a".$this->kaart_id;
			if($this->geklikt){
				$stijl = "background-color:green; width:100px";
			}
			else{
				$stijl = "width:100px";
			}
			echo("<input style='".$stijl."'; type='submit' name='".$knop_naam."'; value='".$kaart_tekst."'/>");
		}
		else{
			echo("<button style='width:100px'; type='button' disabled>".$kaart_tekst."</button>");
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
	function draw($klikbare_kaarten){//weergeef alle kaarten in de hand
		if($klikbare_kaarten){
			echo "<form method='post'>";
		}
		foreach($this->kaarten as $kaart){
			$kaart->draw($klikbare_kaarten);
		}
		if($klikbare_kaarten){
			echo "</form>";
		}
		else{
			echo "</br>";
		}

			
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
		global $speler_aan_de_beurt, $stapel_diepte, $aantal_spelers_in_stapel, $gepaste_spelers, $aantal_spelers_in_spel, $standen, $stapel_ontploft;
		foreach($this->spelers as $speler){
			$naam = get_name_from_player($speler);
			$hand = $this->bepaal_hand($speler);
			echo $naam;
			echo "<br>";			
			//creeer de grote knop
			$stand = $standen[$speler];
			$gepast = $gepaste_spelers[$speler];
			//eerst kijken we of de knop aan moet staan
			// gevallen waarin de knop aan moet staan:
			// 1.speler is aan de beurt, niet gepast of al uit en de stapel is niet leeg. Ofwel passend, ofwel om de stapel weg te halen.
			// 2.speler is aan de beurt, niet uit, stapel is leeg en de speler heeft een legale variatie aan kaarten aangeklikt.
			// 3.speler is aan de beurt, al gepast, maar er is niemand meer over.
			// 4.speler is aan de beurt en stapel is net ontploft
			$disable = ' disabled ';
			if($speler == $speler_aan_de_beurt && !$gepast && $stand == 0){
				$disable = '';
				$hand = $this->bepaal_hand($speler);
				$aangeklikt = $hand->bepaal_aangeklikte_kaarten();
				if($stapel_diepte == 0 && !is_legaal($aangeklikt)){
					$disable = ' disabled '; //in dit geval mag je niet op Speel klikken
				}
			}			
			if($stand > 0){
				$disable = ' disabled ';
			}
			if($speler == $speler_aan_de_beurt && $stapel_ontploft){
				$disable = ' ';
			}
			if($speler == $speler_aan_de_beurt && $gepast && $aantal_spelers_in_stapel == 0 && $stand == 0){
				$disable = ''; //dit is om de stapel weg te doen nadat degene voor je uit is gegaan
			}
			if($aantal_spelers_in_spel == 1){
				$disable = ' disabled ';
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
			echo("<form method='post'><input style='width:200px'; type='submit' name='".$knop_naam."'; value='".$knop_tekst."'".$disable."/></form>");
			//teken de hand
			$klikbare_kaarten = ($speler == $speler_aan_de_beurt && $aantal_spelers_in_stapel > 1 && $aantal_spelers_in_spel > 1 && !$stapel_ontploft);
			$hand->draw($klikbare_kaarten);
			echo "<br>";
		}
		//teken de rest van de stapel
		global $stapel_diepte;
		echo "Stapel<br>";
		if($stapel_diepte>0){
			global $conn;
			$sql = "SELECT kaart_id, diepte FROM spel_stapel WHERE spel_id = ".$this->spel_id;
			$result = $conn->query($sql);
			if($result && $result->num_rows>0){
				$vorige_diepte = 0;
				while($row = $result->fetch_assoc()){
					$kaart_id = $row['kaart_id'];
					$diepte = $row['diepte'];
					if($diepte>$vorige_diepte){
						echo"<br>";
					}
					$vorige_diepte = $diepte;
					$kaart = new Kaart($this->spel_id, $kaart_id);
					$kaart->draw(FALSE);
				}
			}
			else{
				echo $conn->error."<br>".$sql."<br>";
			}
		}
		//als er nog maar 1 persoon over is, is het spel voorbij, en moet je opnieuw kunnen beginnen
		if($aantal_spelers_in_spel <= 1){			
			global $kamer_id;
			$nieuw_spel_knop_naam = "opnieuwdelen".$kamer_id;
			echo "<form method='post'><input style='width:400px'; type='submit' name='".$nieuw_spel_knop_naam."'; value='Delen'/></form>";
		}
		 
		
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
	}
	function speel($speler, $kaarten){
		global $stapel_diepte, $conn, $aantal_overgebleven_spelers;
		$stapel_diepte++;
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
	//kijk welke spelers er mee willen doen
	$spelers = Array();
	$sql = "SELECT speler_id FROM kamers_spelers WHERE kamer_id=".$kamer_id;
	$result = $conn->query($sql);
	if(!$result){
		echo $conn->error."<br>".$sql."<br>";
	}
	if($result->num_rows<=1){
		echo "te weinig spelers";
	}
	if($result->num_rows>=1){
		while($row = $result->fetch_assoc()){
			$spelers[] = $row['speler_id'];
		}
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
	$sql = "INSERT INTO actieve_spellen  VALUES(".$kamer_id.",".$spel_id.");";
	if(!$conn->query($sql)){
		echo $conn->error."<br>".$sql."<br>";
	}	
	//creer de kaarten in de andere database
	foreach(range(0,63) as $kaart_id){
		$sql = "INSERT INTO aangeklikte_kaarten VALUES(".$spel_id.",".$kaart_id.",0)";
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
	global $conn, $speler_aan_de_beurt, $stapel_diepte, $stapel_breedte, $stapel_waarde, $stapel_jokers, $aantal_spelers_in_spel, $aantal_spelers_in_stapel, $gepaste_spelers, $standen, $stapel_ontploft;
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
	global $kamer_id;
	$nieuw_spel_knop_naam = "opnieuwdelen".$kamer_id;
	echo "<form method='post'><input style='width:400px'; type='submit' name='".$nieuw_spel_knop_naam."'; value='Delen'/></form>";
}


function ververs(){
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
	}
	else{
		$nieuw_spel_knop_naam = "opnieuwdelen".$kamer_id;
		if(isset($_POST[$nieuw_spel_knop_naam]) && $_POST[$nieuw_spel_knop_naam] != "doe niets"){
			begin_spel();
			return ververs();
		}
		teken_zonder_spel();
		return False;
	}
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
	if(isset($_POST[$knop_naam]) && $_POST[$knop_naam]!="doe niets"){
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
				//in de database zetten dat de speler gepast heeft
				$sql = "UPDATE spellen_spelers SET gepast = 1 WHERE spel_id=".$spel_id." AND speler_id=".$speler_aan_de_beurt;
				if(!$conn->query($sql)){
					echo $conn->error."<br>".$sql."<br>";
				}
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
					$sql = "UPDATE spellen_spelers SET stand = ".count($spel->spelers)." WHERE spel_id=".$spel_id." AND speler_id=".$speler_aan_de_beurt;
					if(!$conn->query($sql)){
						echo $conn->error."<br>".$sql."<br>";
					}
					//dan moet ook alvast de volgende aan beurt
					$spel->volgende_aan_de_beurt();
				}			
				//de speler heeft de stapel weggelegd
				$spel->nieuwe_stapel($speler_aan_de_beurt);
				break;
		}
		$_POST[$knop_naam]="doe niets";
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
	
	//kijk of er een nieuw spel wordt begonnen
	$nieuw_spel_knop_naam = "opnieuwdelen".$kamer_id;
	if(isset($_POST[$nieuw_spel_knop_naam]) && $_POST[$nieuw_spel_knop_naam] != "doe niets"){
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
		begin_spel();
		$_POST[$nieuw_spel_knop_naam] = "doe niets";
		return ververs();
	}
	
	
	
	$spel->teken();
}




ververs();


	

?>
