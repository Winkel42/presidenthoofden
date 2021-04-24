<?php
$servername = "localhost";
$username = "root";
$dbname = "spellen";
$password = "";
$conn = new mysqli($servername, $username, $password, $dbname);
$kamer_id = $_REQUEST['kamer_id'];
if(!array_search($kamer_id, Array(-1,0,1,2,3))){//kamer -1 is de thuispagina
	$kamer_id = -1;
}
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT verandering FROM check_veranderingen WHERE kamer_id=".$kamer_id;
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
	$aantal_veranderingen = $row['verandering'];
}
echo $aantal_veranderingen;
?>
