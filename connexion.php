<?php
// connexion.php

function getConnexion() {
    $host   = 'localhost';
    $dbname = 'formasat';      // À compléter
    $user   = 'sharly';      // À compléter
    $pass   = '';      // À compléter
    $dsn    = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        die('Erreur connexion : ' . $e->getMessage());
    }
}

