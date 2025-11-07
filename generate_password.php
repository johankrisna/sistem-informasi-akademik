<?php
// Script untuk generate password hash
$password = 'password'; // Ganti dengan password yang diinginkan
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Password: " . $password . "<br>";
echo "Hashed: " . $hashed_password . "<br>";

// Verifikasi
if (password_verify($password, $hashed_password)) {
    echo "Verification: OK";
} else {
    echo "Verification: FAILED";
}
?>