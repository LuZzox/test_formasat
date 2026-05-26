<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// 1. Sécurité : Vérifier l'accès professeur
if (!isset($_SESSION['prof_id'])) {
    header('Location: login.php');
    exit();
}

$prof_id = $_SESSION['prof_id'];
$module_id = (int)($_GET['module_id'] ?? 0);

// 2. Vérifier que le module appartient bien à ce professeur
$stmt = $pdo->prepare("
    SELECT m.*, p.libelle AS promo_libelle 
    FROM module m 
    JOIN promo p ON m.promo_id = p.id 
    WHERE m.id = ? AND m.prof_id = ?
");
$stmt->execute([$module_id, $prof_id]);
$module = $stmt->fetch();

if (!$module) {
    die("Module non trouvé ou vous n'avez pas les droits pour voir ces statistiques.");
}

// 3. Récupérer les statistiques agrégées
$stmtStats = $pdo->prepare("SELECT * FROM stats_module WHERE module_id = ?");
$stmtStats->execute([$module_id]);
$stats = $stmtStats->fetch();

// 4. Récupérer les commentaires des étudiants
$stmtComments = $pdo->prepare("
    SELECT commentaire, date_creation 
    FROM evaluation 
    WHERE module_id = ? AND commentaire IS NOT NULL AND commentaire != '' 
    ORDER BY date_creation DESC
");
$stmtComments->execute([$module_id]);
$comments = $stmtComments->fetchAll();

$chartData = $stats ? [
    (float)$stats['moy_contenu'],
    (float)$stats['moy_pedagogie'],
    (float)$stats['moy_support'],
    (float)$stats['moy_rythme'],
    (float)$stats['moy_applicab']
] : [0, 0, 0, 0, 0];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Stats détaillées - <?= htmlspecialchars($module['nom']) ?></title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { display: block; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .stats-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .chart-section { display: flex; flex-wrap: wrap; gap: 40px; align-items: center; justify-content: center; margin-bottom: 40px; }
        .radar-container { width: 100%; max-width: 450px; }
        .comments-section { margin-top: 30px; }
        .comment-item { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #1877f2; }
        .comment-date { font-size: 0.8rem; color: #65676b; margin-bottom: 5px; }
        .score-box { background: #e7f3ff; color: #1877f2; padding: 15px 25px; border-radius: 12px; text-align: center; }
        .score-value { font-size: 2rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="stats-header">
            <div>
                <h1><?= htmlspecialchars($module['nom']) ?></h1>
                <p>Promotion : <strong><?= htmlspecialchars($module['promo_libelle']) ?></strong></p>
            </div>
            <div class="score-box">
                <div class="score-label">Moyenne Globale</div>
                <div class="score-value"><?= number_format($stats['moy_globale'] ?? 0, 2) ?> / 5</div>
                <small><?= $stats['nb_evaluations'] ?? 0 ?> évaluations</small>
            </div>
        </div>

        <?php if ($stats && $stats['nb_evaluations'] > 0): ?>
            <div class="chart-section">
                <div class="radar-container">
                    <canvas id="radarChart"></canvas>
                </div>
                <div style="flex: 1; min-width: 300px;">
                    <h3>Détails des critères</h3>
                    <ul>
                        <li>Qualité du contenu : <strong><?= $stats['moy_contenu'] ?>/5</strong></li>
                        <li>Pédagogie : <strong><?= $stats['moy_pedagogie'] ?>/5</strong></li>
                        <li>Support de cours : <strong><?= $stats['moy_support'] ?>/5</strong></li>
                        <li>Rythme : <strong><?= $stats['moy_rythme'] ?>/5</strong></li>
                        <li>Applicabilité pro : <strong><?= $stats['moy_applicab'] ?>/5</strong></li>
                    </ul>
                </div>
            </div>

            <div class="comments-section">
                <h2>Commentaires des étudiants</h2>
                <?php if (empty($comments)): ?>
                    <p>Aucun commentaire écrit pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($comments as $c): ?>
                        <div class="comment-item">
                            <div class="comment-date">Le <?= date('d/m/Y', strtotime($c['date_creation'])) ?></div>
                            <div class="comment-text">" <?= nl2br(htmlspecialchars($c['commentaire'])) ?> "</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; padding: 50px;">Aucune donnée statistique disponible pour ce module.</p>
        <?php endif; ?>

        <hr>
        <p style="text-align: center;"><a href="prof_dashboard.php" class="btn">Retour au tableau de bord</a></p>
    </div>

    <script>
        const ctx = document.getElementById('radarChart').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Contenu', 'Pédagogie', 'Support', 'Rythme', 'Applicabilité'],
                datasets: [{
                    label: 'Moyennes',
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: 'rgba(24, 119, 242, 0.2)',
                    borderColor: '#1877f2',
                    pointBackgroundColor: '#1877f2',
                }]
            },
            options: {
                scales: {
                    r: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
</body>
</html>