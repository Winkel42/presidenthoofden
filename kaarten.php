<?php

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



class Kaart {
	private $kaart_id;
	public $kleur;
	public $waarde;
	private $geklikt;
	function __construct($spel_id, $kleur, $waarde){
		global $conn;
		$this->kleur = $kleur;
		$this->waarde = $waarde;
		$this->kaart_id = $spel_id."a".spl_object_id($this); //zorgt dat het id uniek is, ook over alle andere spellen heen
		$this->geklikt = 0;
		//kijk of de kaart al aangeklikt was
		$sql = "SELECT aangeklikt FROM aangeklikte_kaarten WHERE kaart_id='".$this->kaart_id."';";
		$result = $conn->query($sql);
		if($result && $result->num_rows>0){
			while($row = $result->fetch_assoc()){
				if($row['aangeklikt']){
					$this->geklikt = 1;
				}
			}
		}
		else{
			//stop de kaart in de database
			$sql = "INSERT INTO aangeklikte_kaarten VALUES('".$this->kaart_id."',0);";
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
		}
		//kijk of de kaart net aangeklikt werd		
		$knop_naam = "knop".$this->kaart_id;
		if(isset($_POST[$knop_naam])){
			$this->geklikt = 1 - $this->geklikt;
			$sql = "UPDATE aangeklikte_kaarten SET aangeklikt = '".$this->geklikt."'  WHERE kaart_id='".$this->kaart_id."';";
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
		}
	}
	function draw($aan_de_beurt){
		if($this->waarde == "joker"){
			$kaart_tekst = "joker";
		}
		else{
			$kaart_tekst = $this->kleur." ".$this->waarde;
		}
		
		if($aan_de_beurt){
			$knop_naam = "knop".$this->kaart_id;
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
	private $kaarten;
	private $speler;
	private $spel;
	function __construct($speler, $spel){
		$this->speler = $speler;
		$this->spel = $spel;
		$this->kaarten = Array();
		$this->haal_hand_uit_database();
	}
	function draw(){//weergeef alle kaarten in de hand
		//kijk of de speler aan de beurt is
		global $speler_aan_de_beurt;
		$aan_de_beurt = ($this->speler == $speler_aan_de_beurt);
		if($aan_de_beurt){
			echo "<form method='post'>";
		}
		for($i=0; $i<count($this->kaarten); $i++){
			$kaart = $this->kaarten[$i];
			$kaart->draw($aan_de_beurt);
		}
		if($aan_de_beurt){
			echo "</form>";
		}
		else{
			echo "</br>";
		}

			
	}
	
	function haal_hand_uit_database(){
		global $conn;
		$sql = "SELECT kleur, waarde FROM spellen_spelers_kaarten WHERE spel_id='".($this->spel)->spel_id."' AND speler_id='".$this->speler."';";
		$result = $conn->query($sql);
		$this->kaarten = Array();
		if($result){
			while($row = $result->fetch_assoc()){
				$this->kaarten[] = new Kaart(($this->spel)->spel_id,$row["kleur"],$row["waarde"]);
			}
		}
	}
	
	
}

//vind de naam van een speler uit de database
function get_name_from_player($speler_id){
	global $conn;
	$sql = "SELECT speler_naam FROM spelers WHERE speler_id=".$speler_id;
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()){
		return $row["speler_naam"];
	}
}
	

class Spel {
	public $spel_id;
	private $spelers;
	private $handen;//elke speler heeft een hand
	function __construct($spel_id, $spelers){
		$this->spelers = $spelers;
		$this->spel_id = $spel_id;
		$this->handen = array();
		foreach($spelers as $speler){
			$this->handen[] = new Hand($speler, $this);
		}
	}
	function get_hand($speler){//vind de hand die hoort bij een bepaalde speler in dit spel
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
		foreach($this->spelers as $speler){
			$naam = get_name_from_player($speler);
			$hand = $this->get_hand($speler);
			echo $naam;
			echo "<br>";			
			//creeer de grote knop
			global $speler_aan_de_beurt;
			global $stapel_diepte;
			$gepast = heeft_gepast($this->spel_id, $speler);
			if($speler == $speler_aan_de_beurt){
				$disable = '';
			}
			else{
				$disable =' disabled ';
			}
			if($speler == $speler_aan_de_beurt && $stapel_diepte == 0){
				$knop_tekst = "Speel";
			}
			elseif($gepast){
				$knop_tekst = "Gepast";
			}
			else{
				$knop_tekst = "Pas";
			}
			$knop_naam = "grote_knop_van_".$speler;
			echo("<form method='post'><input style='width:200px'; type='submit' name='".$knop_naam."'; value='".$knop_tekst."'".$disable."/></form>");
			//teken de hand
			$hand->draw();
			echo "<br>";
		}
		//teken de rest van de stapel
		global $stapel_diepte;
		echo "Stapel<br>";
		if($stapel_diepte>0){
			global $conn;
			$sql = "SELECT waarde, kleur, diepte FROM spel_stapel WHERE spel_id = ".$this->spel_id;
			$result = $conn->query($sql);
			if($result && $result->num_rows>0){
				$vorige_diepte = 0;
				while($row = $result->fetch_assoc()){
					$waarde = $row['waarde'];
					$kleur = $row['kleur'];
					$diepte = $row['diepte'];
					if($diepte>$vorige_diepte){
						echo"<br>";
					}
					$vorige_diepte = $diepte;
					$kaart = new Kaart($this->spel_id, $kleur, $waarde);
					$kaart->draw(FALSE);
				}
			}
			else{
				echo $conn->error."<br>".$sql."<br>";
			}
		}
	}
	
