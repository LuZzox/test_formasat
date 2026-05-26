<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';

    if (!empty($identifiant) && !empty($password)) {
        // Recherche de l'administrateur
        $stmt = $pdo->prepare("SELECT id, identifiant, mot_de_passe FROM administrateur WHERE identifiant = ?");
        $stmt->execute([$identifiant]);
        $admin = $stmt->fetch();

        // Vérification du mot de passe (haché avec password_hash)
        if ($admin && password_verify($password, $admin['mot_de_passe'])) {
            // Authentification réussie
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['identifiant'];
            
            // Redirection vers le dashboard admin
            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // En cas d'échec
    header("Location: admin_login.php?error=1");
    exit();
} else {
    header("Location: admin_login.php");
}