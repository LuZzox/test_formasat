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

    // Ajouter un Module
    if (isset($_POST['action']) && $_POST['action'] === 'add_module') {
        $nom = trim($_POST['nom'] ?? '');
        $prof_id = (int)($_POST['prof_id'] ?? 0);
        $promo_id = (int)($_POST['promo_id'] ?? 0);
        $date_debut = $_POST['date_debut'] ?? '';
        $date_fin = $_POST['date_fin'] ?? '';
        $description = trim($_POST['description'] ?? '');

        if (empty($nom) || $prof_id <= 0 || $promo_id <= 0 || empty($date_debut) || empty($date_fin)) {
            $errors[] = "Tous les champs obligatoires doivent être remplis.";
        } elseif ($date_fin <= $date_debut) {
            $errors[] = "La date de fin doit être postérieure à la date de début.";
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO module (nom, prof_id, promo_id, date_debut, date_fin, description) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$nom, $prof_id, $promo_id, $date_debut, $date_fin, $description])) {
                    $message = "Module ajouté avec succès !";
                    $_POST = []; // Vider le formulaire
                } else {
                    $errors[] = "Erreur lors de l'ajout du module.";
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur de base de données : " . $e->getMessage();
            }
        }
    }

    // Supprimer un Module
    if (isset($_POST['action']) && $_POST['action'] === 'delete_module') {
        $module_id = (int)($_POST['module_id'] ?? 0);
        if ($module_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM module WHERE id = ?");
            if ($stmt->execute([$module_id])) {
                $message = "Module supprimé avec succès.";
            } else {
                $errors[] = "Erreur lors de la suppression du module.";
            }
        }
    }
}

// Récupération des données pour les sélecteurs et le tableau
$professeurs = $pdo->query("SELECT id, nom, prenom FROM prof WHERE actif = 1 ORDER BY nom ASC")->fetchAll();
$promotions = $pdo->query("SELECT id, libelle FROM promo WHERE actif = 1 ORDER BY libelle ASC")->fetchAll();

$sql = "SELECT m.*, p.nom as prof_nom, p.prenom as prof_prenom, pr.libelle as promo_libelle 
        FROM module m
        JOIN prof p ON m.prof_id = p.id
        JOIN promo pr ON m.promo_id = pr.id
        ORDER BY m.date_debut DESC";
$modules = $pdo->query($sql)->fetchAll();

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
    <title>Gérer les Modules - FormaSat</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { display: block; padding: 20px; }
        .container { max-width: 1200px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1); }
        .section-title { font-size: 1.8rem; color: #1c1e21; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9rem; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f0f2f5; font-weight: 600; }
        .btn-delete { background-color: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; width: auto; font-size: 0.8rem; margin: 0; }
    </style>
</head>
<body>
    <div class="nav-admin">
        <a href="admin_dashboard.php">📊 Stats Modules</a>
        <a href="add_evaluation.php">📝 Ajouter Évaluation</a>
        <a href="add_admin.php">🔑 Ajouter Admin</a>
        <a href="manage_promos.php">🏫 Gérer Promos</a>
        <a href="manage_profs.php">👨‍🏫 Gérer Profs</a>
        <a href="manage_students.php">🎓 Gérer Étudiants</a>
        <a href="manage_modules.php" class="active">📚 Gérer Modules</a>
        <a href="logout.php" class="btn-logout">Déconnexion</a>
    </div>

    <div class="container">
        <h2 class="section-title">Ajouter un nouveau Module</h2>

        <?php if ($message): ?>
            <p class="success"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="add_module">
            
            <div class="form-group">
                <label>Nom du module :</label>
                <input type="text" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Professeur :</label>
                    <select name="prof_id" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($professeurs as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (($_POST['prof_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nom'] . " " . $p['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Promotion :</label>
                    <select name="promo_id" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($promotions as $pr): ?>
                            <option value="<?= $pr['id'] ?>" <?= (($_POST['promo_id'] ?? '') == $pr['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pr['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Date de début :</label>
                    <input type="date" name="date_debut" required value="<?= htmlspecialchars($_POST['date_debut'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Date de fin :</label>
                    <input type="date" name="date_fin" required value="<?= htmlspecialchars($_POST['date_fin'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Description (optionnel) :</label>
                <textarea name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <button type="submit">Créer le module</button>
        </form>

        <h2 class="section-title" style="margin-top: 40px;">Modules existants</h2>
        <table>
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Promotion</th>
                    <th>Formateur</th>
                    <th>Période</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modules as $m): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($m['nom']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($m['description']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($m['promo_libelle']) ?></td>
                    <td><?= htmlspecialchars($m['prof_prenom'] . " " . $m['prof_nom']) ?></td>
                    <td>
                        Du <?= date('d/m/Y', strtotime($m['date_debut'])) ?><br>
                        au <?= date('d/m/Y', strtotime($m['date_fin'])) ?>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Supprimer ce module ? Cela supprimera également toutes les statistiques et évaluations liées.');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="delete_module">
                            <input type="hidden" name="module_id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn-delete">Supprimer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($modules)): ?>
                    <tr><td colspan="5" style="text-align:center;">Aucun module trouvé.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>