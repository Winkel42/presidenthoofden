<?php
$servername = "localhost";
$username = "root";
$dbname = "spellen";
$password = "";
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT verandering FROM check_veranderingen";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
	$aantal_veranderingen = $row['verandering'];
}
echo $aantal_veranderingen;
?>
