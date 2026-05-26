<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();
// 1. Sécurité : Vérifier si l'utilisateur est connecté (Admin seulement pour ajouter une évaluation)
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sécurité : Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF invalide.");
    }

    $moduleId = $_POST['module_id'] ?? null;
    $etudiantId = $_POST['etudiant_id'] ?? null;
    $notes = [
        'note_contenu' => (int)($_POST['note_contenu'] ?? 0),
        'note_pedagogie' => (int)($_POST['note_pedagogie'] ?? 0),
        'note_support' => (int)($_POST['note_support'] ?? 0),
        'note_rythme' => (int)($_POST['note_rythme'] ?? 0),
        'note_applicabilite' => (int)($_POST['note_applicabilite'] ?? 0)
    ];
    $commentaire = trim($_POST['commentaire'] ?? '');

    if (!$moduleId) $errors[] = "Veuillez sélectionner un module.";
    if (!$etudiantId) $errors[] = "Veuillez sélectionner un étudiant.";
    foreach ($notes as $n) {
        if ($n < 1 || $n > 5) { $errors[] = "Toutes les notes doivent être entre 1 et 5."; } // Removed break to show all errors
    }
    
    if (empty($errors)) {
        // Vérifier si une évaluation existe déjà pour éviter l'erreur Duplicate Entry
        $checkStmt = $pdo->prepare("SELECT id FROM evaluation WHERE etudiant_id = ? AND module_id = ?");
        $checkStmt->execute([$etudiantId, $moduleId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // Mise à jour de l'évaluation existante
            $sql = "UPDATE evaluation SET note_contenu = ?, note_pedagogie = ?, note_support = ?, note_rythme = ?, note_applicabilite = ?, commentaire = ?, date_creation = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$notes['note_contenu'], $notes['note_pedagogie'], $notes['note_support'], $notes['note_rythme'], $notes['note_applicabilite'], $commentaire, $existing['id']]);
        } else {
            // Insertion d'une nouvelle évaluation
            $stmt = $pdo->prepare("INSERT INTO evaluation (etudiant_id, module_id, note_contenu, note_pedagogie, note_support, note_rythme, note_applicabilite, commentaire) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$etudiantId, $moduleId, $notes['note_contenu'], $notes['note_pedagogie'], $notes['note_support'], $notes['note_rythme'], $notes['note_applicabilite'], $commentaire]);
        }

        header('Location: admin_dashboard.php?module_id=' . $moduleId);
        exit();
    }
}

if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// 2. Récupérer la liste des modules pour le sélecteur
$stmtModules = $pdo->query("SELECT id, nom FROM module ORDER BY nom ASC");
$allModules = $stmtModules->fetchAll();

// Récupérer la liste des étudiants
$stmtEtudiants = $pdo->query("SELECT id, nom, prenom FROM etudiant ORDER BY nom ASC");
$allEtudiants = $stmtEtudiants->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une évaluation - Admin</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f9; padding: 20px; }
        .card { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 10px; font-weight: bold; }
        select, input, textarea { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        button { background: #007bff; color: white; border: none; padding: 10px; width: 100%; margin-top: 20px; cursor: pointer; border-radius: 4px; }
        .errors { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Nouvelle évaluation (Admin)</h1>
        <?php if ($errors): ?>
            <div class="errors"><ul><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <label>Module :</label>
            <select name="module_id" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($allModules as $m): ?><option value="<?= $m['id'] ?>" <?= (isset($_POST['module_id']) && $_POST['module_id'] == $m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nom']) ?></option><?php endforeach; ?>
            </select>

            <label>Étudiant :</label>
            <select name="etudiant_id" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($allEtudiants as $e): ?><option value="<?= $e['id'] ?>" <?= (isset($_POST['etudiant_id']) && $_POST['etudiant_id'] == $e['id']) ? 'selected' : '' ?>><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></option><?php endforeach; ?>
            </select>

            <label>Contenu (1-5) :</label><input type="number" name="note_contenu" min="1" max="5" value="<?= htmlspecialchars($_POST['note_contenu'] ?? '') ?>" required>
            <label>Pédagogie (1-5) :</label><input type="number" name="note_pedagogie" min="1" max="5" value="<?= htmlspecialchars($_POST['note_pedagogie'] ?? '') ?>" required>
            <label>Support (1-5) :</label><input type="number" name="note_support" min="1" max="5" value="<?= htmlspecialchars($_POST['note_support'] ?? '') ?>" required>
            <label>Rythme (1-5) :</label><input type="number" name="note_rythme" min="1" max="5" value="<?= htmlspecialchars($_POST['note_rythme'] ?? '') ?>" required>
            <label>Applicabilité (1-5) :</label><input type="number" name="note_applicabilite" min="1" max="5" value="<?= htmlspecialchars($_POST['note_applicabilite'] ?? '') ?>" required>
            <label>Commentaire :</label>
            <textarea name="commentaire" rows="3"><?= htmlspecialchars($_POST['commentaire'] ?? '') ?></textarea>

            <button type="submit">Enregistrer l'évaluation</button>
            <p style="text-align:center;"><a href="admin_dashboard.php">Annuler</a></p>
        </form>
    </div>
</body>
</html>