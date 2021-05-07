<?php
include 'maak_verbinding.php';
$kamer_id = $_REQUEST['kamer_id'];
if(!array_search($kamer_id, Array(-1,0,1,2,3,4))){//kamer -1 is de thuispagina
	$kamer_id = -1;
}
$sql = "SELECT verandering FROM check_veranderingen WHERE kamer_id=".$kamer_id;
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
	$aantal_veranderingen = $row['verandering'];
}
echo $aantal_veranderingen;
?>
