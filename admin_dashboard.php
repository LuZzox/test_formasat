<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// 1. Sécurité : Vérifier si l'utilisateur est connecté (Admin ou Étudiant selon vos besoins)
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// 2. Récupérer la liste des modules pour le sélecteur
$stmtModules = $pdo->query("SELECT id, nom FROM module ORDER BY nom ASC");
$allModules = $stmtModules->fetchAll();

// 3. Récupérer les stats si un module est sélectionné
$selectedModuleId = $_GET['module_id'] ?? null;
$stats = null;
$chartData = [];

if ($selectedModuleId) {
    $sql = "SELECT
                m.nom AS module_nom,
                s.nb_evaluations,
                s.moy_contenu,
                s.moy_pedagogie,
                s.moy_support,
                s.moy_rythme,
                s.moy_applicab AS moy_applicabilite, -- Renamed for consistency with old code
                s.moy_globale
            FROM module m
            JOIN stats_module s ON m.id = s.module_id
            WHERE m.id = ?";
    
    $stmtStats = $pdo->prepare($sql);
    $stmtStats->execute([$selectedModuleId]);
    $stats = $stmtStats->fetch();

    if ($stats) {
        $chartData = [
            (float)$stats['moy_contenu'],
            (float)$stats['moy_pedagogie'],
            (float)$stats['moy_support'],
            (float)$stats['moy_rythme'],
            (float)$stats['moy_applicabilite']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - FormaSat</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { display: block; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; width: 100%; }
        .header { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        
        /* Navigation Menu */
        .nav-admin { background: #fff; padding: 15px 20px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .nav-admin a { background: #f0f2f5; color: #1c1e21; padding: 10px 15px; border-radius: 6px; font-weight: 500; text-decoration: none; transition: background 0.3s; font-size: 0.9rem; }
        .nav-admin a:hover { background: #e4e6eb; color: #1877f2; }
        .nav-admin a.active { background: #1877f2; color: #fff; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-value { font-size: 24px; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; font-size: 14px; margin-top: 5px; }
        .chart-container { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: center; }
        canvas { max-width: 500px; }
        
        .btn-logout { color: #dc3545 !important; margin-left: auto; }
        form select { width: auto; min-width: 250px; margin: 0; }
        h2 { margin: 0; font-size: 1.4rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="nav-admin">
        <a href="admin_dashboard.php" class="active">📊 Stats Modules</a>
        <a href="add_evaluation.php">📝 Ajouter Évaluation</a>
        <a href="add_admin.php">🔑 Ajouter Admin</a>
        <a href="manage_promos.php">🏫 Gérer Promos</a>
        <a href="manage_profs.php">👨‍🏫 Gérer Profs</a>
        <a href="manage_students.php">🎓 Gérer Étudiants</a>
        <a href="manage_modules.php">📚 Gérer Modules</a>
        <a href="logout.php" class="btn-logout">Déconnexion</a>
    </div>

    <div class="header">
        <h2>Statistiques par Module</h2>
        <form action="" method="GET">
            <select name="module_id" onchange="this.form.submit()">
                <option value="">-- Choisir un module --</option>
                <?php foreach ($allModules as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $selectedModuleId == $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($stats): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['nb_evaluations'] ?></div>
                <div class="stat-label">Total Évaluations</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= round($stats['moy_globale'] * 20, 1) ?>%</div>
                <div class="stat-label">Score Satisfaction Global</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['moy_globale'] ?> / 5</div>
                <div class="stat-label">Moyenne Générale</div>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="radarChart"></canvas>
        </div>

        <script>
            const ctx = document.getElementById('radarChart').getContext('2d');
            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: ['Contenu', 'Pédagogie', 'Support', 'Rythme', 'Applicabilité'],
                    datasets: [{
                        label: 'Moyennes pour <?= addslashes($stats['module_nom']) ?>',
                        data: <?= json_encode($chartData) ?>,
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(0, 123, 255, 1)'
                    }]
                },
                options: {
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 5,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
        </script>
    <?php elseif ($selectedModuleId): ?>
        <div class="header" style="justify-content: center;">
            <p>Aucune évaluation n'a encore été soumise pour ce module.</p>
        </div>
    <?php else: ?>
        <div class="header" style="justify-content: center;">
            <p>Veuillez sélectionner un module pour visualiser les statistiques.</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
