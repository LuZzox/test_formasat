<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// 1. Sécurité : Vérifier si l'administrateur est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php?error=admin_required'); // Redirection vers la page de login avec un message d'erreur
    exit();
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF invalide.");
    }

    $critere_name = trim($_POST['critere'] ?? '');
    // Génère un nom de colonne propre (ex: "note_moyens_techniques")
    $column_name = 'note_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $critere_name));

    if (empty($critere_name)) {
        $errors[] = "Le nom du critère est requis.";
    } else {
        try {
            // Ajout physique de la colonne dans la table
            $sql = "ALTER TABLE evaluation ADD COLUMN $column_name TINYINT NOT NULL DEFAULT 0";
            $pdo->exec($sql);
            $success = "Le critère '$critere_name' a été ajouté à la base de données (colonne : $column_name).";
        } catch (PDOException $e) {
            $errors[] = "Erreur SQL : " ; // La colonne existe peut-être déjà
        }
    }
}

if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Critère d'Évaluation</title>    
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Ajouter un Critère d'Évaluation</h1>
    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?= htmlspecialchars($success) ?><br>
            <small>Note : Vous devez maintenant mettre à jour vos fichiers PHP pour afficher ce nouveau champ.</small>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="form-group">
            <label for="critere">Nom du critère d'évaluation :</label>
            <input type="text" name="critere" id="critere" required placeholder="Ex: Moyens techniques">
        </div>
        <button type="submit" name="submit">Ajouter le critère</button>
        <p><a href="admin_dashboard.php">Retour au dashboard</a></p>
    </form>
</body>
</html>