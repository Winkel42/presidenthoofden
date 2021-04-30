<?php

function spel_opruimen($spel_id){
	global $conn;
	$conn->query("DELETE FROM actieve_spellen WHERE spel_id=".$spel_id);
	$conn->query("DELETE FROM spellen_beurt WHERE spel_id=".$spel_id);
	$conn->query("DELETE FROM spel_stapel WHERE spel_id=".$spel_id);
	$conn->query("DELETE FROM spellen_spelers_kaarten WHERE spel_id=".$spel_id);
	$conn->query("DELETE FROM aangeklikte_kaarten WHERE spel_id=".$spel_id);
	//alleen spellen_spelers moet blijven want daar staat de stand in
}

function ruim_oude_spellen_op(){
	global $conn;
	$sql = "SELECT spel_id FROM actieve_spellen WHERE TIMESTAMPDIFF(hour, begintijd, CURRENT_TIMESTAMP)>=24";
	$result = $conn->query($sql);
	if(!$result){
		echo $conn->error."<br>".$sql."<br>";
	}
	while($row = $result->fetch_assoc()){
		$spel_id = $row['spel_id'];
		spel_opruimen($spel_id);
	}
}
