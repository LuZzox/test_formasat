<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// 1. Vérifier s'il y a déjà des administrateurs dans la base
$stmt = $pdo->query("SELECT COUNT(*) FROM administrateur");
$count = $stmt->fetchColumn();

// Sécurité : Si un admin existe déjà, on bloque l'accès pour éviter les abus
if ($count > 0) {
    die("Un administrateur existe déjà. Cette page est réservée à la configuration initiale. <a href='admin_login.php'>Se connecter</a>");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Protection CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF invalide.");
    }

    $identifiant = trim($_POST['identifiant'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirm_mot_de_passe = $_POST['confirm_mot_de_passe'] ?? '';

    if (empty($identifiant) || empty($mot_de_passe) || empty($confirm_mot_de_passe)) {
        $message = "Tous les champs sont requis.";
    } elseif ($mot_de_passe !== $confirm_mot_de_passe) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        // 3. Hacher le mot de passe pour la sécurité
        $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);

        // 4. Insertion du premier administrateur
        $stmt = $pdo->prepare("INSERT INTO administrateur (identifiant, mot_de_passe) VALUES (?, ?)");
        if ($stmt->execute([$identifiant, $hashed_password])) {
            $message = "Premier administrateur créé avec succès !";
            unset($_SESSION['csrf_token']); // Nettoyage après succès
            $count = 1; // On simule le count pour cacher le formulaire
        } else {
            $message = "Erreur lors de la création du compte.";
        }
    }
}

// Générer le token CSRF s'il n'existe pas
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Configuration Initiale - FormaSat</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Initialisation Admin</h2>
        <p><small>Cette page ne fonctionne que si la table administrateur est vide.</small></p>
        
        <?php if ($message): ?>
            <p class="<?= strpos($message, 'succès') !== false ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></p>
            <?php if (strpos($message, 'succès') !== false): ?>
                <p><a href="admin_login.php">Accéder à la page de connexion</a></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($count == 0): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="form-group">
                <label>Identifiant :</label>
                <input type="text" name="identifiant" required>
            </div>
            <div class="form-group">
                <label>Mot de passe :</label>
                <input type="password" name="mot_de_passe" required>
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe :</label>
                <input type="password" name="confirm_mot_de_passe" required>
            </div>
            <button type="submit">Créer mon compte Admin</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>