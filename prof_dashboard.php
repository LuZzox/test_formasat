<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// Vérifier que l'utilisateur est connecté en tant que professeur
if (!isset($_SESSION['prof_id'])) {
    header('Location: login.php');
    exit();
}

$prof_id = $_SESSION['prof_id'];
$prof_name = $_SESSION['prof_name'];

// Récupérer les modules enseignés par ce professeur
$stmt = $pdo->prepare("
    SELECT 
        m.id, 
        m.nom AS module_nom, 
        p.libelle AS promo_libelle,
        s.nb_evaluations,
        s.moy_globale
    FROM module m
    JOIN promo p ON m.promo_id = p.id
    LEFT JOIN stats_module s ON m.id = s.module_id
    WHERE m.prof_id = :prof_id
    ORDER BY m.nom
");
$stmt->execute([':prof_id' => $prof_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Professeur - FormaSat</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 20px; }
        .welcome { text-align: center; margin-bottom: 30px; font-size: 1.1em; }
        .module-list { list-style: none; padding: 0; }
        .module-item { background: #e9ecef; margin-bottom: 10px; padding: 15px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .module-info { flex-grow: 1; }
        .module-stats { margin-left: 20px; text-align: right; }
        .module-actions a { text-decoration: none; background: #007bff; color: white; padding: 8px 12px; border-radius: 5px; }
        .module-actions a:hover { background: #0056b3; }
        .logout-link { display: block; text-align: right; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bienvenue, <?= htmlspecialchars($prof_name) ?> !</h1>
        <p class="welcome">Voici les modules que vous enseignez et leurs statistiques d'évaluation.</p>

        <ul class="module-list">
            <?php if (empty($modules)): ?>
                <li class="module-item">Aucun module ne vous est actuellement assigné.</li>
            <?php else: ?>
                <?php foreach ($modules as $module): ?>
                    <li class="module-item">
                        <div class="module-info">
                            <strong><?= htmlspecialchars($module['module_nom']) ?></strong> (<?= htmlspecialchars($module['promo_libelle']) ?>)
                        </div>
                        <div class="module-stats">
                            Évaluations : <?= $module['nb_evaluations'] ?? 0 ?> <br>
                            Moyenne : <?= number_format($module['moy_globale'] ?? 0, 2) ?> / 5
                        </div>
                        <div class="module-actions">
                            <a href="admin_dashboard.php?module_id=<?= $module['id'] ?>">Voir les stats détaillées</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

        <div class="logout-link">
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>
</body>
</html>