<?php
// connexion.php

function getConnexion() {
    $host   = 'localhost';
    $dbname = 'formasat';
    $user   = 'sharly';
    $pass   = ''; // Considérez l'utilisation de variables d'environnement en production
    $dsn    = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        // 1. Enregistrer l'erreur détaillée dans les logs du serveur (Apache/PHP error.log)
        error_log('Database Connection Error: ' . $e->getMessage());
        
        // 2. Afficher un message générique et sécurisé à l'utilisateur
        die('Une erreur système est survenue. Veuillez réessayer plus tard.');
    }
}