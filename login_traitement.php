<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Tentative de connexion en tant qu'administrateur
    $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE identifiant = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['mot_de_passe'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['username'] = $admin['identifiant'];
        header("Location: admin_dashboard.php");
        exit;
    }

    // 2. Si non admin, tentative de connexion en tant qu'étudiant (par email)
    $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE email = ?");
    $stmt->execute([$username]);
    $etudiant = $stmt->fetch();

    if ($etudiant && password_verify($password, $etudiant['mot_de_passe'])) {
        $_SESSION['etudiant_id'] = $etudiant['id'];
        $_SESSION['etudiant_name'] = $etudiant['prenom'] . ' ' . $etudiant['nom'];
        header("Location: dashboard.php");
        exit;
    }

    // 3. Échec de connexion
    echo "Nom d'utilisateur ou mot de passe incorrect.";
}