	function nieuwe_stapel($speler){
		echo $speler;
		global $conn;
		//leeg de stapel en update de beurtvolgorde
		$sql = "DELETE FROM spel_stapel";
		if(!$conn->query($sql)){
			echo $conn->error."<br>".$sql."<br>";
		}
		$i=array_search($speler, $this->spelers);
		echo "het speler id is ".$i;
		for($j=0;$j<count($this->spelers);$j++){
			$sql = "UPDATE spellen_spelers SET wanneer_beurt=".$j." WHERE spel_id=".$this->spel_id." AND speler_id=".$this->spelers[$i];
			if(!$conn->query($sql)){
				echo $conn->error."<br>".$sql."<br>";
			}
			$i = ($i+1)%count($this->spelers);
		}
	}
}


function maak_geschud_stok($spel_id){
	$presidenthoofdstok = Array(new Kaart($spel_id, "klaver","9"), new Kaart($spel_id, "klaver","9"), new Kaart($spel_id, "klaver","9"), new Kaart($spel_id, "ruiten","9"));
	for($i=0;$i<8;$i++){
		$presidenthoofdstok[] = new Kaart($spel_id, "nvt","joker");
	}
	foreach(array("klaver","ruiten","harten","schoppen") as $kleur){
		foreach(array("8","4","5","6","7","3","9","10","J","Q","K","A","2") as $waarde){
			$presidenthoofdstok[] = new Kaart($spel_id, $kleur, $waarde);
		}
	}
	shuffle($presidenthoofdstok);
	return $presidenthoofdstok;
}
	

function begin_spel($speler_ids){
	$kamer_id = 1;
	global $conn;
	//creeer het goede id voor dit spel
	$sql = "SELECT MAX(spel_id) FROM spellen_spelers;";
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()){
		$spel_id = $row["MAX(spel_id)"]+1;
	}
	//creeer het spel in de database
	$sql = "INSERT INTO actieve_spellen (kamer_id, spel_id) VALUES('".$kamer_id."','".$spel_id."');";
	if($conn->query($sql) == FALSE){
		echo 'database updaten mislukt';
		echo $conn->error;
	}	
	
	echo $spel_id;
	
	//nu gaan we de kaarten uitdelen
	$geschud_stok = maak_geschud_stok($spel_id);
	$index_speler = rand(0,count($speler_ids)-1);
	for($i=0;$i<64;$i++){
		$kaart = $geschud_stok[$i];
		//kijk of het de klaver acht is
		if($kaart->kleur == "klaver" && $kaart->waarde =="8"){
			$index_begin_speler = $index_speler;
		}		
		$speler_id = $speler_ids[$index_speler];
		$sql = "INSERT INTO spellen_spelers_kaarten (spel_id, speler_id, kleur, waarde) VALUES('".$spel_id."','".$speler_id."','".$kaart->kleur."','".$kaart->waarde."');";
		if($conn->query($sql) == FALSE){
			echo 'database updaten mislukt';
			echo $conn->error;
		}
		$index_speler = ($index_speler+1)%count($speler_ids);
	}
	//zet de beurten in de database
	$i = $index_begin_speler;
	for($j=0;$j<count($speler_ids);$j++){
		$speler_id = $speler_ids[$i];
		$sql = "INSERT INTO spellen_spelers (spel_id, speler_id, wanneer_beurt, gepast) VALUES('".$spel_id."','".$speler_id."',".$j.", FALSE);";
		if($conn->query($sql) == FALSE){
			echo $conn->error."<br>".$sql;
		}		
		$i = ($i+1)%count($speler_ids);
	}
}
	
	
$speler_ids = Array(1,2,3);
//begin_spel($speler_ids);

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

function heeft_gepast($spel_id, $speler){
	//kijk of een speler al gepast heeft
	global $conn;
	$sql = "SELECT wanneer_beurt FROM spellen_spelers WHERE spel_id = ".$spel_id." AND speler_id=".$speler." AND wanneer_beurt<0";
	$result = $conn->query($sql);
	if($result){
		if($result->num_rows>0){
			return True;
		}
		else{
			return FALSE;
		}
	}
	else{
		echo $conn->error."<br>".$sql;
	}
}
		


