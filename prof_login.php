<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Formateur - FormaSat</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Espace Formateur</h2>
        <?php if (isset($_GET['error'])): ?>
            <p style="color:red;">Email ou mot de passe incorrect.</p>
        <?php endif; ?>
        <form action="traitement_prof_login.php" method="POST">
            <div class="form-group">
                <label>Email Professionnel :</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Mot de passe :</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Se connecter</button>
        </form>
        <p><a href="login.php">Connexion étudiant</a></p>
    </div>
</body>
</html>