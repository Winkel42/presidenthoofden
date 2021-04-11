<?php

class Kaart {
	private $kleur;
	private $waarde;
	function __construct($kleur, $waarde){
		$this->kleur = $kleur;
		$this->waarde = $waarde;
	}
	function get_kleur(){
		return $this->kleur;
	}
	function get_waarde(){
		return $this->waarde;
	}
	function draw(){
		echo $this->kleur;
		echo " ";
		echo $this->waarde;
		echo "   ";
	}
}

$presidenthoofdstok = Array(new Kaart("klaver","negen"), new Kaart("klaver","negen"), new Kaart("klaver","negen"), new Kaart("ruiten","negen"));
for($i=0;$i<8;$i++){
	$presidenthoofdstok[] = new Kaart("nvt","joker");
}
foreach(array("klaver","ruiten","harten","schoppen") as $kleur){
	foreach(array("8","4","5","6","7","3","9","10","J","Q","K","A","2") as $waarde){
		$presidenthoofdstok[] = new Kaart($kleur, $waarde);
	}
}

//een hand is wat één speler in één spel vastheeft
class Hand {
	private $kaarten;
	private $speler;
	private $spel;
	function __construct($kaarten, $speler, $spel){
		$this->kaarten = $kaarten;
		$this->speler = $speler;
		$this->spel = $spel;
	}
	function draw(){//weergeef alle kaarten in de hand
		for($i=0; $i<count($this->kaarten); $i++){
			$kaart = $this->kaarten[$i];
			$kaart->draw();
		}
	}
	function new_card($kaart){
		$this->kaarten[] = $kaart;
	}
	
}
	
class Speler {
	private $naam;
	function __construct($naam){
		$this->naam = $naam;
	}
	function get_name(){
		return $this->naam;
	}
}

class Spel {
	private $spelers;
	private $handen;//elke speler heeft een hand
	function __construct($spelers){
		$this->spelers = $spelers;
		$this->handen = array();
		foreach($spelers as $speler){
			$this->handen[] = new Hand(array(), $speler, $this);
		}
	}
	function get_hand($speler){//vind de hand die hoort bij een bepaalde speler in dit spel
		$i = array_search($speler, $this->handen);
		return $this->handen[$i];
	}
}

$spelers = array(new Speler("Jeroen"), new Speler("Amber"), new Speler("Aldo"));

function maak_geschud_stok(){
	global $presidenthoofdstok;
	$stok = $presidenthoofdstok;
	shuffle($stok);
	return $stok;
}


function begin_spel($spelers){
	$spel = new Spel($spelers);
	//nu gaan we de kaarten uitdelen
	$geschud_stok = maak_geschud_stok();
	$index_speler = rand(0,count($spelers)-1);
	for($i=0;$i<64;$i++){
		$hand = $spel->get_hand($spelers[$index_speler]);
		$hand->new_card($geschud_stok[$i]);
		$index_speler++;
		$index_speler = ($index_speler+1)%count($spelers);
	}
	foreach($spelers as $speler){
		echo $speler->get_name();
		echo "    ";
		$hand = $spel->get_hand($speler);
		$hand->draw();
		echo "<br>";
	}
}
	
begin_spel($spelers);

?>
