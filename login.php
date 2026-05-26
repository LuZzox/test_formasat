<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Étudiant - FormaSat</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Espace Étudiant</h2>
        <?php if (isset($_GET['error'])): ?>
            <p style="color:red;">Email ou mot de passe incorrect.</p>
        <?php endif; ?>
        <form action="login_traitement.php" method="POST">
            <div class="form-group">
                <label>Email :</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Mot de passe :</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Se connecter</button>
        </form>
        <hr>
        <p>Pas encore de compte ? <a href="inscription.php">S'inscrire</a></p>
        <p><a href="prof_login.php">Espace Formateur</a> | <a href="admin_login.php">Administration</a></p>
    </div>
</body>
</html>