<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Tentative de connexion en tant qu'étudiant uniquement
    $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE email = ?");
    $stmt->execute([$email]);
    $etudiant = $stmt->fetch();

    if ($etudiant && password_verify($password, $etudiant['mot_de_passe'])) {
        $_SESSION['etudiant_id'] = $etudiant['id'];
        $_SESSION['etudiant_name'] = $etudiant['prenom'] . ' ' . $etudiant['nom'];
        header("Location: dashboard.php");
        exit;
    }

    header("Location: login.php?error=1");
    exit;
}