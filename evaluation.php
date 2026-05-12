<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['etudiant_id'])) {
    header('Location: login.php');
    exit();
}

// Récupération des modules pour le select
$stmt = $pdo->query("SELECT id, nom FROM module ORDER BY nom ASC");
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Évaluer un module - FormaSat</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .form-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        h2 { color: #333; text-align: center; }
        .form-group { margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .rating-group { display: flex; gap: 15px; align-items: center; }
        select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; }
        button { background-color: #007bff; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 1rem; }
        button:hover { background-color: #0056b3; }
        .radio-item { display: flex; align-items: center; gap: 5px; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Évaluation de fin de module</h2>
    <form action="evaluation_traitement.php" method="POST">
        
        <div class="form-group">
            <label for="module_id">Module suivi :</label>
            <select name="module_id" id="module_id" required>
                <option value="">-- Sélectionnez un module --</option>
                <?php foreach ($modules as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php
        $criteres = [
            'note_contenu' => 'Qualité du contenu',
            'note_pedagogie' => 'Pédagogie du formateur',
            'note_support' => 'Qualité des supports',
            'note_rythme' => 'Rythme de la formation',
            'note_applicabilite' => 'Applicabilité pro'
        ];
        foreach ($criteres as $name => $label): ?>
            <div class="form-group">
                <label><?= $label ?> (de 1 à 5) :</label>
                <div class="rating-group">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <div class="radio-item">
                            <input type="radio" name="<?= $name ?>" value="<?= $i ?>" id="<?= $name . $i ?>" required>
                            <label for="<?= $name . $i ?>" style="font-weight: normal;"><?= $i ?></label>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="form-group">
            <label for="commentaire">Commentaires libres (optionnel) :</label>
            <textarea name="commentaire" id="commentaire" rows="4" placeholder="Votre avis nous intéresse..."></textarea>
        </div>

        <button type="submit">Envoyer mon évaluation</button>
    </form>
</div>

</body>
</html>