<?php
$password = "admin123";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash pour 'admin123': " . $hash;
?> 