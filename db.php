<?php
$conn = new mysqli("localhost", "root", "", "oxford_game");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
