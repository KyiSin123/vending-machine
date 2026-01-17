<?php
// config.php 
if (!isset($pdo)) {
    try {
        $dsn = 'mysql:host=localhost;dbname=vending_machine;charset=utf8';
        $username = 'root';
        $password = '';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
    }
}

// API JWT secret key
$jwtSecret = 'mR7vK2xP9Lq4Nf1Zt6bYc8Dg3Hs5Jw0Qa9Vr2Xu7Pi4Se6Tm1Un8Ao3Cd5Eg7Bh';

// Base path for the app when hosted in a subfolder
$appBasePath = '/vending-machine';
