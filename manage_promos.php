<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// 1. Sécurité : Vérifier si l'utilisateur est connecté en tant qu'administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php?error=admin_required');
    exit();
}

$message = '';
$errors = [];

// Gestion des requêtes POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Protection CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Token CSRF invalide.");
    }

    // Ajouter une Promotion
    if (isset($_POST['action']) && $_POST['action'] === 'add_promo') {
        $libelle = trim($_POST['libelle'] ?? '');
        $annee = (int)($_POST['annee'] ?? date('Y'));

        if (empty($libelle)) {
            $errors[] = "Le libellé de la promotion est requis.";
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO promo (libelle, annee) VALUES (?, ?)");
                if ($stmt->execute([$libelle, $annee])) {
                    $message = "Promotion ajoutée avec succès !";
                    $_POST = []; // Vider le formulaire
                }
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $errors[] = "Une promotion avec ce libellé existe déjà.";
                } else {
                    $errors[] = "Erreur : " . $e->getMessage();
                }
            }
        }
    }

    // Toggle Statut Actif/Inactif
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $promo_id = (int)($_POST['promo_id'] ?? 0);
        $new_status = (int)($_POST['status'] ?? 0);
        
        $stmt = $pdo->prepare("UPDATE promo SET actif = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $promo_id])) {
            $message = "Statut de la promotion mis à jour.";
        } else {
            $errors[] = "Erreur lors de la mise à jour du statut.";
        }
    }
}

// Récupération des promotions
$promos = $pdo->query("SELECT * FROM promo ORDER BY annee DESC, libelle ASC")->fetchAll();

// Générer le token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Promotions - FormaSat</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { display: block; padding: 20px; }
        .container { max-width: 1000px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1); }
        .section-title { font-size: 1.8rem; color: #1c1e21; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .form-inline { display: flex; gap: 15px; align-items: flex-end; background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .form-inline .form-group { margin-bottom: 0; flex: 1; }
        .form-inline button { width: auto; margin-top: 0; white-space: nowrap; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f0f2f5; }
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .btn-status { background: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="nav-admin">
        <a href="admin_dashboard.php">📊 Stats Modules</a>
        <a href="add_evaluation.php">📝 Ajouter Évaluation</a>
        <a href="add_admin.php">🔑 Ajouter Admin</a>
        <a href="manage_promos.php" class="active">🏫 Gérer Promos</a>
        <a href="manage_profs.php">👨‍🏫 Gérer Profs</a>
        <a href="manage_students.php">🎓 Gérer Étudiants</a>
        <a href="manage_modules.php">📚 Gérer Modules</a>
        <a href="logout.php" class="btn-logout">Déconnexion</a>
    </div>

    <div class="container">
        <h2 class="section-title">Ajouter une Promotion</h2>

        <?php if ($message): ?>
            <p class="success"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-inline">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="add_promo">
            
            <div class="form-group">
                <label>Libellé (ex: SLAM 2025A) :</label>
                <input type="text" name="libelle" required placeholder="Nom de la promo">
            </div>
            <div class="form-group">
                <label>Année :</label>
                <input type="number" name="annee" value="<?= date('Y') ?>" min="2000" max="2100" required>
            </div>
            <button type="submit">Créer la promotion</button>
        </form>

        <h2 class="section-title">Promotions existantes</h2>
        <table>
            <thead>
                <tr>
                    <th>Libellé</th>
                    <th>Année</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promos as $p): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['libelle']) ?></strong></td>
                    <td><?= htmlspecialchars($p['annee']) ?></td>
                    <td>
                        <span class="badge <?= $p['actif'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $p['actif'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="promo_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="status" value="<?= $p['actif'] ? 0 : 1 ?>">
                            <button type="submit" class="btn-status">
                                <?= $p['actif'] ? 'Désactiver' : 'Activer' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>