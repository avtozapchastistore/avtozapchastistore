<?php
$host = getenv('DB_HOST') ?: 'mysql-377602bb-avtozapchastistore-2b5f.i.aivencloud.com';
$port = (int)(getenv('DB_PORT') ?: 14526);
$user = getenv('DB_USER') ?: 'avnadmin';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: 'defaultdb';

$conn = new mysqli($host, $user, $pass, $name, $port);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
