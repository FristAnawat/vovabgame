<?php
session_start();
$_SESSION['score'] = 0; // รีเซ็ตคะแนน
$_SESSION['lives'] = 3; // รีเซ็ตหัวใจเป็น 3
header("Location: index.php"); // สมมติว่าไฟล์หลักชื่อ index.php
exit();
?>