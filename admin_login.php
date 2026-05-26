<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administration - FormaSat</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #2c3e50; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 1.5rem; }
        .error-msg { background-color: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; text-align: center; font-size: 0.9rem; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #34495e; font-weight: 600; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 1rem; }
        input:focus { border-color: #3498db; outline: none; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); }
        button { width: 100%; padding: 0.75rem; border: none; border-radius: 4px; background-color: #e74c3c; color: white; font-size: 1rem; font-weight: bold; cursor: pointer; transition: background 0.3s; }
        button:hover { background-color: #c0392b; }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: #7f8c8d; text-decoration: none; font-size: 0.9rem; }
        .back-link:hover { color: #34495e; text-decoration: underline; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Espace Admin</h2>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-msg">Identifiants incorrects ou accès refusé.</div>
    <?php endif; ?>

    <form action="traitement_admin_login.php" method="POST">
        <div class="form-group">
            <label for="identifiant">Identifiant</label>
            <input type="text" id="identifiant" name="identifiant" required placeholder="Ex: admin">
        </div>

        <div class="form-group">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required>
        </div>

        <button type="submit">Se connecter au panel</button>
    </form>

    <a href="login.html" class="back-link">Retour à la connexion étudiant</a>
</div>

</body>
</html>