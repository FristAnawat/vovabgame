<?php
$host = 'localhost';
$user = 'root'; // หรือชื่อผู้ใช้ที่คุณสร้าง
$pass = ''; // หรือรหัสผ่านที่คุณตั้ง
$dbname = 'oxford_game'; // ชื่อฐานข้อมูลที่คุณสร้าง

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>