<?php
function bepaal_waarde($kaart_id){
	$i = (int)$kaart_id/4;
	$alle_kaarten = Array('joker','joker','8','4','5','6','7','3','9','9','10','J','Q','K','A','2');
	return $alle_kaarten[$i];
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
		if(is_int($kaart){
		$kaart_waarde = bepaal_waarde($kaart)}
		else{
			$kaart_waarde = $kaart->waarde;
		}
		if($kaart_waarde != 'joker'){
			if($kaart_waarde != $waarde && $waarde != 'joker'){
				return False;
			}
			$waarde = $kaart_waarde;
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
?>