function ververs(){
	global $conn;
	$kamer_id = 1;
	//eerst vinden we het juiste spel_id
	$sql = "SELECT spel_id FROM actieve_spellen WHERE kamer_id='".$kamer_id."';";
	$result = $conn->query($sql);
	if($result){
		while($row = $result->fetch_assoc()){
			$spel_id = $row['spel_id'];
		}
	}
	else{
		echo "hier gebeurt niks";
		return;
	}
	
	echo "het spel id is ".$spel_id."<br>";
	$spel = maak_spel_vanuit_database($spel_id);
	
	
	
	//bepaal een aantal globale variabelen die de stand van het spel aangeven
	global $speler_aan_de_beurt, $stapel_diepte, $stapel_breedte, $stapel_waarde, $stapel_jokers;
	//wie is er aan de beurt
	$sql = "SELECT speler_id FROM spellen_spelers WHERE spel_id=".$spel_id." AND wanneer_beurt=0";
	$result = $conn->query($sql);
	if($result and $result->num_rows>0){
		while($row = $result->fetch_assoc()){
			$speler_id = $row['speler_id'];
		}
	}
	else{
		echo "error<br>".$conn->error."<br>".$sql;
	}
	$speler_aan_de_beurt = $speler_id;
	//hoe diep is de stapel
	$sql = "SELECT MAX(diepte) FROM spel_stapel WHERE spel_id = ".$spel_id;
	$result = $conn->query($sql);
	if($result && $result->num_rows>0){
		while($row = $result->fetch_assoc()){
			$stapel_diepte = $row['MAX(diepte)'];
			if(!$stapel_diepte){
				$stapel_diepte = 0;
			}
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
		$sql = "SELECT waarde FROM spel_stapel WHERE spel_id = ".$spel_id." AND diepte = ".$relevante_diepte;
		$result = $conn->query($sql);
		if($result && $result->num_rows>0){
			$stapel_waarde = "joker";
			while($row = $result->fetch_assoc()){
				$kaart_waarde = $row['waarde'];
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
		$sql = "SELECT waarde FROM spel_stapel WHERE spel_id = ".$spel_id." AND diepte = ".$stapel_diepte;
		$result = $conn->query($sql);
		if($result && $result->num_rows>0){
			$stapel_breedte = $result->num_rows;
		}
		else{
			echo $conn->error."<br>".$sql;
		}
	}
	
	//ligt er een joker bovenop
	if($stapel_diepte>0){
		$stapel_jokers = FALSE;
		$sql = "SELECT waarde FROM spel_stapel WHERE spel_id = ".$spel_id." AND diepte = ".$stapel_diepte;
		$result = $conn->query($sql);
		if($result && $result->num_rows>0){
			while($row = $result->fetch_assoc()){
				$kaart_waarde = $row['waarde'];
				if($kaart_waarde=="joker"){
					$stapel_jokers = TRUE;
				}
			}
		}
		else{
			echo $conn->error."<br>".$sql."<br>";
		} 
	}
	
	//test:
	echo "de diepte is ".$stapel_diepte.", de breedte is ".$stapel_breedte.", de waarde is ".$stapel_waarde." en er zijn jokers: ".$stapel_jokers."<br>";
	
	$spel->teken();
	
	//ook het aantal overgebleven spelers is belangrijk in wat volgt
	$sql = "SELECT speler_id FROM spellen_spelers WHERE spel_id=".$spel_id." AND wanneer_beurt>=0";
	$result = $conn->query($sql);
	if($result){
		$aantal_overgebleven_spelers = $result->num_rows;
	}
	else{
		echo $conn->error."<br>".$sql."<br>";
	}
		
	
	//nu moeten we nog kijken, of er iets moet gebeuren
	$knop_naam = "grote_knop_van_".$speler_aan_de_beurt;
	if(isset($_POST[$knop_naam])){
		switch($_POST[$knop_naam]){
			case "Speel":
				//de speler heeft gespeeld
				//nu moeten we uitvinden, welke kaarten gespeeld zijn
				
				break;
			case "Pas":
				//de speler heef gepast
				//als er maar één persoon over is, mag die beginnen
				if($aantal_overgebleven_spelers==2){
					$sql = "SELECT speler_id FROM spellen_spelers WHERE spel_id=".$spel_id." AND wanneer_beurt>=0";
					$result = $conn->query($sql);
					if($result and $result->num_rows>0){
						while($row = $result->fetch_assoc()){
							$overgebleven_speler = $row['speler_id'];
						}
					}
					else{
						echo $conn->error."<br>".$sql."<br>";
					}
					$spel->nieuwe_stapel($overgebleven_speler);
				}
				else{
					//beurt aanpassen in database
					$sql = "UPDATE spellen_spelers SET wanneer_beurt = wanneer_beurt-1 WHERE spel_id=".$spel_id;
					if(!$conn->query($sql)){
						echo $conn->error."<br>".$sql."<br>";
					}
				}
				
				
				break;
			case "Stapel weg":
				//de speler heeft de stapel weggelegd
				break;
		}
	}
	
}




ververs();

//$conn->query("INSERT INTO `spel_stapel` (`spel_id`, `kleur`, `waarde`, `diepte`) VALUES ('64', 'schoppen', '4', '1');");
?>
