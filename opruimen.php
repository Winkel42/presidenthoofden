<?php

function spel_opruimen($kamer_id, $spel_id){
	global $conn;
	$conn->query("INSERT INTO spellen_archief VALUES (".$kamer_id.",".$spel_id.",
					(SELECT begintijd FROM actieve_spellen WHERE spel_id=".$spel_id."), CURRENT_TIMESTAMP)");
	$conn->query("DELETE FROM actieve_spellen WHERE spel_id=".$spel_id);
	$conn->query("DELETE FROM spellen_beurt WHERE spel_id=".$spel_id);
	$conn->query("DELETE FROM spel_stapel WHERE spel_id=".$spel_id);
	$conn->query("DELETE FROM spellen_spelers_kaarten WHERE spel_id=".$spel_id);
	$conn->query("DELETE FROM aangeklikte_kaarten WHERE spel_id=".$spel_id);
	$conn->query("DELETE FROM doorgegeven_kaarten WHERE spel_id=".$spel_id);
	$conn->query("DELETE FROM doorgeef_informatie WHERE spel_id=".$spel_id);
	//alleen spellen_spelers moet blijven want daar staat de stand in
}

function ruim_oude_spellen_op(){
	global $conn;
	$sql = "SELECT kamer_id, spel_id FROM actieve_spellen WHERE TIMESTAMPDIFF(minute, begintijd, CURRENT_TIMESTAMP)>=180";
	$result = $conn->query($sql);
	if(!$result){
		echo $conn->error."<br>".$sql."<br>";
	}
	while($row = $result->fetch_assoc()){
		$kamer_id = $row['kamer_id'];
		$spel_id = $row['spel_id'];
		spel_opruimen($kamer_id, $spel_id);
	}
}

function haal_oude_spelers_uit_kamers(){
	global $conn;
	$sql = "DELETE FROM kamers_spelers WHERE TIMESTAMPDIFF(minute, binnengekomen_tijd, CURRENT_TIMESTAMP)>=180 AND kamer_id NOT IN (
				SELECT kamer_id FROM spellen_archief WHERE TIMESTAMPDIFF(minute, eindtijd, CURRENT_TIMESTAMP)<=180
			)";
	if(!$conn->query($sql)){
		echo $conn->error."<br>".$sql."<br>";
	}
}