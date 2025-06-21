<?php
$hashed_password = '$2y$10$PiOGYScXpx.0d1r5qSYgXO1WBNU.bh6Xb1c8Lf5uOe8mFOCxTpoKe'; // ค่าจากฐานข้อมูล
$password_to_test = '12345';

if (password_verify($password_to_test, $hashed_password)) {
    echo "รหัสผ่านตรงกัน!";
} else {
    echo "รหัสผ่านไม่ตรงกัน!";
}
?>
