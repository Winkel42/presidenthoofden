<?php

include 'maak_verbinding.php';

$kamer_id = $_GET['kamer_id'];

$sql = "SELECT kaart_id FROM actieve_spellen A, spellen_spelers_kaarten B WHERE A.spel_id=B.spel_id AND kamer_id=".$kamer_id;
$result = $conn->query($sql);

$voortgang = 64-$result->num_rows;
echo $voortgang;

?>