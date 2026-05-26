<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// 1. Sécurité : Vérifier si l'utilisateur est connecté en tant qu'administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Protection CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF invalide.");
    }

    $nom_promotion = trim($_POST['nom_promotion'] ?? '');

    if (empty($nom_promotion)) {
        $message = "Le nom de la promotion est requis.";
    } else { 
        // 3. Vérifier l'unicité du nom de la promo
        $stmt = $pdo->prepare("SELECT id FROM promo WHERE libelle = ?");
        $stmt->execute([$nom_promotion]);
        if ($stmt->fetch()) {
            $message = "Une promotion avec ce nom existe déjà.";
        } else {
            // 4. Insertion dans la base de données (table promo)
            $stmt = $pdo->prepare("INSERT INTO promo (libelle, annee) VALUES (?, YEAR(CURRENT_DATE()))");
            if ($stmt->execute([$nom_promotion])) {
                $message = "Promotion ajoutée avec succès.";
            } else {
                $message = "Erreur lors de l'ajout de la promotion.";
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
    <title>Ajouter une Promotion</title>
    <link rel="stylesheet" href="styles.css"> <!-- Assurez-vous que ce fichier CSS existe -->
</head>
<body>
    <h1>Ajouter une Promotion</h1>
    <?php if ($message): ?>
        <p style="color: <?= strpos($message, 'succès') !== false ? 'green' : 'red' ?>;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST" action="add_promotion.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="form-group">
            <label for="nom_promotion">Nom de la Promotion:</label>
            <input type="text" id="nom_promotion" name="nom_promotion" required maxlength="100">
        </div>
        <button type="submit">Ajouter la Promotion</button>
    </form>
    <p><a href="admin_dashboard.php">Retour au tableau de bord</a></p>
</body>
</html>