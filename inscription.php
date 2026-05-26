<?php
session_start();
require_once 'connexion.php';
$pdo = getConnexion();

// Récupérer la liste des promotions actives pour le sélecteur
$stmtPromos = $pdo->query("SELECT id, libelle FROM promo WHERE actif = 1 ORDER BY libelle ASC");
$promos = $stmtPromos->fetchAll(PDO::FETCH_ASSOC);

// Gérer les erreurs et les données du formulaire depuis la session si redirigé depuis inscription_traitement.php
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];

// Nettoyer les données de session après les avoir récupérées
unset($_SESSION['errors']);
unset($_SESSION['form_data']);

// Générer le token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - FormaSat</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .form-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
        h2 { color: #333; text-align: center; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Ensures padding doesn't increase width */
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            margin-top: 1.5rem;
        }
        button:hover { background-color: #0056b3; }
        .errors {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .errors ul {
            margin: 0;
            padding-left: 20px;
        }
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Inscription Étudiant</h2>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="inscription_traitement.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="form-group"><label for="nom">Nom :</label><input type="text" id="nom" name="nom" value="<?= htmlspecialchars($formData['nom'] ?? '') ?>" required></div>
        <div class="form-group"><label for="prenom">Prénom :</label><input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($formData['prenom'] ?? '') ?>" required></div>
        <div class="form-group"><label for="email">Email :</label><input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required></div>
        <div class="form-group"><label for="mot_de_passe">Mot de passe :</label><input type="password" id="mot_de_passe" name="mot_de_passe" required></div>
        <div class="form-group"><label for="confirmation">Confirmer le mot de passe :</label><input type="password" id="confirmation" name="confirmation" required></div>

        <div class="form-group">
            <label for="promo_id">Promotion :</label>
            <select id="promo_id" name="promo_id" required>
                <option value="">-- Sélectionner une promotion --</option>
                <?php foreach ($promos as $promo): ?>
                    <option value="<?= $promo['id'] ?>"
                        <?= (isset($formData['promo_id']) && $formData['promo_id'] == $promo['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($promo['libelle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">S'inscrire</button>
    </form>

    <div class="login-link">
        <p>Déjà un compte ? <a href="login.php">Connectez-vous ici</a></p>
    </div>
</div>

</body>
</html>