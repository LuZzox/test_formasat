<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Récupérer et nettoyer les données (trim, htmlspecialchars)
    $nom      = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $prenom   = htmlspecialchars(trim($_POST['prenom'] ?? ''));
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';
    $promoId  = (int)($_POST['promo_id'] ?? 0); // Assuming promo_id is passed from the form
    $confirm  = $_POST['confirmation'] ?? '';

    $errors = [];

    // 2. Valider : champs vides ? email valide ? mots de passe identiques ?
    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($confirm)) {
        $errors[] = "Tous les champs sont obligatoires.";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    if ($password !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Appel de la procédure stockée
        $stmt = $pdo->prepare("CALL sp_inscrire_etudiant(?, ?, ?, ?, ?, @succes, @message)");
        $stmt->execute([$nom, $prenom, $email, $hashedPassword, $promoId]);
        
        $res = $pdo->query("SELECT @succes AS succes, @message AS message")->fetch();

        if ($res['succes']) {
            $_SESSION['success_message'] = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            header('Location: login.php');
            exit;
        }
        $errors[] = $res['message'];
    }

    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: inscription.php');
    exit;
}