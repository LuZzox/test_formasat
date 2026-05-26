<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM prof WHERE email = ?");
    $stmt->execute([$email]);
    $prof = $stmt->fetch();

    if ($prof && password_verify($password, $prof['mot_de_passe'])) {
        $_SESSION['prof_id'] = $prof['id'];
        $_SESSION['prof_name'] = $prof['prenom'] . ' ' . $prof['nom'];
        header("Location: prof_dashboard.php");
        exit;
    }

    header("Location: prof_login.php?error=1");
    exit;
}