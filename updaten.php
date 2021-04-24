<?php
function update($kamer_id){
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