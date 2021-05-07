<?php
$servername = "localhost";
$username = "presidenthoofden";
$dbname = "spellen";
$password = "presidenthoofden";

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>