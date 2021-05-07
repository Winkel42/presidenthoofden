<?php

include 'lees_en_zet_koekjes.php';
include 'updaten.php';
include 'vernieuwscript.php';
include 'algemene_functies_en_klasses_voor_een_spel.php';
include 'maak_verbinding.php';

$aantal_eigen_veranderingen = 0;

function teken_algemene_dingen_voor_spel(){
	global $kamer_id, $stapel_breedte, $stapel_diepte, $stapel_waarde, $stapel_jokers;
	$log_in_informatie = bepaal_log_in_informatie();
	$ingelogd = $log_in_informatie['ingelogd'];
	vernieuw_functie($kamer_id);
	if($ingelogd){
		$ingelogde_speler = $log_in_informatie['ingelogde_speler'];
		teken_script_kaart_klik($kamer_id, $ingelogde_speler);
	}
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
	if($ingelogd){
		echo "<input type='submit' formaction='instellingen.php' class='menu_knop' value='Instellingen'>";
	}
	echo "<input type='submit' class='menu_knop' formaction='index.php'; value='Terug naar thuispagina'></form>";
	echo "</p>";
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
		global $conn, $speler_aan_de_beurt, $stapel_diepte, $stapel_breedte, $stapel_waarde, $stapel_jokers, $aantal_spelers_in_spel, $aantal_spelers_in_stapel, $gepaste_spelers, $standen, $doorgeef_fase, $doorgeef_standen;
		bepaal_globale_variabelen($spel);	
		
		//test:
		//echo "de diepte is ".$stapel_diepte.", de breedte is ".$stapel_breedte.", de waarde is ".$stapel_waarde." en er zijn jokers: ".($stapel_jokers?"ja":"nee")."<br> er zijn ".$aantal_spelers_in_spel." spelers over<br>";
		
		//iedereen die niet aan de beurt is heeft geen kaarten meer aangeklikt tenzij doorgeeffase
		foreach($spel->spelers as $speler){
			if(!$doorgeef_fase && $speler != $speler_aan_de_beurt){
				$spel->verwijder_geklikte_kaarten($speler);
			}
		}	
		
		//nu moeten we nog kijken, of er iets moet gebeuren, omdat iemand op een knop gedrukt heeft
		$knop_naam = "grote_knop_van_".$speler_aan_de_beurt;
		if(isset($_POST[$knop_naam]) && $_POST[$knop_naam] != "Geef door"){
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
					//de doorgegeven kaarten worden hierna niet meer aangegeven
					$spel->verwijder_doorgegeven_kaarten($speler_aan_de_beurt);			
					//beurt aanpassen in database
					$spel->volgende_aan_de_beurt();
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
						//niet updaten zodat mensen het nog kunnen zien
						//dan moet ook alvast de volgende aan beurt
						$spel->volgende_aan_de_beurt();
					}			
					//de speler heeft de stapel weggelegd
					$spel->nieuwe_stapel($speler_aan_de_beurt);
					break;
				default:
					//enige mogelijkheid is voor doorgeven maar dat komt later
				
			}		
			$_POST = Array();
			return ververs();
		}
		//kijk of iemand doorgeeft
		if($doorgeef_fase){
			foreach($spel->spelers as $speler){
				$knop_naam = "grote_knop_van_".$speler;
				if(isset($_POST[$knop_naam]) && $_POST[$knop_naam] == "Geef door"){
					//kijk of de speler al heeft doorgegeven
					$sql = "SELECT speler_id FROM doorgegeven_kaarten WHERE spel_id=".$spel_id." AND speler_id=".$speler;
					$result = $conn->query($sql);
					if(!$result){
						echo $conn->error."<br>".$sql."<br>";
					}
					if(!($result->num_rows)){
						//de persoon probeert kaarten door te geven, we moeten kijken of het valide is
						$hand = $spel->bepaal_hand($speler);
						$aangeklikte_kaarten = $hand->bepaal_aangeklikte_kaarten();
						$doorgeef_stand = $doorgeef_standen[$speler];
						$legale_doorgeef = is_doorgeef_legaal($hand->kaarten, $aangeklikte_kaarten, $doorgeef_stand);
						if($legale_doorgeef){
							foreach($aangeklikte_kaarten as $kaart){
								$sql = "INSERT INTO doorgegeven_kaarten VALUES (".$spel_id.",".$speler.",".$kaart->kaart_id.")";
								if(!$conn->query($sql)){
									echo $conn->error."<br>".$sql."<br>";
								}
								$sql = "DELETE FROM spellen_spelers_kaarten WHERE spel_id=".$spel_id." AND speler_id=".$speler." AND kaart_id=".$kaart->kaart_id;
								if(!$conn->query($sql)){
									echo $conn->error."<br>".$sql."<br>";
								}
							}
							//kijk of iedereen heeft doorgegeven
							$sql = "SELECT speler_id FROM doorgeef_informatie WHERE spel_id=".$spel_id." AND speler_id NOT IN (SELECT speler_id FROM doorgegeven_kaarten WHERE spel_id=".$spel_id.")";
							$result = $conn->query($sql);
							if(!$result){
								echo $conn->error."<br>".$sql."<br>";
							}
							if(!($result->num_rows)){
								$spel->geef_door();
							}
						}
						else{
							echo "<script>alert('Geef goeie shit door');</script>";
						}
						$_POST = Array();
						return ververs();
					}
				}
			}
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
		
		//kijk of het spel beëindigd wordt
		$eind_spel_knop_naam = "einde_spel".$spel_id;
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
			//vergeet niet om het huidige spel uit de actieve spellen en andere databases te verwijderen
			spel_opruimen($kamer_id, $spel_id);
			update(-1);//we updaten expres niet in de kamer zelf zodat andere spelers nog even kunnen kijken
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