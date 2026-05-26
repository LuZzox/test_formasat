<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// 1. pas de securiter car temporaire, mais a faire plus tard
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php?error=admin_required'); // Redirection vers la page de login avec un message d'erreur
    exit();
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
        // 3. Vérifier l'unicité de l'identifiant
        $stmt = $pdo->prepare("SELECT id FROM administrateur WHERE identifiant = ?");
        $stmt->execute([$identifiant]);
        if ($stmt->fetch()) {
            $message = "Cet identifiant est déjà utilisé.";
        } else {
            // 4. Hacher le mot de passe
            $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            // 5. Insertion dans la base de données
            $stmt = $pdo->prepare("INSERT INTO administrateur (identifiant, mot_de_passe) VALUES (?, ?)");
            if ($stmt->execute([$identifiant, $hashed_password])) {
                $message = "Administrateur ajouté avec succès.";
            } else {
                $message = "Erreur lors de l'ajout de l'administrateur.";
            }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Administrateur</title>
    <link rel="stylesheet" href="styles.css"> <!-- Assurez-vous que ce fichier CSS existe -->
</head>
<body>
    <h1>Ajouter un Administrateur</h1>
    <?php if ($message): ?>
        <p style="color: <?= strpos($message, 'succès') !== false ? 'green' : 'red' ?>;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST" action="add_admin.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <label for="identifiant">Identifiant:</label>
        <input type="text" id="identifiant" name="identifiant" required><br><br>
        <label for="mot_de_passe">Mot de passe:</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required><br><br>
        <label for="confirm_mot_de_passe">Confirmer le mot de passe:</label>
        <input type="password" id="confirm_mot_de_passe" name="confirm_mot_de_passe" required><br><br>
        <input type="submit" value="Ajouter l'Administrateur">
    </form>
    <p><a href="admin_dashboard.php">Retour au tableau de bord</a></p>
</body>
</html>