<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// Vérifier que l'utilisateur est connecté en tant qu'étudiant
if (!isset($_SESSION['etudiant_id'])) {
    header('Location: login.html');
    exit();
}

$etudiant_id = $_SESSION['etudiant_id'];
$etudiant_name = $_SESSION['etudiant_name'];

// Récupérer les modules de la promotion de l'étudiant
$stmt = $pdo->prepare("
    SELECT 
        m.id, 
        m.nom AS module_nom, 
        p.libelle AS promo_libelle,
        prof.prenom AS prof_prenom,
        prof.nom AS prof_nom,
        (SELECT COUNT(*) FROM evaluation e WHERE e.etudiant_id = :etudiant_id AND e.module_id = m.id) AS a_evalue
    FROM etudiant e_etu
    JOIN promo p ON e_etu.promo_id = p.id
    JOIN module m ON m.promo_id = p.id
    JOIN prof ON m.prof_id = prof.id
    WHERE e_etu.id = :etudiant_id
    ORDER BY m.nom
");
$stmt->execute([':etudiant_id' => $etudiant_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Message de succès après évaluation
$success_message = '';
if (isset($_GET['noted']) && $_GET['noted'] == 'success') {
    $success_message = 'Votre évaluation a été enregistrée avec succès !';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Étudiant - FormaSat</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 20px; }
        .welcome { text-align: center; margin-bottom: 30px; font-size: 1.1em; }
        .module-list { list-style: none; padding: 0; }
        .module-item { background: #e9ecef; margin-bottom: 10px; padding: 15px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .module-item.evaluated { background: #d4edda; }
        .module-item.not-evaluated { background: #fff3cd; }
        .module-info { flex-grow: 1; }
        .module-actions a { text-decoration: none; background: #007bff; color: white; padding: 8px 12px; border-radius: 5px; }
        .module-actions a:hover { background: #0056b3; }
        .logout-link { display: block; text-align: right; margin-top: 20px; }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bienvenue, <?= htmlspecialchars($etudiant_name) ?> !</h1>
        <?php if ($success_message): ?>
            <div class="success-message"><?= $success_message ?></div>
        <?php endif; ?>
        <p class="welcome">Voici la liste de vos modules. N'oubliez pas de les évaluer !</p>
        <ul class="module-list">
            <?php foreach ($modules as $module): ?>
                <li class="module-item <?= $module['a_evalue'] > 0 ? 'evaluated' : 'not-evaluated' ?>">
                    <div class="module-info">
                        <strong><?= htmlspecialchars($module['module_nom']) ?></strong><br>
                        Promotion: <?= htmlspecialchars($module['promo_libelle']) ?><br>
                        Professeur: <?= htmlspecialchars($module['prof_prenom'] . ' ' . $module['prof_nom']) ?>
                    </div>
                    <div class="module-actions">
                        <?php if ($module['a_evalue'] > 0): ?>
                            <span>Évalué</span>
                        <?php else: ?>
                            <a href="evaluate.php?module_id=<?= $module['id'] ?>">Évaluer</a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>

        <div class="logout-link">
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>
</body>
</html>