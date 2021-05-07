<?php
function update($kamer_id){
	global $conn;
	$sql = "UPDATE check_veranderingen SET verandering = (verandering + 1)%1000 WHERE kamer_id=".$kamer_id;
	if(!$conn->query($sql)){
		echo $conn->error."<br>".$sql."<br>";
	}
	//we verhogen de eigen verandering ook, zodat we niet steeds hoeven te vernieuwen
	if($kamer_id >= 0){
		global $aantal_eigen_veranderingen;
		$aantal_eigen_veranderingen++;
	}
}
?>